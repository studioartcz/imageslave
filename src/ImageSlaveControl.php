<?php

namespace App\Form\Control;

use Latte\Engine;
use Nette;
use Nette\Http\FileUpload;
use Nette\Utils\Html;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use App\Components\ImageSlave;

/**
 * Description of Imager
 *
 * @author Michal Landsman <landsman@studioart.cz>
 */
class ImageSlaveControl extends Nette\Forms\Controls\BaseControl
{

    /**
     * @var
     */
    private $file;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $options;

    /**
     * ImageUploadControl constructor.
     * @param null $name
     * @param null $label
     * @param bool $multiple
     * @param $config
     */
    public function __construct($name = null, $label = NULL, $multiple = FALSE, $config)
    {
        parent::__construct($label);
        $this->control->type = 'file';
        $this->control->multiple = (bool) $multiple;
        $this->setOption('type', 'file');
        $this->name = $name;
        $this->options = $config;
    }


    /**
     * This method will be called when the component (or component's parent)
     * becomes attached to a monitored object. Do not call this method yourself.
     * @param  Nette\ComponentModel\IComponent
     * @return void
     */
    protected function attached($form)
    {
        if ($form instanceof Nette\Forms\Form) {
            if (!$form->isMethod('post')) {
                throw new Nette\InvalidStateException('File upload requires method POST.');
            }
            $form->getElementPrototype()->enctype = 'multipart/form-data';
        }
        parent::attached($form);
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
     * Loads HTTP data.
     * @return void
     */
    public function loadHttpData()
    {
        $this->file = $this->getHttpData(Form::DATA_FILE, '[file]');
        $delete = $this->getHttpData(Form::DATA_LINE, '[delete]');
        if ($this->file === NULL) {
            $this->file = new FileUpload(NULL);
        }

        dump($this->file);
        dump($delete);

        if ($delete === 'on')
        {
            $this->deleteImage();
            $this->value = $this->options['emptyReturn'];
        } else {
            // TODO: return value is not complete
            $this->value = [
                'path' => '',
                'folder' => '',
                'filename' => '',
            ];
        }

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
     * @return self
     * @internal
     */
    public function setValue($value)
    {
        return $this;
    }


    /**
     * Generates control's HTML element.
     * @param  string
     * @return Html|string
     */
    public function getControl()
    {
        $this->setOption('rendered', TRUE);
        $latte = new Engine;
        return $latte->renderToString(__DIR__.'/control.latte', [
            'name' => $this->name
        ]);
    }

    /**
     * Deleting by hidden input
     */
    public function deleteImage()
    {
        // TODO: delete is not complete
//        unlink($this->options['wwwDir'] . $this->hidden);
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
     * Have been all files succesfully uploaded?
     * @return bool
     */
    public function isOk()
    {
        return $this->value instanceof FileUpload
            ? $this->value->isOk()
            : $this->value && array_reduce($this->value, function ($carry, $fileUpload) {
                return $carry && $fileUpload->isOk();
            }, TRUE);
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
        Container::extensionMethod($method, function(
            Container $container,
            $name,
            $label = NULL,
            $multiple = NULL) use ($config)
        {
            $container[$name] = new ImageSlaveControl($name, $label, $multiple, $config);
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
