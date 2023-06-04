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
class PasswordReset extends \ADIOS\Core\Action {
  public static bool $requiresUserAuthentication = FALSE;
  public static bool $hideDefaultDesktop = TRUE;

  public function preRender() {
    $token = $this->params["token"];
    $tokenStatus = "";
    $tokenError = "";

    if ($token != NULL) { 
      try {
        $userModel = $this->adios->getModel("Core/Models/User");
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
