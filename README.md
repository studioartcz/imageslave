# ImageSlave
Nette plugin: Form extension for upload picture (JPG, PNG, SVG, ..) with thumbnail preview &amp; lightbox original. Front-end solution for thumbnails.

Example in form:

![Preview](images/preview.png)

## How install actual version

Add via composer to your project:

    $ composer require studioartcz/imageslave @dev

Add to extensions in your config.neon (fox advanced setup see [doc](doc/extension-setup.md))

    extensions:
        imageslave: App\Form\Control\ImageSlaveExtension
        
For lightbox preview download [client-side assets](client-side/) via bower:

    $ cd {fill-your-path}/vendors/studioartcz/imageslave/
    $ bower install

Add rules to Grunt - [example](docs/grunt-sample.md) and add your copied JS with files from [client-side](client-side/) folder.
        
## Using in Forms

Create form:
    
    public function create()
    {
        $form = new Form();
        $form->addImageSlave("picture", "Pretty picture");
        $form->onSuccess[] = array($this, "processForm");
        return $form;
    }
    
Working with data:

    public function processForm(Form $form, $values)
    {
        var_dump($values->picture);
    }

