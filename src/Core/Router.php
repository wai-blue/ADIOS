<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core;

class Router {
  public ?\ADIOS\Core\Loader $adios = null;

  public $routing = [];
  
  public function __construct(\ADIOS\Core\Loader $adios) {
    $this->adios = $adios;
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
          foreach ($variables as $k2 => $v2) {
            $routeParams[$paramName] = str_replace('$'.$k2, $v2, $routeParams[$paramName]);
          }
        }
      }
    }

    return $routeParams;
  }

  public function applyRouting(string $controller, array $params): array {
    if (!empty($controller)) {
      // Prejdem routovaciu tabulku, ak najdem prislusny zaznam, nastavim controller a params.
      // Ak pre $params['controller'] neexistuje vhodny routing, nemenim nic - pouzije sa
      // povodne $params['controller'], cize requestovana URLka.

      foreach ($this->routing as $routePattern => $route) {
        if (preg_match($routePattern, $controller, $m)) {
          // povodny $controller nahradim novym $route['controller']
          $controller = $route['controller'];

          $route['params'] = $this->replaceRouteVariables($route['params'], $m);

          foreach ($route['params'] as $k => $v) {
            $params[$k] = $v;
          }
        }
      }
    }

    if (empty($controller)) {
      $controller = (php_sapi_name() === 'cli' ? "" : $this->adios->config['defaultController']);
    }

    if (empty($controller)) {
      throw new \ADIOS\Core\Exceptions\GeneralException("No controller specified.");
    }


    return [$controller, $params];
  }

  public function checkPermissions(string $controller) {
    $permissionForRequestedUri = "";
    foreach ($this->routing as $routePattern => $route) {
      if (preg_match((string) $routePattern, $controller, $m)) {
        $permissionForRequestedUri = $route['permission'];
      }
    }

    if (
      !empty($permissionForRequestedUri)
      && !$this->adios->permissions->has($permissionForRequestedUri)
    ) {
      throw new \ADIOS\Core\Exceptions\NotEnoughPermissionsException("Not enough permissions ({$permissionForRequestedUri}).");
    }
  }

}