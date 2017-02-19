<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class HomeController extends AppController {

  public function index() {
    $this->set('title', 'Service de sanctions');
    return $this->render('homepage.twig');
  }

  public function getStats() {
    $cacheFile = ROOT.DS.'tmp'.DS.'cache'.DS.'stats.json';
    if (file_exists($cacheFile) && strtotime('+5 hour', filemtime($cacheFile)) > time())
      return $this->response->withJson(['status' => true, 'data' => json_decode(file_get_contents($cacheFile), true)], 200);

    // configure api
    $api = File::init('API'. DS . 'ApiObsifight', array(Configuration::get('api')['username'], Configuration::get('api')['password']));

    // get counts
    $result = $api->get('/sanction/bans?limit=1&count=1');
    if (!$result->status) // error
      return $this->response->withJson(['status' => false, 'error' => $result->error], $result->code);
    $bansCount = $result->body['count'];

    $result = $api->get('/sanction/mutes?limit=1&count=1');
    if (!$result->status) // error
      return $this->response->withJson(['status' => false, 'error' => $result->error], $result->code);
    $mutesCount = $result->body['count'];

    // get stats this week
    $weeks = array(
      date('Y-m-d', strtotime('-6 days')),
      date('Y-m-d', strtotime('-5 days')),
      date('Y-m-d', strtotime('-4 days')),
      date('Y-m-d', strtotime('-3 days')),
      date('Y-m-d', strtotime('-2 days')),
      date('Y-m-d', strtotime('-1 days')),
      date('Y-m-d'),
    );
    $bansThisWeek = array();
    $mutesThisWeek = array();
    foreach ($weeks as $date) {

      $result = $api->get('/sanction/bans?limit=1&count=1&date=' . $date . '%');
      if (!$result->status) // error
        return $this->response->withJson(['status' => false, 'error' => $result->error], $result->code);
      $bansThisWeek[] = $result->body['count'];

      $result = $api->get('/sanction/mutes?limit=1&count=1&date=' . $date . '%');
      if (!$result->status) // error
        return $this->response->withJson(['status' => false, 'error' => $result->error], $result->code);
      $mutesThisWeek[] = $result->body['count'];

    }

    $data = [
      'counts' => [
        'bans' => $bansCount,
        'mutes' => $mutesCount
      ],
      'graph' => [
        'bans' => $bansThisWeek,
        'mutes' => $mutesThisWeek
      ]
    ];
    file_put_contents($cacheFile, json_encode($data));
    return $this->response->withJson(['status' => true, 'data' => $data], $result->code);
  }

}
