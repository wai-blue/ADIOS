<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Auth;

class ModelUser extends \ADIOS\Core\Auth {
  public $loginAttribute = 'login';
  public $passwordAttribute = 'password';
  public $activeAttribute = 'is_active';
  public $verifyMethod = 'password_verify';

  public function auth() {

    $userModel = new ($this->app->getCoreClass('Models\\User'))($this->app);

    if ($this->isUserInSession()) {
      $this->loadUserFromSession();
    } else {
      $login = $this->app->params['login'] ?? '';
      $password = $this->app->params['password'] ?? '';
      $rememberLogin = $this->app->params['rememberLogin'] ?? false;

      $login = trim($login);

      if (empty($login) && !empty($_COOKIE[_ADIOS_ID.'-user'])) {
        $login = $userModel->authCookieGetLogin();
      }

      if (!empty($login) && !empty($password)) {
        $users = $userModel->eloquent
          ->orWhere($this->loginAttribute, $login)
          ->where($this->activeAttribute, '<>', 0)
          ->get()
          ->makeVisible([$this->passwordAttribute])
          ->toArray()
        ;

        foreach ($users as $user) {
          $passwordMatch = FALSE;

          if ($this->verifyMethod == 'password_verify' && password_verify($password, $user[$this->passwordAttribute] ?? "")) {
            $passwordMatch = TRUE;
          }
          if ($this->verifyMethod == 'md5' && md5($password) == $user[$this->passwordAttribute]) {
            $passwordMatch = TRUE;
          }

          if ($passwordMatch) {
            $authResult = $userModel->loadUser($user['id']);
            $this->signIn($authResult);

            if ($rememberLogin) {
              setcookie(
                _ADIOS_ID.'-user',
                $userModel->authCookieSerialize($user[$this->loginAttribute], $user[$this->passwordAttribute]),
                time() + (3600 * 24 * 30)
              );
            }

            break;

          }
        }
      }
    }
  }
}
