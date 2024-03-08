<?php

namespace ADIOS\Controllers;

/**
 * @package Components\Controllers
 */
class Logout extends \ADIOS\Core\Controller {
  public bool $requiresUserAuthentication = FALSE;
  public bool $hideDefaultDesktop = TRUE;

  public function prepareViewParams() {
    unset($_SESSION[_ADIOS_ID]);

    setcookie(_ADIOS_ID.'-user', '', 0);
    setcookie(_ADIOS_ID.'-language', '', 0);

    header("Location: {$this->adios->config['url']}");
    exit();

  }
}
