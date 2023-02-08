<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Actions\UI\DataTable;

/**
 * @package UI\Actions\DataTable
 */
class Update extends \ADIOS\Core\Action {

  public function render() {
    try {
      if (is_numeric($this->params['id'])) {
        $tmpModel = $this->adios->getModel($this->params['model'])
          ->find($this->params['id'])
        ;

        return $tmpModel->update([
          $this->params['colName'] => $this->params['newValue']
        ]);
      } else {
        throw new \ADIOS\Core\Exceptions\GeneralException("Nothing to update.");
      }

      // Replace newValue if col is type of enum
      /*$columnSettings = $adios->db->tables[$table];
      if (!empty($columnSettings[$colName]['enum_values'])) {
        $newValue = array_search($newValue, $columnSettings[$colName]['enum_values']);
      } */
    } catch (\ADIOS\Core\Exceptions\GeneralException $e) {
      return $e->getMessage();
    }
  }
}