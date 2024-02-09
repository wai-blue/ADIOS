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
class Params extends \ADIOS\Core\Controller {
  public static bool $hideDefaultDesktop = true;

  function __construct(\ADIOS\Core\Loader $adios, array $params = []) {
    parent::__construct($adios, $params);
    $this->permissionName = $this->params['model'] . ':Read';
  }

  /**
  * React component take this argument type for displaying in table
  * so in some case replace type for own custom type
  */
  private function getColumnType($columnType): string {
    switch ($columnType) {
      case 'datetime':
      case 'date':
      case 'time': return 'string';
      default: return $columnType;
    }
  }

  public function getTableParams(array $customParams = []) {
    try {
      $model = $this->adios->getModel($this->params['model']);
      $columns = $model->getColumnsToShowInView('Table');

      $customParams = \ADIOS\Core\HelperFunctions::arrayMergeRecursively(
        $customParams,
        $model->defaultTableParams ?? []
      );

      if (is_array($customParams['columns'])) {
        foreach ($columns as $colName => $colDef) {
          if (
            isset($customParams['columns'][$colName]['show'])
            && !$customParams['columns'][$colName]['show']
          ) {
            unset($columns[$colName]);
          } else {
            $columns[$colName]['viewParams']['Table'] = $customParams['columns'][$colName];
          }
        }

        unset($customParams['columns']);
      }

      $canRead = $this->adios->permissions->has($this->params['model'] . ':Read');
      $canCreate = $this->adios->permissions->has($this->params['model'] . ':Create');
      $canUpdate = $this->adios->permissions->has($this->params['model'] . ':Update');
      $canDelete = $this->adios->permissions->has($this->params['model'] . ':Delete');

      return \ADIOS\Core\HelperFunctions::arrayMergeRecursively(
        $customParams,
        [
          'columns' => $columns,
          'title' => $model->defaultTableParams['title'],
          'folderUrl' => $model->getFolderUrl(),
          'addButtonText' => $model->defaultTableParams['addButtonText'] ?? "Add new record",
          'canRead' => $canRead,
          'canCreate' => $canCreate,
          'canUpdate' => $canUpdate,
          'canDelete' => $canDelete,
        ]
      );
    } catch (\Exception $e) {
      http_response_code(400);

      return [
        'status' => 'error',
        'message' => $e->getMessage() 
      ];
    }
  }

  public function renderJson() {
    return $this->getTableParams();
  }

}
