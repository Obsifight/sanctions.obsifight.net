<?php
class AppController {

  public function __construct($app) {
    $this->app = $app;
    $this->container = $this->app->getContainer();
    $this->view = $this->container->view;
    $this->db = $this->container->get('db');
  }

}
