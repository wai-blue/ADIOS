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
class AddCards extends \ADIOS\Core\Controller {

  function render(): bool|string
  {
    return $this->app->view->Dashboard($this->params)
      ->addCardsToConfiguration(json_decode($_POST['cards']), $_POST['preset'], $_POST['area'])
    ;
  }
}
