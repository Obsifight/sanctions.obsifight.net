<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class HomeController extends AppController {

  public function index() {
    $this->set('title', 'Service de sanctions');
    return $this->render('homepage.twig');
  }

}
