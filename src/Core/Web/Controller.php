<?php

namespace ADIOS\Core\Web;

class Controller {
  public $adios;
  public array $params = [];
  public array $viewParams = [];

  function __construct($adios, $params = []) {
    $this->adios = $adios;
    $this->params = $params;
  }

  public function prepareViewParams() {
    $this->viewParams = [];
  }

  public function render(array $params) {
    // if string is returned, ADIOS Web will not continue in rendering and outputs the returned string
    return NULL;
  }

}

