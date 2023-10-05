<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\UI\Input;

/**
 * @package UI\Controllers\Input
 */
class AutocompleteGetItemText extends \ADIOS\Core\Controller {
  public function render() {
    $value = (int) $this->params['value'];
    $lookupModel = $this->adios->getModel($this->params['model']);

    $lookupRow = reset($lookupModel->lookupQuery(
      $this->params['initiating_model'],
      $this->params['initiating_column'],
      [], // form_data
      [], // params
      "id = {$value}" // having
    )->fetch());

    if (!is_array($lookupRow) && $value > 0) {
      return "-- Record not found --";
    } else {
      return $lookupRow['input_lookup_value'];
    }
  }
}