<?php

namespace Axioma\Bundle\TranslatorBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Symfony\Component\Finder\Finder;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\DumpException;

class AxiomaTranslatorRequestListener
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Yaml parser instance
     */
    protected $yaml;

    /**
     * @var Finder instance
     */
    protected $finder;

    /**
     * @var Src directory path
     */
    protected $rootDir;

    /**
     * @array Locales collection
     */
    public $collection = array();

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->yaml = new \Symfony\Component\Yaml\Parser();
        $this->finder = new \Symfony\Component\Finder\Finder();
        $this->rootDir = $this->container->getParameter('kernel.root_dir').'/../src';
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernel::MASTER_REQUEST != $event->getRequestType()) {
            return;
        }

        $translationFiles = $this->getTranslationsFiles();
        if(is_array($translationFiles) && count($translationFiles)) {
            $this->collection = $this->getParsedTranslationsFiles($translationFiles);
        }
    }

    public function addTranslation($key, array $locales)
    {
        if($key && count($locales)) {
            foreach($locales as $locale => $value) {
                if(array_key_exists($locale, $this->collection)) {
                    $this->collection[$locale]['entries'][$key] = $value;

                    //@TODO: Add better checker for global messages.yml file is first, for update
                    if(count($this->collection[$locale]['data']) > 1) {
                        $localesFilePath = array_keys($this->collection[$locale]['data']);

                        $filePath = '';
                        foreach($localesFilePath as $localeFilePath) {
                            $filePath = $localeFilePath;

                            if ( preg_match( '/messages/', $localeFilePath ) ) {
                                break;
                            }
                        }

                        $this->collection[$locale]['data'][$filePath]['entries'][$key] = $value;

                        $this->updateLocaleDataTranslations($this->collection[$locale]['data'][$filePath]);
                    }
                    else {
                        foreach($this->collection[$locale]['data'] as $filePath => $data) {
                            $this->collection[$locale]['data'][$filePath]['entries'][$key] = $value;

                            $this->updateLocaleDataTranslations($this->collection[$locale]['data'][$filePath]);
                        }
                    }
                }
            }
        }
    }

    public function updateTranslation($key, $locale, $value)
    {
        if(isset($key) && isset($locale) && isset($value)) {
            if(array_key_exists($locale, $this->collection)) {
                $this->collection[$locale]['entries'][$key] = $value;

                foreach($this->collection[$locale]['data'] as $filePath => $data) {
                    $this->collection[$locale]['data'][$filePath]['entries'][$key] = $value;

                    $this->updateLocaleDataTranslations($this->collection[$locale]['data'][$filePath]);
                }
            }
        }
    }

    public function removeTranslation($key)
    {
        if(isset($key)) {
            foreach($this->collection as $locale => $collection) {
                if(array_key_exists($key, $this->collection[$locale]['entries'])) {

                    unset($this->collection[$locale]['entries'][$key]);

                    foreach($collection['data'] as $filePath => $data) {
                        if(array_key_exists($key, $data['entries'])) {

                            unset(
                                $data['entries'][$key],
                                $this->collection[$locale]['data'][$filePath]['entries'][$key]
                            );

                            $this->updateLocaleDataTranslations($data);
                        }
                    }
                }
            }
        }
    }

    private function updateLocaleDataTranslations(array $translationsData)
    {
        if(array_key_exists('filename', $translationsData) &&
            array_key_exists('entries', $translationsData) &&
            count($translationsData['entries'])) {

            try {
                $dumper = new Dumper();
                $yamlDumper = $dumper->dump($translationsData['entries'], 1);

                file_put_contents($translationsData['filename'], $yamlDumper);
            }
            catch (DumpException $e) {
                throw $e;
            }
        }
    }

    private function getParsedTranslationsFiles(array $files)
    {
        $locales = array();

        foreach($files as $filePath) {
            try {
                list($name, $locale, $type) = explode('.', basename($filePath));

                if($type == 'yml') {
                    $values = $this->yaml->parse(file_get_contents($filePath));

                    $fileData = array(
                        $filePath => array(
                            'filename' => $filePath,
                            'locale' => $locale,
                            'type' => $type,
                            'entries' => $values
                        )
                    );

                    if(array_key_exists($locale, $locales)) {
                        $locales[$locale]['entries'] = array_merge($values, $locales[$locale]['entries']);
                        $locales[$locale]['data'] = array_merge($fileData, $locales[$locale]['data']);
                    }
                    else {
                        $locales[$locale] = array(
                            'entries' => $values,
                            'data' => $fileData,
                        );
                    }
                }

            }
            catch (ParseException $e) {
                throw $e;
            }
        }

        return $locales;
    }

    private function getTranslationsFiles()
    {
        $this->finder->files()->in($this->rootDir)->exclude('config')->name('*.yml');

        $this->finder->sortByName();

        if(iterator_count($this->finder)) {
            foreach ($this->finder as $file) {
                $files[] = $file->getRealpath();
            }

            return $files;
        }

        return FALSE;
    }
}