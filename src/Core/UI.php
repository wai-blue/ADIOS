<?php

namespace ADIOS\Core;

class UI
{

  public $adios;

  public function __construct(&$adios, $params)
  {
    $this->adios = $adios;
  }

  public function create($component_name, $params = null)
  {
    list($component_class, $uid) = explode('#', $component_name);

    $params['component_class'] = $component_class;
    if (!empty($uid)) {
      $params['uid'] = $uid;
    }

    $class_name = "\\ADIOS\\Core\\UI\\{$component_class}";
    return new $class_name($this->adios, $params);
  }

  public function __call(string $name, array $arguments)
  {
    $chr = substr($name, 0, 1);
    $firstLetterIsCapital = strtolower($chr) != $chr;

    $className = "\\ADIOS\\Core\\UI\\{$name}";

    if (
      $firstLetterIsCapital
      && class_exists($className)
    ) {
      return new $className($this->adios, $arguments[0]);
    } else {
      throw new \ADIOS\Core\Exceptions\UnknownUIComponent();
    }
  }

  public function render($component_name, $params = null)
  {
    return $this->create($component_name, $params)->render();
  }

  public function Title($params)
  {
    return $this->create('Title', $params);
  }

  public function Form($params)
  {
    return $this->create('Form', $params);
  }

  public function Input($params)
  {
    return $this->create('Input', $params);
  }

  public function Tabs($params)
  {
    return $this->create('Tabs', $params);
  }

  public function Table($params)
  {
    return $this->create('Table', $params);
  }

  public function Html($params)
  {
    return $this->create('Html', $params);
  }

  public function Tree($params)
  {
    return $this->create('Tree', $params);
  }

  public function FileBrowser($params)
  {
    return $this->create('FileBrowser', $params);
  }

  public function SettingsPanel($params)
  {
    return $this->create('SettingsPanel', $params);
  }

  public function Cards($params)
  {
    return $this->create('Cards', $params);
  }

  public function Window($params)
  {
    return $this->create('Window', $params);
  }

  public function Button($params = [])
  {
    return $this->create('Button', $params);
  }

  public function DataTable($params = [])
  {
    return $this->create('DataTable', $params);
  }
}
