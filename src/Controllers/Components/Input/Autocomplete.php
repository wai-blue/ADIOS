<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\Input;

/**
 * @package Components\Controllers\Input
 */
class Autocomplete extends \ADIOS\Core\Controller {
  public function render() {
    $tmpValue = str_replace(' ', '', str_replace('.', '', str_replace(',', '', str_replace('-', '', str_replace('_', '', $this->params['value'])))));
    $having = "replace(replace(replace(replace(replace(input_lookup_value, ' ', ''), '.', ''), ',', ''), '-', ''), '_', '') like '%".$this->app->db->escape($tmpValue)."%'";

    $lookupModel = $this->app->getModel($this->params['model']);

    $lookupRows = $lookupModel->lookupQuery(
      $this->params['initiating_model'],
      $this->params['initiating_column'],
      @json_decode($this->params['form_data'], TRUE) ?? [], // form_data
      [],
      $having
    )->fetch();

    $retval = [];
    if (_count($lookupRows)) {
      foreach ($lookupRows as $key => $value) {
        $retval[] = [
          htmlspecialchars($value['id']),
          htmlspecialchars($value['input_lookup_value'])
        ];
      }
    }

    return $retval;
  }
}

