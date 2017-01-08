<?php
class ApiObsifight {

  private $username = '';
  private $password = '';
  private $token = '';

  private $endpoint = '';

  private $actions = array(
    'AUTH' => '/authenticate'
  );

  public function __construct($username, $password, $endpoint = 'http://api.obsifight.net') {
    $this->username = $username;
    $this->password = $password;

    $this->endpoint = $endpoint;
  }

  private function __connect() {
    // Init request
    list($body, $code, $error) = $this->__request($this->actions['AUTH'], 'POST', ['username' => $this->username, 'password' => $this->password], false);
    if ($error || $code !== 200 || !$body || !$body['status'])
      return false;
    // get token
    $this->token = $body['data']['token'];
  }

  private function __request($action, $method = 'GET', $body = [], $token = true) {
    // Init request
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $this->endpoint.$action);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    // headers
    $headers = array(
      'Content-Type: application/json'
    );
    if ($token)
      $headers[] = 'Authorization: '.$this->getToken();
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    if (!empty($body))
      curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
    // execute
    $result = @json_decode(curl_exec($curl), true);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_errno($curl);
    curl_close($curl);

    return [$result, $code, $error];
  }

  public function getToken() {
    if (!$this->token)
      $this->__connect();
    return $this->token;
  }

  public function get($route, $method = 'GET', $body = array()) {
    list($body, $code, $error) = $this->__request($route, $method, $body);
    return (object)[
      'status' => ($code === 200),
      'code' => $code,
      'success' => $body['status'],
      'error' => (isset($body['error'])) ? $body['error'] : '',
      'body' => (isset($body['data'])) ? $body['data'] : ''
    ];
  }

}
