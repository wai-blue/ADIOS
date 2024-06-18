<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core;

class Locale {
  public ?\ADIOS\Core\Loader $app = null;
  
  public function __construct($app) {
    $this->app = $app;
  }

  public function dateFormat() {
    return $this->app->config["locale"]["date"]["format"] ?? "d.m.Y";
  }

  public function datetimeFormat() {
    return $this->app->config["locale"]["datetime"]["format"] ?? "d.m.Y H:i:s";
  }

  public function timeFormat() {
    return $this->app->config["locale"]["time"]["format"] ?? "H:i:s";
  }

  public function currencySymbol() {
    return $this->app->config["locale"]["currency"]["symbol"] ?? "€";
  }

  public function getAll(string $keyBy = "") {
    return [
      "dateFormat" => $this->dateFormat(),
      "timeFormat" => $this->timeFormat(),
      "datetimeFormat" => $this->datetimeFormat(),
      "currencySymbol" => $this->currencySymbol(),
    ];
  }

}