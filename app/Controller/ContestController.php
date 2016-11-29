<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class ContestController extends AppController {

  function index(Request $request, Response $response, array $args = array()) {
    require ROOT.DS.'lib'.DS.'API'.DS.'ApiObsifightClass.php';
    $api = new ApiObsifight('Eywek', '84pdmQpPGedGxYG');
    //debug($api->get('/user/Eywek'));
    //debug($api->get('/sanction/bans?limit=1'));
    return $this->view->render($response, 'homepage.twig', [
      'name' => 'yolo'
    ]);
  }

}
