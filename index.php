<?php
// ===
// Init vars
// ===
date_default_timezone_set('Europe/Paris');

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));

require ROOT.DS.'lib'.DS.'Utils'.DS.'File.class.php';
File::import('Utils/Functions.php');
File::init('Utils/Configuration');

// ===
// Configuration
// ===

File::import('global.php', 'app' . DS. 'Config');

// ====
// Init app
// ====
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';

$app = new \Slim\App([
  'settings' => [
    'displayErrorDetails' => Configuration::get('debug'),

    'logger' => [
      'name' => 'sanctions-app',
      'level' => Monolog\Logger::DEBUG,
      'path' => ROOT.DS.'tmp'.DS.'logs'.DS.'errors.log',
    ],
  ],
]);

$app->add(new \Slim\Middleware\Session([
  'name' => 'obsifight_sanctions',
  'autorefresh' => true,
  'lifetime' => '5 hours'
]));
// Get container
$container = $app->getContainer();

// Database
$dbConfig = File::import('database.php', 'app' . DS . 'Config');
if ($dbConfig['enable']) {
  Configuration::set('db', $dbConfig);

  // Init Eloquent
  $container['db'] = function ($container) {
    $capsule = new \Illuminate\Database\Capsule\Manager;
    $capsule->addConnection(Configuration::get('db'));

    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    File::import('Handler.php', 'app' . DS . 'Model' .DS . 'Migrations');
    MigrationsHandler::run();

    return $capsule;
  };
}

// ===
// View
// ===

// Register component on container
$container['view'] = function ($container) {
  $view = new \Slim\Views\Twig(ROOT.DS.'app'.DS.'View', [
      'cache' => (!Configuration::get('debug') && Configuration::get('cache')) ? ROOT.DS.'tmp'.DS.'cache' : false,
      'debug' => Configuration::get('debug')
  ]);

  // Instantiate and add Slim specific extension
  $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
  $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));
  if (Configuration::get('debug'))
    $view->addExtension(new Twig_Extension_Debug());

  return $view;
};

// ===
// ERRORS
// ===

$container['notFoundHandler'] = function ($c) {
  return function ($request, $response) use ($c) {
    $session = new \SlimSession\Helper;
    return $c['view']->render($response->withStatus(404), 'Errors/404.twig', [
      'title' => 'Page introuvable',
      'router' => [
        'getBase' => $request->getUri()->getBaseUrl(),
        'getAssetsBase' => $request->getUri()->getBaseUrl() . '/public/assets'
      ],
      'user' => ($user = $session->get('user')) ? $user : false,
      'version' => trim(file_get_contents(ROOT.DS.'VERSION'))
    ]);
  };
};
$container['errorHandler'] = function ($c) {
  return function ($request, $response, $exception) use ($c) {
    $session = new \SlimSession\Helper;
    return $c['view']->render($response->withStatus(500), 'Errors/500.twig', [
      'title' => 'Erreur interne',
      'router' => [
        'getBase' => $request->getUri()->getBaseUrl(),
        'getAssetsBase' => $request->getUri()->getBaseUrl() . '/public/assets'
      ],
      'user' => ($user = $session->get('user')) ? $user : false,
      'version' => trim(file_get_contents(ROOT.DS.'VERSION'))
    ]);
  };
};

// ===
// Init route
// ===
// paths
$routes = File::import('routes.php', 'app' . DS. 'Config');

// vars
$routesList = [];

// app controller
File::import('AppController.php', 'app' . DS. 'Controller');

// each routes
foreach ($routes as $route => $controller) {
  // get method + route or setup default method
  if (count(explode(' ', $route)) === 2) {
    $method = strtolower(explode(' ', $route)[0]);
    $route = explode(' ', $route)[1];
  } else {
    $method = 'get';
  }

  $routesList[strtoupper($method).' '.$route] = $controller;

  // init route on app
  $app->{$method}($route, function (Request $request, Response $response, array $args = array()) use ($route) {
    global $controllerPath;
    global $app;
    global $routesList;

    // init vars
    $controller = $routesList[$request->getMethod().' '.$route];
    list($controller, $action) = explode('.', $controller);
    // include + init controller class
    $controllerClass = File::init($controller . '.php', array($app, $request, $response), null, 'app' . DS . 'Controller');
    return call_user_func_array(array($controllerClass, $action), $args);
  });
}

// ===
// Run app
// ===
$app->run();
