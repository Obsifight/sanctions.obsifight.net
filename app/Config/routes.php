<?php
return array(
  'GET /contest' => 'ContestController.index',
  'POST /contest/search' => 'ContestController.search',
  'POST /contest' => 'ContestController.add',
  'GET /contest/{id}' => 'ContestController.view',

  'POST /user/login' => 'UserController.login',
);
