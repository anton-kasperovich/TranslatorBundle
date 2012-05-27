<?php

namespace Axioma\Bundle\TranslatorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AxiomaTranslatorController extends Controller
{
    public function addAction()
    {
        $request = $this->getRequest();

        if($request->isXmlHttpRequest()) {
            $axiomaTranslator = $this->container->get('axioma.listener.translator');

            if (null !== $axiomaTranslator) {

                if($request->request->get('check-only')) {
                    $key = $request->request->get('key');

                    $locales = $axiomaTranslator->collection;
                    foreach($locales as $locale => $data) {
                        if (isset($data['entries'][$key])) {
                            $res = array(
                                'result' => false,
                                'msg' => 'The key already exists. Please update it instead.',
                            );

                            return new \Symfony\Component\HttpFoundation\Response(json_encode($res));
                        }
                    }
                }
                else {
                    $axiomaTranslator->addTranslation($request->request->get('key'), $request->request->get('locale'));
                }

                $res = array(
                    'result' => true,
                );

                return new \Symfony\Component\HttpFoundation\Response(json_encode($res));
            }
        }

        throw new \Exception('Something went wrong!');
    }

    public function updateAction()
    {
        $request = $this->getRequest();

        if($request->isXmlHttpRequest()) {
            $axiomaTranslator = $this->container->get('axioma.listener.translator');

            if (null !== $axiomaTranslator) {
                $oldData = '';

                $key = (string) $request->request->get('key');
                $locale = (string) $request->request->get('locale');
                $value = (string) $request->request->get('val');

                if(isset($key) && isset($locale) && isset($value)) {
                    if(array_key_exists($locale, $axiomaTranslator->collection) &&
                        array_key_exists('entries', $axiomaTranslator->collection[$locale]) &&
                        array_key_exists($key, $axiomaTranslator->collection[$locale]['entries'])) {

                        $oldData = $axiomaTranslator->collection[$locale]['entries'][$key];
                    }

                    $axiomaTranslator->updateTranslation($key, $locale, $value);
                }

                $res = array(
                    'result' => true,
                    'oldata' => $oldData,
                );

                return new \Symfony\Component\HttpFoundation\Response(json_encode($res));
            }
        }

        throw new \Exception('Something went wrong!');
    }

    public function removeAction()
    {
        $request = $this->getRequest();

        if($request->isXmlHttpRequest()) {
            $axiomaTranslator = $this->container->get('axioma.listener.translator');

            if (null !== $axiomaTranslator) {
                $axiomaTranslator->removeTranslation((string) $request->request->get('key'));

                $res = array(
                    'result' => true,
                );
                return new \Symfony\Component\HttpFoundation\Response(json_encode($res));
            }
        }

        throw new \Exception('Something went wrong!');
    }

    public function listAction()
    {
        $axiomaTranslator = $this->container->get('axioma.listener.translator');

        if (null !== $axiomaTranslator) {
            $locales = $axiomaTranslator->collection;

            $data = array();
            $default = $this->container->getParameter('locale', 'en');
            $missing = array();

            foreach ($data as $d) {
                if (!isset($locales[$d['locale']])) {
                    $locales[$d['locale']] = array(
                        'entries' => array(),
                        'data'    => array()
                    );
                }
                if (is_array($d['entries'])) {
                    $locales[$d['locale']]['entries'] = array_merge($locales[$d['locale']]['entries'], $d['entries']);
                    $locales[$d['locale']]['data'][$d['filename']] = $d;
                }
            }

            $keys = array_keys($locales);

            foreach ($keys as $locale) {
                if ($locale != $default) {
                    foreach ($locales[$default]['entries'] as $key => $val) {
                        if (!isset($locales[$locale]['entries'][$key])) {
                            $missing[$key] = 1;
                        }
                    }
                }
            }

            return $this->render('AxiomaTranslatorBundle:Editor:list.html.twig', array(
                    'locales' => $locales,
                    'default' => $default,
                    'missing' => $missing,
                )
            );
        }

        throw new \Exception('Something went wrong!');
    }
}