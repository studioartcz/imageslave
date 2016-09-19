# ImageSlave
Nette plugin: Form extension for upload picture (JPG, PNG, SVG, ..) with thumbnail preview &amp; lightbox original. Front-end solution for thumbnails.

Example in form:

[Preview](images/preview.png)

## How install actual version

Add via composer to your project:

    $ composer require studioartcz/imageslave @dev

Add to extensions in your config.neon:

    extensions:
        imageslave: App\Form\Control\ImageSlaveExtension
        
For lightbox preview download [client-side assets](client-side/) via bower:

    $ cd {fill-your-path}/vendors/studioartcz/imageslave/
    $ bower install

Add rules to Grunt - [example](docs/grunt-sample.md) and add your copied JS with files from [client-side](client-side/) folder.
        
## Using in Forms

[Read Forms doc](docs/forms.md).