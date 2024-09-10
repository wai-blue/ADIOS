<?php

namespace ADIOS\Core\Web;

class Router {
  public array $routingTable = [];
  public \ADIOS\Core\Loader $app;

  function __construct($routingTable = NULL) {
    if ($routingTable !== NULL) {
      $this->setRoutingTable($routingTable);
    }
  }

  function setRoutingTable($routingTable) {
    $this->routingTable = $routingTable;
    return $this;
  }

  function getCurrentPageRoutes() {
    $routes = [];

    foreach ($this->routingTable as $route => $params) {
      if (
        ($route == "*"
          || $route == $this->app->web->pageUrl
          || preg_match("/^".str_replace("/", "\\/", trim($route, "/"))."$/", $this->app->web->pageUrl)
        )
      ) {
        $routes[$route] = $params;
      }
    }

    return $routes;
  }

  function getCurrentPageControllers() {
    $controllers = [];
    $routes = $this->getCurrentPageRoutes();
    
    foreach ($routes as $route => $params) {
      if (!empty($params["controllers"])) {
        foreach ($params["controllers"] as $controller) {
          $controllers[] = $controller;
        }
      }
    }

    return $controllers;
  }

  function getCurrentPageUrlVariables() {
    $urlVariables = [];
    $routes = $this->getCurrentPageRoutes();

    foreach ($routes as $route => $params) {
      if (isset($params['urlVariables']) && is_array($params['urlVariables'])) {
        if (preg_match("/^".str_replace("/", "\\/", trim($route, "/"))."$/", $this->app->web->pageUrl, $m)) {
          $tmpUrlVariables = $params['urlVariables'];
          foreach ($tmpUrlVariables as $varIndex => $varName) {
            if (isset($m[$varIndex])) {
              $urlVariables[$varName] = $m[$varIndex];
              unset($params['urlVariables'][$varIndex]);
            }
          }

          $urlVariables = array_merge($urlVariables, $params['urlVariables']);
        }
      }
    }

    return $urlVariables;
  }

  function getCurrentPageTemplateVariables() {
    $templateVariables = [];
    $routes = $this->getCurrentPageRoutes();

    foreach ($routes as $route => $params) {
      if (
        !empty($params["templateVariables"])
        && is_array($params["templateVariables"])
      ) {
        $templateVariables = array_merge(
          $templateVariables,
          $params["templateVariables"]
        );
      }
    }

    return $templateVariables;
  }

  function getCurrentPageTemplate() {
    $template = "";
    $routes = $this->getCurrentPageRoutes();

    foreach ($routes as $route => $params) {
      if (!empty($params["template"])) {
        $template = $params["template"];
      }
    }

    if ($template == "") $template = $this->app->web->pageUrl;

    return $template;
  }

  function getNotFoundTemplate() {
    return $this->routingTable["NotFoundTemplate"] ?? "";
  }

  function performRedirects() {
    $routes = $this->getCurrentPageRoutes();

    foreach ($routes as $route) {
      if (!empty($route['redirect'])) {
        switch ($route['redirect'][1]) {
          case 301:
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . $this->app->web->config['rewriteBase'] . $route['redirect'][0]);
            exit();
          break;
          case 302:
            header('HTTP/1.1 302 Moved Temporarily');
            header('Location: ' . $this->app->web->config['rewriteBase'] . $route['redirect'][0]);
            exit();
          break;
        }
      }
    }
  }

}