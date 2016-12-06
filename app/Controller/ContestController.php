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

  public function add() {
    $query = $_POST;

    // check request
    if (!isset($query['user']) || empty($query['user']))
      return $this->response->withJson(['status' => false, 'error' => 'Missing `user` params.'], 400);
    if (!isset($query['password']) || empty($query['password']))
      return $this->response->withJson(['status' => false, 'error' => 'Missing `password` params.'], 400);
    if (!isset($query['sanction']) || empty($query['sanction']) || !isset($query['sanction']['id']) || empty($query['sanction']['id']) || !isset($query['sanction']['type']) || empty($query['sanction']['type']))
      return $this->response->withJson(['status' => false, 'error' => 'Missing `sanction` params.'], 400);
    if ($query['sanction']['id'] != intval($query['sanction']['id']))
      return $this->response->withJson(['status' => false, 'error' => 'Invalid `sanction.id` params.'], 400);
    if (!in_array($query['sanction']['type'], array('ban', 'mute')))
      return $this->response->withJson(['status' => false, 'error' => 'Invalid `sanction.type` params.'], 400);
    if (!isset($query['explain']) || empty($query['explain']))
      return $this->response->withJson(['status' => false, 'error' => 'Missing `explain` params.'], 400);

    // configure api
    require ROOT.DS.'lib'.DS.'API'.DS.'ApiObsifightClass.php';
    $api = new ApiObsifight(Configuration::get('api')['username'], Configuration::get('api')['password']);

    // check user credentials
    $result = $api->get('/user/authenticate', 'POST', array(
      'username' => $query['user'],
      'password' => $query['password']
    ));
    // http errors && invalid credentials
    if (!$result->status) {
      return $this->response->withJson(['status' => false, 'error' => $result->error], $result->code);
    }
    // check sanction
    $result = $api->get("/sanction/{$query['sanction']['type']}s/{$query['sanction']['id']}");
    if (!$result->status) { // error
      return $this->response->withJson(['status' => false, 'error' => $result->error], $result->code);
    }
    $sanction = $result->body[$query['sanction']['type']];
    // check if active
    if (!$sanction['state'])
      return $this->response->withJson(['status' => false, 'error' => 'Sanction ended.'], 404);

    // create entry in db

    // return success with contest id

  }

}
