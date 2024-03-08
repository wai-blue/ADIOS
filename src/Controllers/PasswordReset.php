<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers;

/**
 * 'Forgot-password' action. Renders the password reset screen of the ADIOS application.
 *
 * @package Components\Controllers
 */
class PasswordReset extends \ADIOS\Core\Controller {
  public static bool $requiresUserAuthentication = FALSE;
  public bool $hideDefaultDesktop = TRUE;

  public function getViewParams() {
    $token = $this->params["token"];
    $tokenStatus = "";
    $tokenError = "";

    if ($token != NULL) {
      try {
        $userModel = $this->adios->getModel("ADIOS/Core/Models/User");
        $userModel->validateToken($token, false);
        $tokenStatus = "success";
      } catch (\ADIOS\Core\Exceptions\InvalidToken $e) {
        $tokenStatus = "fail";
        $tokenError = "Invalid token: ".$e->getMessage();
      }
    }

    return [
      "userPasswordReset" => $this->adios->userPasswordReset,
      "token" => [
        "status" => $tokenStatus,
        "error" => $tokenError
      ]
    ];
  }
}
