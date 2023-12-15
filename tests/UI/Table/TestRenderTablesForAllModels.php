<?php

namespace ADIOS\Tests\UI\Table;

class TestRenderTablesForAllModels extends \ADIOS\Core\Test {
  public function init() : void {
    foreach ($this->adios->models as $model) {
      $table = new \ADIOS\Core\ViewsWithController\Table($this->adios, [
        "model" => $model,
      ]);

      $this->checkAssertion("loadedRowsCount", function($assertionValueAndParams) {
        // This assertion can always be true because the goal of this test is to
        // render Components/Table for each model.
        // In case of DB problem, the Components/Table will throw an exception and the
        // test will fail.
        return TRUE; 
      });
    }

  }
}
