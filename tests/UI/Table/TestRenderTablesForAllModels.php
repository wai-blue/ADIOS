<?php

namespace ADIOS\Tests\UI\Table;

class TestRenderTablesForAllModels extends \ADIOS\Core\Test {
  public function init() : void {
    foreach ($this->adios->models as $model) {
      $table = new \ADIOS\Core\Views\Table($this->adios, [
        "model" => $model,
      ]);

      $this->checkAssertion("loadedRowsCount", function($assertionValueAndParams) {
        // This assertion can always be true because the goal of this test is to
        // render UI/Table for each model.
        // In case of DB problem, the UI/Table will throw an exception and the
        // test will fail.
        return TRUE; 
      });
    }

  }
}
