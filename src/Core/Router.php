<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core;

class Router {
  public ?\ADIOS\Core\Loader $app = null;

  public $routing = [];
  
  public function __construct(\ADIOS\Core\Loader $app) {
    $this->app = $app;
  }

  public function setRouting($routing) {
    if (is_array($routing)) {
      $this->routing = $routing;
    }
  }

  public function addRouting($routing) {
    if (is_array($routing)) {
      $this->routing = array_merge($this->routing, $routing);
    }
  }

  public function replaceRouteVariables($routeParams, $variables) {
    if (is_array($routeParams)) {
      foreach ($routeParams as $paramName => $paramValue) {

        if (is_array($paramValue)) {
          $routeParams[$paramName] = $this->replaceRouteVariables($paramValue, $variables);
        } else {
          krsort($variables);
          foreach ($variables as $k2 => $v2) {
            $routeParams[$paramName] = str_replace('$'.$k2, $v2, (string)$routeParams[$paramName]);
          }
        }
      }
    }

    return $routeParams;
  }

  public function applyRouting(string $routeUrl, array $params): array {
    $route = [];

    foreach ($this->routing as $routePattern => $tmpRoute) {
      if (preg_match($routePattern.'i', $routeUrl, $m)) {

        if (!empty($tmpRoute['redirect'])) {
          $url = $tmpRoute['redirect']['url'];
          foreach ($m as $k => $v) {
            $url = str_replace('$'.$k, $v, $url);
          }
          $this->redirectTo($url, $tmpRoute['redirect']['code'] ?? 302);
          exit;
        } else {
          $route = $tmpRoute;
          // $controller = $tmpRoute['controller'] ?? '';
          // $view = $tmpRoute['view'] ?? '';
          // $permission = $tmpRoute['permission'] ?? '';
          $tmpRoute['params'] = $this->replaceRouteVariables($tmpRoute['params'] ?? [], $m);

          foreach ($this->replaceRouteVariables($tmpRoute['params'] ?? [], $m) as $k => $v) {
            $params[$k] = $v;
          }
        }
      }
    }

    // return [$controller, $view, $permission, $params];
    return [$route, $params];
  }

  public function checkPermission(string $permission) {
    if (!$this->app->permissions->granted($permission)
    ) {
      throw new \ADIOS\Core\Exceptions\NotEnoughPermissionsException("Not enough permissions ({$permission}).");
    }
  }

  public function redirectTo(string $url, int $code = 302) {
    header("Location: {$this->app->config['url']}/".trim($url, "/"), true, $code);
    exit;
  }

}
