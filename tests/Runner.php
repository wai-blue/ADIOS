<?php

namespace ADIOS\Tests;

class Runner {

  public object $adios;

  public string $testName = "";
  public object $test;

  public function __construct($adios, $testName)
  {
    $this->adios = $adios;
    $this->testName = $testName;

    $testClass = "\\ADIOS\\Tests\\".str_replace("/", "\\", $testName);

    $this->test = new $testClass($adios);
  }

  public function run()
  {
    $this->test->run();
  }
}
