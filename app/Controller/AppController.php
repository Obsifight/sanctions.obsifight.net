<?php
class AppController {

  private $viewVars = array();
  private $viewExt = '.twig';

  public function __construct($app, $request, $response) {
    // Setup vars
    $this->app = $app;
    $this->container = $this->app->getContainer();
    $this->view = $this->container->view;
    $this->db = $this->container->get('db');
    $this->request = $request;
    $this->response = $response;

    // Setup view vars
    $this->__setViewVars();
  }

  private function __setViewVars() {
    $this->set('request', $this->request);
    $this->set('response', $this->response);
    $this->set('router', array(
      'getBase' => $this->request->getUri()->getBaseUrl(),
      'getAssetsBase' => $this->request->getUri()->getBaseUrl() . '/public/assets'
    ));
    $this->set('version', trim(file_get_contents(ROOT.DS.'VERSION')));
  }

  protected function set($var, $value) {
    $this->viewVars[$var] = $value;
  }

  protected function render($customView = null) {
    if (!empty($customView))
      $view = $customView;
    else
      $view = $this->__getView($this->__getCaller()['class'], $this->__getCaller()['function']);

    return $this->view->render($this->response, $view, $this->viewVars);
  }

  private function __getCaller() {
    return debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2];
  }

  private function __getView($controller, $action) {
    $controller = str_replace('Controller', '', $controller);

    return $controller . DS . $action . $this->viewExt;
  }

  public function loadModel($modelName) {
    if (!isset($this->modelsLoaded))
      $this->modelsLoaded = (object) array();
    if (!isset($this->modelsLoaded->{$modelName})) {
      // load model class
      File::import($modelName.'.php', 'app'.DS.'Model');
      $this->modelsLoaded->{$modelName} = true;
    }
  }

}
