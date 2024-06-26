<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Desktop\Ajax;

/**
 * @package Components\Controllers
 */
class SetLanguage extends \ADIOS\Core\Controller {
  public function render() {
    if (!in_array($this->params['language'], $this->app->config['availableLanguages'])) {
      throw new \ADIOS\Core\Exceptions\GeneralException("Invalid language");
    }

    $_SESSION[_ADIOS_ID]['language'] = $this->params['language'];
  }
}