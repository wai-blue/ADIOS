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
        $tmpModel = $this->adios->getModel($this->params['model']);

        $newValue = $this->params['newValue'];

        // Replace newValue if col is type of enum
        $columnSettings = $this->adios->db->tables["{$this->adios->gtp}_{$tmpModel->sqlName}"];
        if (!empty($columnSettings[$this->params['colName']]['enum_values'])) {
          $newValue = array_search(
            $this->params['newValue'], 
            $columnSettings[$this->params['colName']]['enum_values']
          );
        } 

        return $tmpModel->find($this->params['id'])->update([
          $this->params['colName'] => $newValue
        ]);
      } else {
        throw new \ADIOS\Core\Exceptions\GeneralException("Nothing to update.");
      }
    } catch (\ADIOS\Core\Exceptions\GeneralException $e) {
      return $e->getMessage();
    }
  }
}