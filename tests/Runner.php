<?php

namespace ADIOS\Tests;

class Runner {

  public \ADIOS\Core\Loader $app;

  public string $testName = "";
  public object $test;

  public function __construct(\ADIOS\Core\Loader$app, $testName)
  {
    $this->app = $app;
    $this->testName = $testName;

    $testClass = "\\ADIOS\\Tests\\".str_replace("/", "\\", $testName);

    $this->test = new $testClass($app);
  }

  public function run()
  {
    $this->test->run();
  }
}
