<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Actions\UI\Dashboard;

use ADIOS\Core\Views\Input;
use ADIOS\Core\Views\Inputs\CheckboxField;

/**
 * @package UI\Actions
 */
class Configure extends \ADIOS\Core\Action {
  public string $twigTemplate = "ADIOS/Templates/UI/Dashboard/Configure";

  function preRender(): array
  {
    $dashboard = new \ADIOS\Core\Views\Dashboard($this->adios, $this->params);
    $availableCards = array_merge($dashboard->getAvailableCards()[0]);
    $forms = $dashboard->getSettingsInputs($availableCards);

    return [
      'availableCards' => $availableCards,
      'forms' =>  $forms
    ];
  }
}
