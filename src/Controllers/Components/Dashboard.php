<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components;

/**
 * @package Components\Controllers
 */
class Dashboard extends \ADIOS\Core\Controller {

  function render() {
    if ((int) ($_GET['preset'] ?? 0) < 0) return $this->adios->renderHtmlWarning(400);

    return $this->adios->view->Dashboard($this->params)->render();
  }

}
