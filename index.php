<?php

define('BASEPATH', __DIR__);

require_once BASEPATH.'/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Neutron\Silex\Provider\FilesystemServiceProvider;

// Setup

$app = new Silex\Application();
$app['debug'] = true;

require_once BASEPATH.'/web/modules/twig.php';

$app->register(new FilesystemServiceProvider());
$app->register(new Silex\Provider\SecurityServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app['security.firewalls'] = array(
    'admin' => array(
        'pattern' => '^/blitz',
        'form' => array(
            'login_path' => '/login',
            'check_path' => '/blitz/check_login',
            'default_target_path' => '/blitz'
            ),
        'logout' => array('logout_path' => '/blitz/logout'),
        'users' => array(
            'admin' => array('ROLE_ADMIN', 'u/1FwxH0nsC8gc/2RLJHBulfKYEJEPBkSfgu8dLbagYARG27pm8HT4fr8OBNLqTULvTJuPHIcIHBbg8X/MQ1fw=='),
        ),
    ),
);

// Loading the config

$app['projects'] = json_decode(file_get_contents('web/config/projects.json'), true);


// Defining the routes

$app->get('/', function() use($app) {
    #$password = $app['security.encoder.digest']->encodePassword('', '');
    return 'Please login';
});

$app->get('/login', function(Request $request) use ($app) {
    return $app['twig']->render('login.twig.html', array(
        'error'         => $app['security.last_error']($request),
        'last_username' => $app['session']->get('_security.last_username'),
    ));
});

$app->get('/public/blitz/project/{name}/{lang}.json', function(Request $request, $name, $lang) use($app) {
    $data['datafile'] = str_replace('{lang}', $lang, $app['projects'][$name]['datafile']);
    $data['datafile'] = str_replace('.json', '[draft].json', $data['datafile']);
    $json = json_decode(file_get_contents($data['datafile']));
    return $app->json($json);
});

$app->get('/blitz/check_login');
$app->get('/blitz/logout');

$app->get('/blitz', function(Request $request) use ($app) {
    return $app['twig']->render('projects.twig.html', array('projects' => $app['projects']));
});

$app->get('/blitz/project/{name}/{lang}', function(Request $request, $name, $lang) use($app) {
    if (!isset($app['projects'][$name])) {
        return $app['twig']->render('error.twig.html', array('message' => 'Project not found'));
    }

    $data['remote'] = '/public/blitz/project/'.$name.'/'.$lang.'.json';
    $data['datafile'] = str_replace('{lang}', $lang, $app['projects'][$name]['datafile']);

    return $app['twig']->render('view.twig.html', $data);
})->bind('project');

$app->post('/blitz/project/{name}/{lang}/{method}', function (Request $request, $name, $lang, $method) use($app) {
    $newJson = $request->get('data');

    $datafile = str_replace('{lang}', $lang, $app['projects'][$name]['datafile']);

    $originalJson = json_decode(file_get_contents($datafile), true);
    $result = array_replace_recursive($originalJson, $newJson);

    if ($method == 'save') {
        $datafile = str_replace('.json', '[draft].json', $datafile);
    }
    $app['filesystem']->dumpFile($datafile, json_encode($result, JSON_PRETTY_PRINT));
    
    return $app->json(array('success' => true, 'message' => 'Success'));
});

$app->run();