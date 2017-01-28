<?php
return function ($table) {
  $table->increments('id');
  $table->integer('contest_id');
  $table->integer('user_id');
  $table->text('content');
  $table->timestamps();
};
