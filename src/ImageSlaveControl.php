<?php

namespace App\Form\Control;

use Nette;
use Nette\Http\FileUpload;
use Nette\Utils\Html;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Latte\Engine;
use App\Components\ImageSlave;

/**
 * Description of Imager
 *
 * @author Michal Landsman <landsman@studioart.cz>
 */
class ImageSlaveControl extends Nette\Forms\Controls\BaseControl
{
    /**
     * @var array
     */
    private $file;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $hidden;

    /**
     * @var string
     */
    private $delete;

    /**
     * @var array
     */
    private $options;

    /**
     * @var
     */
    private $request;

    /**
     * ImageUploadControl constructor.
     * @param null $name
     * @param null $label
     * @param $config
     */
    public function __construct($name = null, $label = NULL, $config)
    {
        parent::__construct($label);
        $this->monitor(Nette\Forms\Container::class);
        //$this->control->type     = 'file';
        //$this->control->multiple = false;
        $this->name = $name;
        $this->options = $config;

    }

    public function setPath($path)
    {
        $this->options['path'] = $path;
        return $this;
    }

    public function setUseTimestamp($result)
    {
        $this->options['useTimestamp'] = $result;
    }

    /**
     * Todo: allow array for multiple images (database json with title / order, ...)
     * @param $format
     */
    public function setValueFormat($format)
    {
        $this->options['valueFormat'] = $format;
    }

    /**
     * Todo: is good??
     * This method will be called when the component (or component's parent)
     * becomes attached to a monitored object. Do not call this method yourself.
     * @param  Nette\ComponentModel\IComponent
     * @return void
     */

    protected function attached($form)
    {
        if ($form instanceof Form) {
            if ($form->getMethod() !== Form::POST) {
                throw new Nette\InvalidStateException('File upload requires method POST.');
            }
            $form->getElementPrototype()->enctype = 'multipart/form-data';
        }
        parent::attached($form);
    }


    /**
     * Loads HTTP data.
     * @return void
     */
    public function loadHttpData()
    {
        $this->request  = $this->getForm()->getHttpData();
        $this->file     = $this->getHttpData(Form::DATA_FILE);
        $this->hidden   = $this->getForm()->getHttpData(Form::DATA_LINE, $this->getActualContainer($this->options['hiddenName']));
        $this->delete   = $this->getForm()->getHttpData(Form::DATA_LINE, $this->getActualContainer($this->options['deleteName']));

        /**
         * upload picture
         */
        if ($this->file !== NULL)
        {
            $path = $this->options['path'] . "/" . ($this->options['useTimestamp'] ? time() . "_" : "") . $this->file->getName();
            $this->file->move($this->options['wwwDir'] .  "/"  . $path);

            /**
             * rewriting picture, old remove
             */
            if(!empty($this->hidden))
            {
                $this->deleteImage();
            }

            $this->setValue($path);
            $this->hidden = $path;
        }

        /**
         * just deleting
         */
        if(!empty($this->delete) && !empty($this->hidden))
        {
            $this->deleteImage();
        }

        /**
         * no changes
         */
        if(empty($this->delete))
        {
            $this->setValue(!empty($this->hidden) ? $this->hidden : $this->options['emptyReturn']);
        }
    }

    /**
     * Deleting by hidden input
     */
    public function deleteImage()
    {
        $this->setValue($this->options['emptyReturn']);
        unlink($this->options['wwwDir'] . $this->hidden);
        $this->hidden = null;
    }

    /**
     * Returns HTML name of control.
     * @return string
     */
    public function getHtmlName()
    {
        return parent::getHtmlName() . ($this->control->multiple ? '[]' : '');
    }

    /**
     * Name hack for more than one form item
     * @param $name
     * @param $withoutContainers
     * @return mixed
     */
    public function getHTMLNameHack($name, $withoutContainers = false)
    {
        $parent = $this->getHTMLName();
        preg_match('~.*\K\[(.*)\]~s', $parent, $results);
        return $withoutContainers ? $results[1] . "_" . $name : str_replace($results[1], $results[1] . "_" . $name, $parent);
    }

    public function getActualContainer($name = "")
    {
        $parent = $this->getHTMLName();
        preg_match('~.*\K\[(.*)\]~s', $parent, $results);
        $r = str_replace($results[0], '', $parent);
        return $name ? $r . "[{$name}]" : $r;
    }

    /**
     * @param $value
     * @return self
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Sets control's default value.
     * @param $value
     * @return self
     */
    public function setDefaultValue($value)
    {
        $this->setValue($value);
        return $this;
    }

    /**
     * Has been any file uploaded?
     * @return bool
     */
    public function isFilled()
    {
        return $this->getValue() instanceof FileUpload ? $this->getValue()->isOk() : (bool) $this->getValue(); // ignore NULL object
    }


    /**
     * Generates control's HTML element.
     * @param  string
     * @return Html|string
     */
    public function getControl()
    {
        $this->setOption('rendered', TRUE);

        $original = strpos($this->getValue(), ":") === false ? '.' . $this->getValue() : $this->getValue();
        $svg      = strpos($this->getValue(), ".svg") !== false;

        $latte = new Engine();
        return $latte->renderToString($this->options['latte']['single'] ? $this->options['latte']['single'] : __DIR__ . '/templates/single.latte', [
            'name'      => $this->name,
            'nameDelete'=> $this->getActualContainer($this->options['deleteName']),
            'nameHidden'=> $this->getActualContainer($this->options['hiddenName']),
            'value'     => $this->getValue(),
            'original'  => $original,
            'type'      => $svg ? 'svg' : 'img',
            'thumb'     => $svg ? $this->drawSvg() : $this->drawThumb(),
            'config'    => $this->options,
        ]);
    }

    /**
     * @return string
     */
    public function drawThumb()
    {
        return ImageSlave::getThumbnail($this->getValue(), $this->options['width'], $this->options['height']);
    }

    /**
     * SVG preview
     * @return string
     */
    public function drawSvg()
    {
        $file = $this->options['wwwDir'] . $this->getValue();
        if(file_exists($file) && !is_dir($file))
        {
            return file_get_contents($file);
        }
        else
        {
            return "some error";
        }
    }

    /**
     * @param string $method
     * @param $config
     */
    public static function register($method = 'addImageSlave', $config)
    {
        Container::extensionMethod($method, function(Container $container, $name, $label) use ($config)
        {
            $container[$name] = new ImageSlaveControl($name, $label, $config);
            return $container[$name];
        });
    }

    /**
     * Todo: regular, think about call in onClick / onSuccess
     * @param $value
     * @param $id
     * @return mixed
     */
    public static function fixCreatePathWithId($value, $id)
    {
        $find  = "regular";
        $value = str_replace($find, $id, $value);

        // todo: move file
        return $value;
    }
}
