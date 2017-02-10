<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Slim\Exception\NotFoundException as NotFoundException;

class UserController extends AppController {

  public function login() {
    $data = $_POST;
    if (!isset($data['username']) || empty($data['username']) || !isset($data['password']) || empty($data['password']))
      return $this->response->withJson(['status' => false, 'error' => 'Missing username or password.'], 400);

    // configure api
    $api = File::init('API'. DS . 'ApiObsifight', array(Configuration::get('api')['username'], Configuration::get('api')['password']));

    // check if ranked
    $staff = @json_decode(@file_get_contents('http://api.obsifight.net/users/staff/premium'), true);
    if (!$staff)
      return $this->response->withJson(['status' => false, 'error' => 'Internal error when get staff.'], 500);
    if (!in_array($data['username'], $staff['data']['Modérateurs']) && !in_array($data['username'], $staff['data']['Administrateurs']))
      return $this->response->withJson(['status' => false, 'error' => 'Not ranked.'], 401);

    // try to connect
    $result = $api->get('/user/authenticate', 'POST', ['username' => $data['username'], 'password' => $data['password']]);
    if (!$result->status) // error
      return $this->response->withJson(['status' => false, 'error' => $result->error], $result->code);
    $userId = $result->body['user']['id'];

    // connect
    $this->session->set('user', ['id' => $userId, 'username' => $data['username']]);

    // return true
    return $this->response->withJson(['status' => true, 'success' => 'You have been successfuly logged!'], 200);
  }

}
