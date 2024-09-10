<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core;

class Input {
  public $name;
  public $app;
  public $params;
  public $gtp;
  public $uid;
  public $cssUid;
  public $value;

  /**
   * languageDictionary
   *
   * @internal
   * @var array
   */
  // public $languageDictionary = [];

  function __construct($app, $uid, $params) {
    $this->app = $app;
    $this->gtp = $this->app->gtp;
    $this->uid = (empty($uid) ? $this->app->getUid() : $uid);
    $this->cssUid = (empty($params['css_uid']) ? $this->uid : $params['css_uid']);
    $this->params = $params;
    $this->value = $this->params['value'];

    // $this->languageDictionary = $this->app->loadLanguageDictionary($this);
  }

  /**
   * translate
   *
   * @internal
   * @param  mixed $string
   * @param  mixed $context
   * @param  mixed $toLanguage
   * @return void
   */
  public function translate(string $string, array $vars = []): string
  {
    return $this->app->translate($string, $vars, $this);
  }

  public function render() {
    return "";
  }

  public function formatValueToHtml() {
    return $this->value;
  }

  public function formatValueToCsv() {
    return $this->value;
  }
}
