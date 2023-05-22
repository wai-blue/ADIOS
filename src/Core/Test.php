<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core;

class Test {
  public $adios = NULL;
  public array $assertions = [];

  public string $sourceFile = "";

  public function __construct($adios) {
    $this->adios = $adios;
    $this->adios->test = $this;

    $this->sourceFile = (new \ReflectionClass(get_class($this)))->getFileName();

  }

  public function init() : void {
    // TO BE OVERRIDEN
    // Exceptions should be thrown in case of problem.
  }

  public function run() {
    try {
      $this->init();

      echo "Test ".get_class($this)." succeeded.\n";
    } catch (\ADIOS\Core\Exceptions\TestAssertionFailed $e) {
      list($assertionName, $assertionValue, $expectedValue) = json_decode($e->getMessage(), TRUE);
      echo "Test ".get_class($this)." failed at assertion '{$assertionName}'.\n";
      echo "Assertion value: ".json_encode($assertionValue, JSON_PRETTY_PRINT)."\n";
      echo "Expected value: ".json_encode($expectedValue, JSON_PRETTY_PRINT)."\n";
    } catch (\Exception $e) {
      echo "Test ".get_class($this)." failed with message: {$e->getMessage()}\n";
    }
  }

  public function assert(string $name, $value) {
    $this->assertions[$name] = $value;
  }

  public function checkAssertion(string $name, $expectedValue) {
    $ok = TRUE;

    if (!isset($this->assertions[$name])) {
      $ok = FALSE;
    } else {
      $ok = $this->assertions[$name] === $expectedValue;
    }

    if (!$ok) {
      throw new \ADIOS\Core\Exceptions\TestAssertionFailed(json_encode([
        $name,
        $this->assertions[$name] ?? NULL,
        $expectedValue
      ]));
    }
  }

}