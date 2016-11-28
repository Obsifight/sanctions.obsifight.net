<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class ContestController {

  function index(Request $request, Response $response) {
    require ROOT.DS.'lib'.DS.'API'.DS.'ApiObsifightClass.php';
    $api = new ApiObsifight('Eywek', '84pdmQpPGedGxYG');
    debug($api->get('/user/Eywek'));
    debug($api->get('/sanction/bans?limit=1'));
  }

}
