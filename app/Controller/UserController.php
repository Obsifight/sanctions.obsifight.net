<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Slim\Exception\NotFoundException as NotFoundException;
use \Slim\Exception\BadRequestException as BadRequestException;

class UserController extends AppController {

  public function login() {
    $data = $_POST;
    if (!isset($data['username']) || empty($data['username']) || !isset($data['password']) || empty($data['password']))
      return $this->response->withJson(['status' => false, 'error' => 'Missing username or password.'], 400);

    // configure api
    $api = File::init('API'. DS . 'ApiObsifight', array(Configuration::get('api')['username'], Configuration::get('api')['password']));
    // try to connect
    $result = $api->get('/user/authenticate', 'POST', ['username' => $data['username'], 'password' => $data['password']]);
    if (!$result->status) // error
      return $this->response->withJson(['status' => false, 'error' => $result->error], $result->code);
    $userId = $result->body['user']['id'];

    // get username
    $result = $api->get('/user/infos/username', 'POST', ['ids' => [$userId]]);
    if (!$result->status) // error
      return $this->response->withJson(['status' => false, 'error' => $result->error], $result->code);
    $username = $result->body['users'][$userId];

    // connect
    $this->session->set('user', ['id' => $userId, 'username' => $username]);

    // return true
    return $this->response->withJson(['status' => true, 'success' => 'You have been successfuly logged!'], 200);
  }

}
