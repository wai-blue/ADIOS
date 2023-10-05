<?php

namespace ADIOS\Core\Widget;

class Controller extends \ADIOS\Core\Controller {
  public $widget = NULL;

  function __construct(&$adios, $params = []) {
    parent::__construct($adios, $params);

    $widgetName = str_replace("ADIOS\\Controllers\\", "", get_class($this));
    $widgetName = substr($widgetName, 0, strpos($widgetName, "\\"));

    $this->widget = $this->adios->widgets[$widgetName] ?? NULL;
  }
}

