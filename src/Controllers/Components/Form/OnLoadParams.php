<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\Form;

/**
 * @package Components\Controllers\Form
 */
class OnLoadParams extends \ADIOS\Core\Controller {
  public static bool $hideDefaultDesktop = true;

  function __construct(\ADIOS\Core\Loader $adios, array $params = []) {
    parent::__construct($adios, $params);
    $this->permissionName = $this->params['model'] . ':Read';
  }

  public function getFormParams(array $customParams = []) {
    try {
      $model = $this->adios->getModel($this->params['model']);
      $columns = $model->getColumnsToShowInView('Form');

      $customParams = \ADIOS\Core\HelperFunctions::arrayMergeRecursively(
        $customParams,
        $model->defaultFormParams ?? []
      );

      if (is_array($customParams['columns'])) {
        foreach ($columns as $colName => $colDef) {
          if (
            isset($customParams['columns'][$colName]['show'])
            && !$customParams['columns'][$colName]['show']
          ) {
            unset($columns[$colName]);
          } else {
            $columns[$colName]['viewParams']['Form'] = $customParams['columns'][$colName];
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
          'folderUrl' => $model->getFolderUrl(),
          'canRead' => $canRead,
          'canCreate' => $canCreate,
          'canUpdate' => $canUpdate,
          'canDelete' => $canDelete,
          'readonly' => !($canUpdate || $canCreate),
        ]
      );
      //   $tmpModel->defaultFormParams ?? []
      // );
    } catch (\Exception $e) {
      http_response_code(400);

      return [
        'status' => 'error',
        'message' => $e->getMessage() 
      ];
    }
  }

  public function renderJson() {
    return $this->getFormParams();
  }

}
