<?php
return array(
  'GET /' => 'HomeController.index',
  'GET /stats' => 'HomeController.getStats',

  'GET /contest' => 'ContestController.index',
  'POST /contest/search' => 'ContestController.search',
  'POST /contest' => 'ContestController.add',
  'GET /contest/list' => 'ContestController.listContests',
  'GET /contest/list/public' => 'ContestController.listContestsPublic',
  'GET /contest/{id}' => 'ContestController.view',
  'DELETE /contest/{id}' => 'ContestController.close',
  'PUT /contest/{id}' => 'ContestController.edit',
  'POST /contest/{id}/comment' => 'ContestController.addComment',

  'POST /user/login' => 'UserController.login',
);
