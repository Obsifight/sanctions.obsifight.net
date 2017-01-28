<?php
use Illuminate\Database\Capsule\Manager as Capsule;

class MigrationsHandler {

  static private $migrations = array();

  static private function __listAllMigrations() {
    foreach (glob(ROOT . DS . 'app' . DS. 'Model' . DS . 'Migrations' . DS . '*Table.php') as $file) {
      // get filename
      $filename = strtolower(basename($file, 'Table.php'));
      // add to list
      self::$migrations[$filename] = require $file;
    }
    return self::$migrations;
  }

  static public function run() {
    $migrations = self::__listAllMigrations();
    foreach ($migrations as $model => $callback) {
      if (!Capsule::schema()->hasTable($model)) // if not exists
        Capsule::schema()->create($model, $callback); // create
    }
  }

}
