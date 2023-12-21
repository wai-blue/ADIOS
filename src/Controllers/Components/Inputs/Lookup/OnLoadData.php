<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\Inputs\Lookup;

/**
 * @package Components\Controllers\Table
 */
class OnLoadData extends \ADIOS\Core\Controller {
  public static bool $hideDefaultDesktop = true;

  public function renderJson() { 
    try {
      $tmpModel = $this->adios->getModel($this->params['model']);

      $lookupSqlValue = "(" .
        str_replace("{%TABLE%}.", '', $tmpModel->lookupSqlValue())
        . ") as lookupSqlValue";

      $tmpData = $tmpModel->selectRaw('id, ' . $lookupSqlValue)->get();

      $data = [];
      foreach ($tmpData as $item) {
        $data[$item['id']] = $item;
      }

      return [
        'data' => $data
      ];
    } catch (\ADIOS\Core\Exceptions\GeneralException $e) {
      // TODO: Error
    }
  }

}
