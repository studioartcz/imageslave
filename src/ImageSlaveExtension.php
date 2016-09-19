<?php

namespace App\Form\Control;

use Nette;
use Nette\PhpGenerator as Code;

/**
 * Description of Imager
 *
 * @author Michal Landsman <landsman@studioart.cz>
 */
class ImageSlaveExtension extends Nette\DI\CompilerExtension
{
    private $defaults =  [
        'width'         => 225,
        'height'        => 150,
        'wwwDir'        => '%wwwDir%',
        'path'          => 'img/',
        'useTimestamp'  => true,
        'allowDelete'   => true,
        'emptyReturn'   => '',
        'hiddenName'    => 'imageHidden',
        'deleteName'    => 'imageDelete',
        'valueFormat'   => 'string',
        'wrapperClass'  => 'well iUploader',
        'lightboxClass' => 'lightbox',
        'thumbClass'    => 'img-thumbnail',
        'deleteLabelClass'      => 'labelDelete',
        'deleteLabelStyle'      => 'margin-right: 5px',
        'deleteCheckboxClass'   => 'styled',
        'uploadClass'           => 'form-control',
        'lang' => [
            'delete' => 'Smazat obrázek',
            'zoom'   => 'Klikněte pro originál'
        ],
        'latte' => [
            'single' => null,
        ]
    ];

    public function afterCompile(Code\ClassType $class)
    {
        parent::afterCompile($class);

        $init = $class->methods['initialize'];
        $config = $this->getConfig($this->defaults);
        $init->addBody('\App\Form\Control\ImageSlaveControl::register(?, ?);', ['addImageSlave', $config]);
    }

}