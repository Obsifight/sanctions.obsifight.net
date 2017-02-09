<?php
return array(
  'GET /contest' => 'ContestController.index',
  'POST /contest/search' => 'ContestController.search',
  'POST /contest' => 'ContestController.add',
  'GET /contest/{id}' => 'ContestController.view',
  'DELETE /contest/{id}' => 'ContestController.close',
  'PUT /contest/{id}' => 'ContestController.edit',
  'POST /contest/{id}/comment' => 'ContestController.addComment',

  'POST /user/login' => 'UserController.login',
);
