<?php
class File {

  static public function import($file, $path = null) {
    // define folder path
    if (!$path)
      $path = ROOT.DS.'lib'.DS; // lib
    else
      $path = ROOT.DS.$path.DS;

    // define file path
    if (!strpos($file, '.php')) // custom file name
      $filePath = $path . $file . '.class.php';
    else
      $filePath = $path . $file;

    // include
    return require $filePath;
  }

  static public function init($file, $args = array(), $className = null, $path = null) {
    // define class name
    if (!$className) {
      $exploded = explode(DS, $file);
      $className = end($exploded); // from path
    }
    if (strpos($className, '.class.php')) // remove .class.php
      $className = substr($className, 0, -10);
    if (strpos($className, '.php')) // remove .php
      $className = substr($className, 0, -4);

    self::import($file, $path); // import

    // init
    $class = new ReflectionClass($className);
    return $class->newInstanceArgs($args);
  }

}
