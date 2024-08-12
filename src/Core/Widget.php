<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core;

/*
  * ...
  * 
  */

class Widget {
  public \ADIOS\Core\Loader $app;

  public string $name = ""; // $name and $fullName are the same, $name is deprecated
  public string $fullName = ""; // $name and $fullName are the same, $name is deprecated
  public string $shortName = "";

  public array $models = [];

  function __construct(\ADIOS\Core\Loader $app, array $params = []) {
    $appNamespace = ($app->config['appNamespace'] ?? 'App');
    $this->name = str_replace("{$appNamespace}\\Widgets\\", "", get_class($this));
    $this->fullName = str_replace("{$appNamespace}\\Widgets\\", "", get_class($this));
    $this->shortName = end(explode("/", $this->name));

    $this->app = $app;

    // inicializacia widgetu
    $this->init();

    $this->app->dispatchEventToPlugins("onWidgetAfterInit", [
      "widget" => $this,
    ]);

    // nacitanie modelov
    $this->loadModels();

    $this->app->dispatchEventToPlugins("onWidgetModelsLoaded", [
      "widget" => $this,
    ]);

  }

  public function init() {
    // to be overriden
    // desktop shortcuts, routing, ...
  }

  public function onBeforeRender() {
    // to be overriden
  }

  public function onAfterRender() {
    // to be overriden
  }

  public function routing(array $routing = [])
  {
    return $this->app->dispatchEventToPlugins("onWidgetAfterRouting", [
      "model" => $this,
      "routing" => $routing,
    ])["routing"];
  }

  public function onBeforeDesktopParams(\ADIOS\Controllers\Desktop $desktop) {
    // to be overriden
  }

  public function translate(string $string, array $vars = []): string
  {
    return $this->app->translate($string, $vars, $this);
  }

  public function install() {
    return TRUE;
  }

  public function loadModels() {
    $this->name = $dir = str_replace("\\", "/", $this->name);
    $dir = $this->app->widgetsDir . "/{$this->name}/Models";

    if (is_dir($dir)) {
      foreach (scandir($dir) as $file) {
        if (is_file("{$dir}/{$file}")) {
          $tmpModelName = str_replace(".php", "", $file);
          $this->app->registerModel(($this->app->config['appNamespace'] ?? 'App') . "/Widgets/{$this->name}/Models/{$tmpModelName}");
        }
      }
    }

    $this->app->dispatchEventToPlugins("onWidgetAfterModelsLoaded", [
      "widget" => $this,
    ]);
  }

}
