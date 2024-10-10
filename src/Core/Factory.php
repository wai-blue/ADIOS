<?php

namespace ADIOS\Core;

class Factory {
  // public string $class;
  // public string $classConverted;

  // public function __construct(string $class)
  // {
  //   $this->class = $class;
  //   $this->classConverted = $this->app->config['coreClasses'][$class] ?? ('\\ADIOS\\' . $class);
  // }

  // public function exists(): bool
  // {
  //   return class_exists($this->classConverted);
  // }

  public static function create(string $class, array $args = [])
  {
    $app = \ADIOS\Core\Helper::getGlobalApp();
    $classBackslash = str_replace('/', '\\', $class);
    $coreClass = $app->config['coreClasses'][$class] ?? ($app->config['coreClasses'][$classBackslash] ?? '');

    $classConverted = empty($coreClass) ? '\\ADIOS\\' . $classBackslash : $coreClass;

    return (new \ReflectionClass($classConverted))->newInstanceArgs($args);
  }
}