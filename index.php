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
    return $app->redirect('/login');
});

$app->get('/login', function(Request $request) use ($app) {
    return $app['twig']->render('login.twig.html', array(
        'error'         => $app['security.last_error']($request),
        'last_username' => $app['session']->get('_security.last_username'),
    ));
});

$app->get('/public/blitz/project/{name}/{lang}', function(Request $request, $name, $lang) use($app) {
    $datafile = str_replace('{lang}', $lang, $app['projects'][$name]['datafile']);
    
    if (!file_exists($datafile)) {
        return $app->json(array('error' => 'File not found'));
    }

    $json = json_decode(file_get_contents($datafile));
    return $app->json($json);
});

$app->get('/blitz/project/{name}/{lang}/json', function(Request $request, $name, $lang) use($app) {
    $original = str_replace('{lang}', $lang, $app['projects'][$name]['datafile']);
    $draft = str_replace('.json', '[draft].json', $original);

    if (!file_exists($draft)) {
        if (!file_exists($original)) {
            return 'File not exists';
        } else {
            $app['filesystem']->copy($original, $draft);
        }
    }

    $data['datafile'] = $draft;

    $json = json_decode(file_get_contents($data['datafile']));
    return $app->json($json);
});

$app->get('/blitz/check_login');
$app->get('/blitz/logout');

$app->get('/blitz', function(Request $request) use ($app) {
    return $app['twig']->render('projects.twig.html', array('projects' => $app['projects']));
})->bind('projects');

$app->get('/blitz/project/{name}/{lang}', function(Request $request, $name, $lang) use($app) {
    if (!isset($app['projects'][$name])) {
        return $app['twig']->render('error.twig.html', array('message' => 'Project not found'));
    }

    $data['remote'] = '/blitz/project/'.$name.'/'.$lang.'/json';
    $data['site'] = $app['projects'][$name]['site'];
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