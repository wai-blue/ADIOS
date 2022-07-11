<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Actions;

/**
 * 'Forgot-password' action. Renders the password reset screen of the ADIOS application.
 *
 * @package UI\Actions
 */
class ForgotPassword extends \ADIOS\Core\Action {
  public function preRender() {
    return [
      "userForgotPassword" => $this->adios->userForgotPassword
    ];
  }
}
