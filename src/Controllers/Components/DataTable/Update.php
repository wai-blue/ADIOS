<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\DataTable;

/**
 * @package Components\Controllers\DataTable
 */
class Update extends \ADIOS\Core\Controller {

  public function render() {
    try {
      
      $idToUpdate = (int) $this->params['id'];

      if ($idToUpdate > 0) {
        $sessionParams = (array) $_SESSION[_ADIOS_ID]['views'][$this->params['uid']];
        $colNameToUpdate = $this->params['colName'];
        $newValue = $this->params['newValue'];

        $tmpModel = $this->app->getModel($sessionParams['model']);

        // Replace newValue if col is type of enum
        if (!empty($sessionParams['columnSettings'][$colNameToUpdate]['enumValues'])) {
          $newValue = array_search(
            $this->params['newValue'], 
            $sessionParams['columnSettings'][$colNameToUpdate]['enumValues']
          );
        } 

        return $tmpModel->eloquent->find($idToUpdate)->update([
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