<?php

namespace EC;

use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Symfony\Component\Translation\Loader\YamlFileLoader;

// use EC\Stats;
// use EC\Application;

$app = new Application();
$app->register(new UrlGeneratorServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(
    new SessionServiceProvider(),
    array(
        'session.storage.save_path' => __DIR__.'/../cache/session'
    )
);

$app->register(
    new TwigServiceProvider(),
    array(
        'twig.path'    => array(__DIR__.'/../templates'),
    //   'twig.options' => array('cache' => __DIR__.'/../cache/twig'),
    )
);

$app['twig'] = $app->share(
    $app->extend(
        'twig',
        function ($twig, $app) {
            return $twig;
        }
    )
);

$app['stats'] = $app->share(
    function () {
        return new Stats();
    }
);

// Checks if the mode is set to central, if yes then you must be logged in
$app['central.noaccess'] = $app->share(
    function () use ($app) {
        if ($app['centralmode'] && !$app['security']->isGranted('IS_AUTHENTICATED_FULLY')) {
            return true;
        }
        return false;
    }
);

$app->register(
    new TranslationServiceProvider(),
    array(
        'locale_fallback' => 'en',
    )
);

$app['translator'] = $app->share(
    $app->extend(
        'translator',
        function ($translator, $app) {
            $translator->addLoader('yaml', new YamlFileLoader());

            $translator->addResource('yaml', __DIR__.'/locales/en.yml', 'en');
            $translator->addResource('yaml', __DIR__.'/locales/nl.yml', 'nl');

            return $translator;
        }
    )
);

return $app;
