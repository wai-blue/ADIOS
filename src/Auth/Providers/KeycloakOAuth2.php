<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Auth\Providers;

class KeycloakOAuth2 extends \ADIOS\Core\Auth {
  public $provider;

  function __construct(\ADIOS\Core\Loader $app, array $params = [])
  {
    parent::__construct($app, $params);

    $this->provider = new \League\OAuth2\Client\Provider\GenericProvider([
      'clientId'                => $this->app->config['auth']['clientId'],    // The client ID assigned to you by the provider
      'clientSecret'            => $this->app->config['auth']['clientSecret'],    // The client password assigned to you by the provider
      'redirectUri'             => $this->app->config['url'] . '/',
      'urlAuthorize'            => $this->app->config['auth']['urlAuthorize'],
      'urlAccessToken'          => $this->app->config['auth']['urlAccessToken'],
      'urlResourceOwnerDetails' => $this->app->config['auth']['urlResourceOwnerDetails'],
    ], [
      'httpClient' => new \GuzzleHttp\Client([\GuzzleHttp\RequestOptions::VERIFY => false]),
    ]);

  }

  public function signOut() {
    $accessToken = $_SESSION[_ADIOS_ID]['oauthAccessToken'];
// var_dump($accessToken->getToken());echo"<br/>";
//     $options = [
//       'form_params' => [
//         'client_id' => $this->app->config['auth']['clientId'],
//         'client_secret' => $this->app->config['auth']['clientSecret'],
//         'token_type_hint' => 'access_token',
//         'token' => $accessToken->getToken(),
//       ],
//       'headers' => [
//         'Content-type' => 'application/x-www-form-urlencoded',
//         // 'access_token' => $accessToken->getToken(),
//       ],
//     ];
// var_dump($options);
//     $request = $this->provider->getAuthenticatedRequest(
//       'POST',
//       $this->app->config['auth']['urlRevoke'],
//       '',
//       $options
//     );

//     $response = $this->provider->getResponse($request);
//     var_dump($response);echo"<br/>";
//     var_dump($response->getBody()->getContents());
//     exit;



    // $request = $this->provider->getAuthenticatedRequest(
    //   'POST',
    //   $this->app->config['auth']['urlLogout'],
    //   $accessToken,
    //   $options
    // );
    // var_dump($request);echo"<br/>";


    // $client = new \GuzzleHttp\Client([\GuzzleHttp\RequestOptions::VERIFY => false]);
    // $response = $client->post(
    //   $this->app->config['auth']['urlRevoke'],
    //   [
    //     'headers' => [
    //       'Content-Type' => 'application/x-www-form-urlencoded',
    //     ],
    //     'form_params' => [
    //       'client_id' => $this->app->config['auth']['clientId'],
    //       'client_secret' => $this->app->config['auth']['clientSecret'],
    //       'token_type_hint' => 'access_token',
    //       'token' => $accessToken->getToken(),
    //     ],
    //   ]
    // );

    // var_dump(($accessToken->getValues()));exit;
    $idToken = $accessToken->getValues()['id_token'] ?? '';

    $this->deleteSession();
    header("Location: {$this->app->config['auth']['urlLogout']}?id_token_hint=".\urlencode($idToken)."&post_logout_redirect_uri=".\urlencode("{$this->app->config['accountUrl']}?signed-out"));
    exit;
    // } else {
    //   parent::signOut();
    // }
  }

  public function auth()
  {
    $accessToken = $_SESSION[_ADIOS_ID]['oauthAccessToken'];

    if ($accessToken) {
      try {
        $accessToken = $this->provider->getAccessToken('refresh_token', [
          'refresh_token' => $accessToken->getRefreshToken()
        ]);

        $_SESSION[_ADIOS_ID]['oauthAccessToken'] = $accessToken;

        $resourceOwner = $this->provider->getResourceOwner($accessToken);

        if ($resourceOwner) $this->signIn($resourceOwner->toArray());
        else $this->deleteSession();
      } catch (\Exception $e) {
        $this->deleteSession();
      }
    } else {

      $authCode = $this->app->params['code'] ?? '';
      $authState = $this->app->params['state'] ?? '';

      // If we don't have an authorization code then get one
      if (empty($authCode)) {

        // Fetch the authorization URL from the provider; this returns the
        // urlAuthorize option and generates and applies any necessary parameters
        // (e.g. state).
        $authorizationUrl = $this->provider->getAuthorizationUrl(['scope' => ['openid']]);

        // Get the state generated for you and store it to the session.
        $_SESSION[_ADIOS_ID]['oauth2state'] = $this->provider->getState();

        // Optional, only required when PKCE is enabled.
        // Get the PKCE code generated for you and store it to the session.
        $_SESSION[_ADIOS_ID]['oauth2pkceCode'] = $this->provider->getPkceCode();

        // Redirect the user to the authorization URL.
        header('Location: ' . $authorizationUrl);
        exit;

      // Check given state against previously stored one to mitigate CSRF attack
      } elseif (
        empty($authState)
        || empty($_SESSION[_ADIOS_ID]['oauth2state'])
        || $authState !== $_SESSION[_ADIOS_ID]['oauth2state']
      ) {
        if (isset($_SESSION[_ADIOS_ID]['oauth2state'])) unset($_SESSION[_ADIOS_ID]['oauth2state']);
        exit('Invalid state');
      } else {

        try {

          // Optional, only required when PKCE is enabled.
          // Restore the PKCE code stored in the session.
          $this->provider->setPkceCode($_SESSION[_ADIOS_ID]['oauth2pkceCode']);

          // Try to get an access token using the authorization code grant.
          $accessToken = $this->provider->getAccessToken('authorization_code', [
            'code' => $authCode
          ]);

          $_SESSION[_ADIOS_ID]['oauthAccessToken'] = $accessToken;

          // Using the access token, we may look up details about the
          // resource owner.
          $resourceOwner = $this->provider->getResourceOwner($accessToken);

          $authResult = $resourceOwner->toArray();

        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

          if ($e->getMessage() == 'invalid_grant') {
          }

          // Failed to get the access token or user details.
          exit($e->getMessage());

        }
      }

      if ($authResult) {
        $this->signIn($authResult);

        $this->app->router->redirectTo('');
        exit;
      } else {
        $this->deleteSession();
      }
    }
  }
}
