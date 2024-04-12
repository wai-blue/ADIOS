<?php

/**
  * @package Basic Helper Functions
*/

/**
 * Shorthand for htmlspecialchars with ENT_QUOTES.
 */
if (!function_exists('hsc')) {
  function hsc($string) {
    return htmlspecialchars((string) $string, ENT_QUOTES);
  }
}

/**
 * Shorthand for addslashes
 */
if (!function_exists('ads')) {
  function ads($string) {
    return addslashes((string) $string);
  }
}



function _var_dump($var, $only_return = false) {
  ob_start();
  $dump = var_dump($var);
  $dump = ob_get_clean();
  $str = "<pre style='font-size:11px;color:orange;position:relative;background:white;z-index:10000'>{$dump}</pre>";

  if ($only_return) {
    return $str;
  } else {
    echo $str;
  }
}

function _getmicrotime() {
  list($usec, $sec) = explode(' ', microtime());
  return (float) $usec + (float) $sec;
}

if (!function_exists('_count')) {
  function _count($data) {
    return is_array($data) && count($data) > 0 ? 1 : 0;
  }
}
