<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core;

/*
  * ...
  * 
  */

class Cron {
  protected \ADIOS\Core\Loader $app;
  protected array $params = [];

  function __construct(\ADIOS\Core\Loader $app, array $params = []) {
    $this->app = $app;
    $this->params = $params;
  }

  public function run() {
    //
  }

}

