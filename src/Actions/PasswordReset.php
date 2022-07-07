<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Actions;

/**
 * 'Password-reset' action. Renders the password reset screen of the ADIOS application.
 *
 * @package UI\Actions
 */
class PasswordReset extends \ADIOS\Core\Action {
  public function preRender() {
    return [
      "userPasswordReset" => $this->adios->userPasswordReset
    ];
  }
}
