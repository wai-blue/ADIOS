<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Actions\UI\Dashboard;

/**
 * @package UI\Actions
 */
class GetCardContent extends \ADIOS\Core\Action {
  public static bool $hideDefaultDesktop = TRUE;

  function render() {
    $dashboard = new \ADIOS\Core\Views\Dashboard($this->adios);
    return $dashboard->getCardContent($this->params['uid'] ?? "");
  }
}
