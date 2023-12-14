<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\Dashboard;

/**
 * @package Components\Controllers
 */
class GetCardContent extends \ADIOS\Core\Controller {
  public static bool $hideDefaultDesktop = TRUE;

  function render() {
    $dashboard = new \ADIOS\Core\ViewsWithController\Dashboard($this->adios);

    return $dashboard->getCardContent($this->params['uid'] ?? "");
  }
}
