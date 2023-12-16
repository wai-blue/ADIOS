<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\Table;

/**
 * @package Components\Controllers\Table
 */
class OnLoadParams extends \ADIOS\Core\Controller {
  public static bool $hideDefaultDesktop = true;

  public function renderJson() { 
    try {
      $tmpModel = $this->adios->getModel($this->params['model']);

      $tmpColumns = $tmpModel->columns();

      $columns = [];
      foreach ($tmpColumns as $columnName => $column) {
        $columns[] = [
          'field' => $columnName,
          'headerName' => $column['title'],
          'flex' => 1,
          'type' => $column['type']
        ];
      }

      return [
        'columns' => $columns, 
        'title' => $tmpModel->tableTitle,
        'folderUrl' => $tmpModel->getFolderUrl()
      ];
    } catch (\ADIOS\Core\Exceptions\GeneralException $e) {
      // TODO: Error
    }
  }

}
