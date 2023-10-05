<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\UI\DataTable;

/**
 * @package UI\Controllers\DataTable
 */
class Update extends \ADIOS\Core\Controller {

  public function render() {
    try {
      
      $idToUpdate = (int) $this->params['id'];

      if ($idToUpdate > 0) {
        $sessionParams = (array) $_SESSION[_ADIOS_ID]['views'][$this->params['uid']];
        $colNameToUpdate = $this->params['colName'];
        $newValue = $this->params['newValue'];

        $tmpModel = $this->adios->getModel($sessionParams['model']);

        // Replace newValue if col is type of enum
        if (!empty($sessionParams['columnSettings'][$colNameToUpdate]['enum_values'])) {
          $newValue = array_search(
            $this->params['newValue'], 
            $sessionParams['columnSettings'][$colNameToUpdate]['enum_values']
          );
        } 

        return $tmpModel->find($idToUpdate)->update([
          $colNameToUpdate => $newValue
        ]);
      } else {
        throw new \ADIOS\Core\Exceptions\GeneralException("Nothing to update.");
      }
    } catch (\ADIOS\Core\Exceptions\GeneralException $e) {
      return $e->getMessage();
    }
  }
}