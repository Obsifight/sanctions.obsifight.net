<?php
return function ($table) {
  $table->increments('id');
  $table->integer('contest_id');
  $table->string('action', 6); // CLOSE or UNBAN or REDUCE
  $table->integer('user_id'); // website user's id
  $table->timestamps(); // created_at & updated_at
};
