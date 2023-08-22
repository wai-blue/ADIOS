<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core;

class Test
{
  public ?\ADIOS\Core\Loader $adios = null;
  public array $assertions = [];
  public int $assertionCounter = 0;

  public string $sourceFile = "";

  public function __construct($adios)
  {
    $this->adios = $adios;
    $this->adios->test = $this;

    $this->sourceFile = (new \ReflectionClass(get_class($this)))->getFileName();

  }

  public function findAllTests(string $dir) : array
  {
    $allTests = \ADIOS\Core\HelperFunctions::scanDirRecursively($dir);

    // only .php files starting with "Test" are treated to be tests
    foreach ($allTests as $key => $value) {
      if (strpos(pathinfo($value, PATHINFO_FILENAME), "Test") !== 0) {
        unset($allTests[$key]);
      } else {
        $allTests[$key] = str_replace(".php", "", $value);
      }
    }

    return $allTests;
  }

  public function init() : void
  {
    // TO BE OVERRIDEN
    // Exceptions should be thrown in case of problem.
  }

  public function log($msg)
  {
    echo $msg."\n";
  }

  public function run()
  {
    try {
      $this->assertionCounter = 0;
      $this->init();

      $this->log("Test '".get_class($this)."' succeeded with {$this->assertionCounter} assertions checked.");
    } catch (\ADIOS\Core\Exceptions\TestAssertionFailed $e) {
      list($assertionName, $assertionValueAndParams, $expectedValue) = json_decode($e->getMessage(), TRUE);
      list($assertionValue, $assertionParams) = $assertionValueAndParams;

      $this->log(
        "Test '".get_class($this)."' failed at assertion '{$assertionName}'"
        .(count($assertionParams) == 0 ? "" : " with params ".json_encode($assertionParams))
        ."."
      );

      $this->log("Received value: ".json_encode($assertionValue));
      $this->log("Expected value: ".($expectedValue == "[CLOSURE]" ? "[CLOSURE]" : json_encode($expectedValue)));

      throw new \ADIOS\Core\Exceptions\TestAssertionFailed($e);
    } catch (\Exception $e) {
      $this->log("Test '".get_class($this)."' failed with exception '".get_class($e)."' and message '{$e->getMessage()}'");

      throw new \Exception($e);
    }
  }

  public function assert(string $name, $assertionValue, array $assertionParams = [])
  {
    $this->assertions[$name] = [ $assertionValue, $assertionParams ];
  }

  public function checkAssertion(string $name, $expectedValue)
  {
    $this->assertionCounter++;
    $ok = TRUE;

    if (!isset($this->assertions[$name])) {
      $this->log("Assertion '{$name}' is unknown.");

      $ok = FALSE;
    } else {

      list($assertionValue, $assertionParams) = $this->assertions[$name];

      $this->log(
        "Checking assertion '{$name}'"
        .(count($assertionParams) == 0 ? "" : " with params ".json_encode($assertionParams))
        ."."
      );

      if ($expectedValue instanceof \Closure) {
        $ok = call_user_func($expectedValue, $this->assertions[$name]);
      } else {
        $ok = $assertionValue === $expectedValue;
      }
    }

    if (!$ok) {
      throw new \ADIOS\Core\Exceptions\TestAssertionFailed(json_encode([
        $name,
        $this->assertions[$name] ?? NULL,
        ($expectedValue instanceof \Closure ? "[CLOSURE]" : $expectedValue)
      ]));
    } else {
      $this->log("Check succeeded with assertion value ".json_encode($assertionValue).".");
    }
  }

}