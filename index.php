<?php
// ===
// Init vars
// ===

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));

require ROOT.DS.'lib'.DS.'Utils'.DS.'Functions.php';
require ROOT.DS.'lib'.DS.'Utils'.DS.'Configuration.class.php';

// ===
// Configuration
// ===

require ROOT.DS.'app'.DS.'Config'.DS.'global.php';

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
// Get container
$container = $app->getContainer();

// Database
$dbConfig = require ROOT.DS.'app'.DS.'Config'.DS.'database.php';
if ($dbConfig['enable']) {
  Configuration::set('db', $dbConfig);

  // Init Eloquent
  $container['db'] = function ($container) {
    $capsule = new \Illuminate\Database\Capsule\Manager;
    $capsule->addConnection(Configuration::get('db'));

    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    return $capsule;
  };
}

// ===
// View
// ===

// Register component on container
$container['view'] = function ($container) {
  $view = new \Slim\Views\Twig(ROOT.DS.'app'.DS.'View', [
      'cache' => (!Configuration::get('debug') && Configuration::get('cache')) ? ROOT.DS.'tmp'.DS.'cache' : false
  ]);

  // Instantiate and add Slim specific extension
  $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
  $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));

  return $view;
};

// ===
// Init route
// ===
// paths
$routes = require ROOT.DS.'app'.DS.'Config'.DS.'routes.php';
$controllerPath = ROOT.DS.'app'.DS.'Controller'.DS;

// app controller
require $controllerPath . DS. 'AppController.php';

// each routes
foreach ($routes as $route => $controller) {
  // get method + route or setup default method
  if (count(explode(' ', $route)) === 2) {
    $method = strtolower(explode(' ', $route)[0]);
    $route = explode(' ', $route)[1];
  } else {
    $method = 'get';
  }

  // init route on app
  $app->{$method}($route, function (Request $request, Response $response, array $args = array()) {
    global $controller;
    global $controllerPath;
    global $app;
    // init vars
    list($controller, $action) = explode('.', $controller);
    // include + init controller class
    require $controllerPath . $controller . '.php';
    $controllerClass = new $controller($app);
    return $controllerClass->{$action}($request, $response, $args);
  });
}

// ===
// Run app
// ===
$app->run();
