<?php
function debug($var) {
  if (Configuration::get('debug')) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
  }
}
