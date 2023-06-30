<?php

namespace ADIOS\Core\Web;

class Controller {
  public $adios;
  public array $params = [];

  function __construct($adios, $params = []) {
    $this->adios = $adios;
    $this->params = $params;
  }

  public function preRender() { }

  public function render() {
    // if string is returned, ADIOS Web will not continue in rendering and outputs the returned string
    return NULL;
  }

  public function postRender() { }

}

