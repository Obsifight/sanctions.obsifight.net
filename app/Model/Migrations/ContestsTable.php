<?php
return function ($table) {
  $table->increments('id');
  $table->integer('sanction_id');
  $table->string('sanction_type', 5); // BAN or KICK
  $table->integer('user_id'); // website user's id
  $table->string('status', 10)->default('PENDING'); // PENDING / CLOSED
  $table->text('reason');
  $table->timestamps(); // created_at & updated_at
};
