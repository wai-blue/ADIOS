<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers;

/**
 * 'Login' action. Renders the login screen of the ADIOS application.
 *
 * @package Components\Controllers
 */
class SignIn extends \ADIOS\Core\Controller {
  public bool $requiresUserAuthentication = FALSE;
  public bool $hideDefaultDesktop = TRUE;

  function __construct(\ADIOS\Core\Loader $app, array $params = []) {
    parent::__construct($app, $params);

    $this->permission = "";

    $this->setView($this->app->config['appNamespace'] . '/Views/SignIn');
  }

  public function prepareViewParams() {
    parent::prepareViewParams();

    $this->viewParams["login"] = $_POST['login'] ?? '';
    $this->viewParams["userLogged"] = $this->app->userLogged;
  }
}
