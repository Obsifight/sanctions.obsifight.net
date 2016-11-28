<?php
// ===
// Init vars
// ===

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));

require ROOT.DS.'lib'.DS.'Utils'.DS.'functions.php';

// ====
// Init app
// ====
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';

$app = new \Slim\App;

// ===
// Init route
// ===
$routes = require ROOT.DS.'app'.DS.'Config'.DS.'routes.php';
$controllerPath = ROOT.DS.'app'.DS.'Controller'.DS;
foreach ($routes as $route => $controller) {
  // get method + route or setup default method
  if (count(explode(' ', $route)) === 2) {
    $method = strtolower(explode(' ', $route)[0]);
    $route = explode(' ', $route)[1];
  } else {
    $method = 'get';
  }

  // init route on app
  $app->{$method}($route, function (Request $request, Response $response) {
    global $controller;
    global $controllerPath;
    // init vars
    list($controller, $action) = explode('.', $controller);
    // include + init controller class
    require $controllerPath . $controller . '.php';
    $controllerClass = new $controller();
    return $controllerClass->{$action}($request, $response);
  });
}

// ===
// Run app
// ===
$app->run();
