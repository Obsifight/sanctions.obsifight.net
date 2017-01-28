<?php
use Illuminate\Database\Capsule\Manager as Capsule;

class MigrationsHandler {

  static private $migrations = array();

  static private function __listAllMigrations() {
    foreach (glob(ROOT . DS . 'app' . DS. 'Model' . DS . 'Migrations' . DS . '*Table.php') as $file) {
      // get filename
      $filename = basename($file, 'Table.php');
      // add to list
      self::$migrations[$filename] = require $file;
    }
    return self::$migrations;
  }

  static public function run() {
    $migrations = self::__listAllMigrations();
    foreach ($migrations as $model => $callback) {
      $table = snake_case(str_plural($model));
      if (!Capsule::schema()->hasTable($table)) // if not exists
        Capsule::schema()->create($table, $callback); // create
    }
  }

}
