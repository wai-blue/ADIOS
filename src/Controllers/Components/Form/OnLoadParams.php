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

  public function renderJson() { 
    try {
      $tmpModel = $this->adios->getModel($this->params['model']);
      $tmpColumns = $tmpModel->getColumnsToShowInView('Form');

      if (is_array($this->params['columns'])) {
        $tmpColumns = \ADIOS\Core\HelperFunctions::arrayMergeRecursively(
          $tmpColumns,
          $this->params['columns']);
      }

      $canRead = $this->adios->permissions->has($this->params['model'] . ':Read');
      $canCreate = $this->adios->permissions->has($this->params['model'] . ':Create');
      $canUpdate = $this->adios->permissions->has($this->params['model'] . ':Update');
      $canDelete = $this->adios->permissions->has($this->params['model'] . ':Delete');

      return array_merge(
        [
          'columns' => $tmpColumns,
          'folderUrl' => $tmpModel->getFolderUrl(),
          'canRead' => $canRead,
          'canCreate' => $canCreate,
          'canUpdate' => $canUpdate,
          'canDelete' => $canDelete,
          'readonly' => !($canUpdate || $canCreate),
        ],
        $tmpModel->defaultFormParams ?? []
      );
    } catch (\Exception $e) {
      http_response_code(400);

      return [
        'status' => 'error',
        'message' => $e->getMessage() 
      ];
    }
  }

}
