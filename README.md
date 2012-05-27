# TranslatorBundle

## Installation:

Download or clone the bundle. If you use deps file add it like this:

	[TranslatorBundle]
		git=git://github.com/iniweb/TranslatorBundle.git
		target=/bundles/Axioma/Bundle/TranslatorBundle

Then run ./bin/vendors install

Add Axioma namespace to app/autoload.php:

	$loader->registerNamespaces(array(
		...
		'Axioma' => __DIR__.'/../vendor/bundles',
		...
	));


Enable it in your app/AppKernel.php

	public function registerBundles()
	{
		...

        $bundles[] = new Axioma\Bundle\TranslatorBundle\AxiomaTranslatorBundle();

		...
	}


## Configuration:

Add the routing configuration to app/config/routing.yml

    AxiomaTranslatorBundle:
        resource: "@AxiomaTranslatorBundle/Resources/config/routing.yml"
        prefix:   /

## Usage:

Load editor in browser, edit your translations

	http://your-project.url/axioma/translator/list