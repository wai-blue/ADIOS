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
  public string $gtp = "";
  // public $languageDictionary = [];

  public string $name = ""; // $name and $fullName are the same, $name is deprecated
  public string $fullName = ""; // $name and $fullName are the same, $name is deprecated
  public string $shortName = "";
  public string $myRootFolder = "";

  public array $params = [];
  public array $models = [];

  function __construct(\ADIOS\Core\Loader $app, array $params = []) {
    $appNamespace = ($app->config['appNamespace'] ?? 'App');
    $this->name = str_replace("{$appNamespace}\\Widgets\\", "", get_class($this));
    $this->fullName = str_replace("{$appNamespace}\\Widgets\\", "", get_class($this));
    $this->shortName = end(explode("/", $this->name));

    $this->app = $app;
    $this->params = $params;
    $this->gtp = $this->app->gtp;

    $this->myRootFolder = str_replace("\\", "/", dirname((new \ReflectionClass(get_class($this)))->getFileName()));

    if (!is_array($this->params)) {
      $this->params = [];
    }

    // preklady
    // $this->languageDictionary = $this->app->loadLanguageDictionary($this);

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
