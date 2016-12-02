<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class ContestController extends AppController {

  public function index() {
    $this->set('title', 'Liste des contestations');
    return $this->render('homepage.twig');
  }

  public function search() {
    $query = $_POST;
    if (!isset($query['user']) || empty($query['user']))
      return $this->response->withJson(['status' => false, 'error' => 'Method not implemented yet.'], 501);

    // get user's sanctions
    require ROOT.DS.'lib'.DS.'API'.DS.'ApiObsifightClass.php';
    $api = new ApiObsifight(Configuration::get('api')['username'], Configuration::get('api')['password']);
    $result = $api->get("/user/{$query['user']}/sanctions?limit=3");
    if (!$result->status) { // error
      return $this->response->withJson(['status' => false, 'error' => $result->error], $result->code);
    }

    // check if last ban is active
    if ($result->body['bans'][0]['state'])
      return $this->response->withJson(['status' => true, 'data' => ['type' => 'ban', 'data' => $result->body['bans'][0]]]);
    // check if last mute is active
    if ($result->body['mutes'][0]['state'])
      return $this->response->withJson(['status' => true, 'data' => ['type' => 'mute', 'data' => $result->body['mutes'][0]]]);

    // no bans or mutes
    return $this->response->withJson(['status' => false, 'error' => 'No sanctions active found.'], 200);
  }

}
