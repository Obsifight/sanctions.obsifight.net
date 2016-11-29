<?php
class Configuration {

  static private $variables = array();

  static public function get($var) {
    return (isset(self::$variables[$var])) ? self::$variables[$var] : false;
  }

  static public function set($var, $value) {
    self::$variables[$var] = $value;
  }

}
