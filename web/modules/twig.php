<?php

class TwigInitializer {

	public static $app;

	public static function init($app) {

		self::$app = $app;

		$app->register(new Silex\Provider\TwigServiceProvider(), array(
			'twig.path'       => BASEPATH.'/web/views',
			'twig.options' => array('debug' => true), #array('cache' => BASEPATH.'/web/cache')
			));

		$twig_funcs = array();

		// Add functions to Twig.
		foreach ($twig_funcs as $func) {
			$app['twig']->addFunction($func);
		}
	}

}

TwigInitializer::init($app);

