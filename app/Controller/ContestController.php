<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Slim\Exception\NotFoundException as NotFoundException;

class ContestController extends AppController {

  public function index() {
    $this->set('title', 'Contester une sanction');
    return $this->render('Contest/homepage.twig');
  }

  public function search() {
    $query = $_POST;
    if (!isset($query['user']) || empty($query['user']))
      return $this->response->withJson(['status' => false, 'error' => 'Method not implemented yet.'], 501);

    // get user's sanctions
    $api = File::init('API'. DS . 'ApiObsifight', array(Configuration::get('api')['username'], Configuration::get('api')['password']));
    $result = $api->get("/user/{$query['user']}/sanctions?limit=3");
    if (!$result->status) { // error
      return $this->response->withJson(['status' => false, 'error' => $result->error], $result->code);
    }

    if ($result->body['bans'][0]['state'] || $result->body['mutes'][0]['state']) {
      $type = ($result->body['bans'][0]['state']) ? 'bans' : 'mutes';
      // check is not already in db
      $this->loadModel('Contest');
      $find = Contest::where('sanction_id', $result->body[$type][0]['id'])
                      ->where('sanction_type', substr($type, 0, -1))
                      ->where(function ($query) {
                        $query->where('status', 'PENDING')
                              ->orWhere(function ($q) {
                                $q->where('status', 'CLOSED')
                                      ->whereRaw('updated_at >= DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)'); // interval 1 month before re-contest
                              });
                      })
                      ->first();
      if (!empty($find)) // already contested
        return $this->response->withJson(['status' => false, 'error' => 'Contest already saved.'], 403);
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
    $api = File::init('API'. DS . 'ApiObsifight', array(Configuration::get('api')['username'], Configuration::get('api')['password']));

    // check user credentials
    $result = $api->get('/user/authenticate', 'POST', array(
      'username' => $query['user'],
      'password' => $query['password']
    ));
    // http errors && invalid credentials
    if (!$result->status) {
      return $this->response->withJson(['status' => false, 'error' => $result->error], $result->code);
    }
    $user = $result->body['user'];
    // check sanction
    $result = $api->get("/sanction/{$query['sanction']['type']}s/{$query['sanction']['id']}");
    if (!$result->status) { // error
      return $this->response->withJson(['status' => false, 'error' => $result->error], $result->code);
    }
    $sanction = $result->body[$query['sanction']['type']];
    // check if active
    if (!$sanction['state'])
      return $this->response->withJson(['status' => false, 'error' => 'Sanction ended.'], 404);
    // Check if not already in database
    $this->loadModel('Contest');
    $find = Contest::where('sanction_id', $query['sanction']['id'])
                    ->where('sanction_type', $query['sanction']['type'])
                    ->where(function ($query) {
                      $query->where('status', 'PENDING')
                            ->orWhere(function ($q) {
                              $q->where('status', 'CLOSED')
                                    ->whereRaw('updated_at >= DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)'); // interval 1 month before re-contest
                            });
                    })
                    ->first();
    if (!empty($find)) // already contested
      return $this->response->withJson(['status' => false, 'error' => 'Contest already saved.'], 423);
    // create entry in db
    $contest = new Contest();
    $contest->sanction_id = $sanction['id'];
    $contest->sanction_type = $query['sanction']['type'];
    $contest->user_id = $user['id'];
    $contest->status = 'PENDING';
    $contest->reason = $query['explain'];
    $save = $contest->save();

    if ($save)
      return $this->response->withJson(['status' => true, 'success' => 'Contest added.', 'data' => ['contest' => ['id' => $contest->id]]], 200);
    else
      return $this->response->withJson(['status' => false, 'success' => 'Error when save contest.'], 500);
  }

  public function view($id) {
    // find
    $this->loadModel('Contest');
    $findContest = Contest::where('id', $id)->first();
    if (empty($findContest)) // not found
      throw new NotFoundException($this->request, $this->response);

    // init api
    $api = File::init('API'. DS . 'ApiObsifight', array(Configuration::get('api')['username'], Configuration::get('api')['password']));
    // find sanction
    $findSanction = $api->get("/sanction/{$findContest->sanction_type}s/{$findContest->sanction_id}");
    if (!$findSanction->status) // error
      throw new NotFoundException($this->request, $this->response); // sanction not found
    $sanction = $findSanction->body;
    // get comments
    $this->loadModel('ContestsComment');
    $comments = ContestsComment::where('contest_id', $findContest->id)->get();
    // get history
    $this->loadModel('ContestsHistory');
    $histories = ContestsHistory::where('contest_id', $findContest->id)->get();
    // find users infos + actions array
    $actions = array();
    $users = array($findContest->user_id);
    foreach ($comments as $comment) {
      array_push($users, $comment->user_id);
      array_push($actions, ['type' => 'comment', 'data' => $comment]);
    }
    foreach ($histories as $history) {
      array_push($users, $history->user_id);
      array_push($actions, ['type' => 'history', 'data' => $history]);
    }
    $findUser = $api->get('/user/infos/username', 'POST', ['ids' => $users]);
    if (!$findUser->status) // error
      throw new NotFoundException($this->request, $this->response); // users not found
    $users = $findUser->body['users'];

    // order actions
    usort($actions, function($a, $b) {
      return strtotime($a['data']->created_at) - strtotime($b['data']->created_at);
    });

    // render
    $this->set('actions', $actions);
    $this->set('contest', $findContest);
    $this->set('sanction', $sanction[$findContest->sanction_type]);
    $this->set('usersByIDs', $users);
    $this->set('title', "Contestation de {$users[$findContest->user_id]}");
    return $this->render('Contest/view.twig');
  }

  public function close($id) {
    if (!$this->getCurrentUser())
      return $this->response->withJson(['status' => false, 'error' => 'Not logged.'], 403);
    // find
    $this->loadModel('Contest');
    $findContest = Contest::where('id', $id)->first();
    if (empty($findContest)) // not found
      throw new NotFoundException($this->request, $this->response);
    // close
    $contest = Contest::find($id);
    $contest->status = 'CLOSED';
    $contest->save();
    // set into history
    $this->loadModel('ContestsHistory');
    $history = new ContestsHistory();
    $history->contest_id = $id;
    $history->action = 'CLOSE';
    $history->user_id = $this->getCurrentUser()['id'];
    $history->save();
    // send response
    return $this->response->withJson(['status' => true, 'success' => 'Contest closed.'], 200);
  }

  public function edit($id) {
    if (!$this->getCurrentUser())
      return $this->response->withJson(['status' => false, 'error' => 'Not logged.'], 403);
    // find
    $this->loadModel('Contest');
    $findContest = Contest::where('id', $id)->first();
    if (empty($findContest)) // not found
      throw new NotFoundException($this->request, $this->response);
    // check request
    $data = $this->request->getParsedBody();
    if (!isset($data['type']) || empty($data['type']) || !in_array($data['type'], ['REDUCE', 'UNBAN']))
      return $this->response->withJson(['status' => true, 'success' => 'Missing or invalid type.'], 400);
    if ($data['type'] == 'REDUCE' && (!isset($data['end_date']) || empty($data['end_date']) || date('Y-m-d H:i:s', strtotime($data['end_date'])) != $data['end_date']))
      return $this->response->withJson(['status' => true, 'success' => 'Missing or invalid duration.'], 400);

    // init api
    $api = File::init('API'. DS . 'ApiObsifight', array(Configuration::get('api')['username'], Configuration::get('api')['password']));
    // action
    if ($data['type'] == 'REDUCE') {
      $findSanction = $api->get("/sanction/{$findContest->sanction_type}s/{$findContest->sanction_id}", 'PUT', ['end_date' => date('Y-m-d H:i:s', strtotime($data['end_date']))]);
      if (!$findSanction->status) // error
        return $this->response->withJson(['status' => true, 'success' => 'Error when reduce with API.'], 500);
    } else if ($data['type'] == 'UNBAN') {
      $findSanction = $api->get("/sanction/{$findContest->sanction_type}s/{$findContest->sanction_id}", 'PUT', ['remove_reason' => 'Contestation acceptée']);
      if (!$findSanction->status) // error
        return $this->response->withJson(['status' => true, 'success' => 'Error when reduce with API.'], 500);
    }

    // set into history
    $this->loadModel('ContestsHistory');
    $history = new ContestsHistory();
    $history->contest_id = $id;
    $history->action = strtoupper($data['type']);
    $history->user_id = $this->getCurrentUser()['id'];
    $history->save();

    // close
    $contest = Contest::find($id);
    $contest->status = 'CLOSED';
    $contest->save();
    // set into history
    $this->loadModel('ContestsHistory');
    $history = new ContestsHistory();
    $history->contest_id = $id;
    $history->action = 'CLOSE';
    $history->user_id = $this->getCurrentUser()['id'];
    $history->save();
    // send response
    return $this->response->withJson(['status' => true, 'success' => 'Contest edited.'], 200);
  }

  public function addComment($id) {
    // find
    $this->loadModel('Contest');
    $findContest = Contest::where('id', $id)->first();
    if (empty($findContest)) // not found
      throw new NotFoundException($this->request, $this->response);
    // check request
    $data = $_POST;
    if (!isset($data['content']) || empty($data['content']))
      return $this->response->withJson(['status' => true, 'success' => 'Missing content.'], 400);
    if (!$this->getCurrentUser() && (!isset($data['password']) || empty($data['password'])))
      return $this->response->withJson(['status' => true, 'success' => 'Missing password.'], 400);
    // check credentials if not logged
    if (!$this->getCurrentUser()) {
      // configure api
      $api = File::init('API'. DS . 'ApiObsifight', array(Configuration::get('api')['username'], Configuration::get('api')['password']));
      // get username
      $findUser = $api->get('/user/infos/username', 'POST', ['ids' => [$findContest->user_id]]);
      if (!$findUser->status) // error
        throw new NotFoundException($this->request, $this->response); // users not found
      $username = $findUser->body['users'][$findContest->user_id];
      // check credentials
      $result = $api->get('/user/authenticate', 'POST', ['username' => $username, 'password' => $data['password']]);
      if (!$result->status) // error
        return $this->response->withJson(['status' => false, 'error' => $result->error], $result->code);
      $userId = $result->body['user']['id'];
    } else {
      $userId = $this->getCurrentUser()['id'];
    }
    // add command
    $this->loadModel('ContestsComment');
    $comment = new ContestsComment();
    $comment->contest_id = $id;
    $comment->content = $data['content'];
    $comment->user_id = $userId;
    $comment->save();
    // send response
    return $this->response->withJson(['status' => true, 'success' => 'Commented.'], 200);
  }

  public function listContests() {
    if (!$this->getCurrentUser())
      return $this->response->withStatus(403)->withHeader('Location', '/contest');
    // find contests
    $this->loadModel('Contest');
    $pendingContests = Contest::where('status', 'PENDING')->get();
    $closedContests = Contest::where('status', 'CLOSED')->orderBy('updated_at', 'DESC')->limit(10)->get();
    // users names
    $users = array();
    foreach ($pendingContests as $contest) {
      array_push($users, $contest->user_id);
    }
    foreach ($closedContests as $contest) {
      array_push($users, $contest->user_id);
    }
    // configure api
    $api = File::init('API'. DS . 'ApiObsifight', array(Configuration::get('api')['username'], Configuration::get('api')['password']));
    $findUser = $api->get('/user/infos/username', 'POST', ['ids' => $users]);
    if (!$findUser->status) // error
      throw new NotFoundException($this->request, $this->response); // users not found
    $users = $findUser->body['users'];
    // render
    $this->set('usersByIDs', $users);
    $this->set('pendingContests', $pendingContests);
    $this->set('closedContests', $closedContests);
    $this->set('title', 'Liste des contestations');
    return $this->render('Contest/list.twig');
  }

}
