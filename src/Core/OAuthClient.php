<?php

namespace ADIOS\Core;

class OAuthClient {
  public ?\ADIOS\Core\Loader $app = null;
  
  public function __construct($app) {
    $this->app = $app;
  }

  function safeUrlDecode(string $input)
  {
    $remainder = strlen($input) % 4;
    if ($remainder) {
      $pad = 4 - $remainder;
      $input .= str_repeat('=', $pad);
    }

    return base64_decode(strtr($input, '-_', '+/'));
  }

  public function authenticate(string $username, string $password)
  {
  }

  public function verifyAccessToken(string $accessToken): bool
  {
    if (empty($accessToken)) return false;
    
    list($jwtHeaderB64, $jwtPayloadB64, $jwtSignatureB64) = explode(".", $accessToken);
    $jwtHeader = $this->safeUrlDecode($jwtHeaderB64 ?? '');
    $jwtPayload = $this->safeUrlDecode($jwtPayloadB64 ?? '');
    $jwtSignature = $this->safeUrlDecode($jwtSignatureB64 ?? '');

    return (bool) openssl_verify(
      $this->safeUrlDecode($jwtHeader).".".$this->safeUrlDecode($jwtPayload),
      $jwtSignature,
      file_get_contents($this->app->config['oauth']['publicKey']),
      OPENSSL_ALGO_SHA256
    );

  }

  public function persistUser(array $user)
  {
    $this->app->userProfile = $user;
    $this->app->userLogged = TRUE;
    $_SESSION[_ADIOS_ID]['userProfile'] = $user;
  }

  public function signOut()
  {
    unset($_SESSION[_ADIOS_ID]);
    $this->app->userProfile = [];
    $this->app->userLogged = FALSE;
  }

}