<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\Form;

// use ADIOS\Core\DB\DataTypes\DataTypeColor;

/**
 * @package Components\Controllers\Form
 */
class Endpoint extends \ADIOS\Core\Controller {
  public static bool $hideDefaultDesktop = true;

  protected ?\Illuminate\Database\Eloquent\Builder $query = null;
  public \ADIOS\Core\Model $model;

  function __construct(\ADIOS\Core\Loader $adios, array $params = []) {
    parent::__construct($adios, $params);
    $this->permissionName = $this->params['model'] . ':Read';
  }

public function prepareDataQuery(): \Illuminate\Database\Eloquent\Builder {
  $columnsToShowAsString = '';
  $tmpColumns = $this->model->columns();//getColumnsToShowInView('Form');

  foreach ($tmpColumns as $tmpColumnName => $tmpColumnDefinition) {
    if (!isset($tmpColumnDefinition['relationship'])) {
      $columnsToShowAsString .= ($columnsToShowAsString == '' ? '' : ', ') . $tmpColumnName;
    }
  }

  // TODO: Toto je pravdepodobne potencialna SQL injection diera. Opravit.
  $query = $this->model->selectRaw($columnsToShowAsString);

  foreach ($tmpColumns as $tmpColumnName => $tmpColumnDefinition) {
    if (isset($tmpColumnDefinition['relationship']) && $tmpColumnDefinition['type'] == 'tags') {
      $query->with($tmpColumnDefinition['relationship']);
      $details[$tmpColumnDefinition['relationship']] = $query->first()->roles()->getRelated()->get()->toArray();
    }
  }

  return $query;
}

  public function loadData() {
    $this->model = $this->adios->getModel($this->params['model']);

    $data = [];
    $details = [];

    if (isset($this->params['id']) && (int) $this->params['id'] > 0) {
      $this->query = $this->prepareDataQuery();
      $data = $this->query->find($this->params['id'])->toArray();

      foreach ($details as $key => $value) {
        $data[$key] = [
          'values' => $data[$key],
          'all' => $value
        ];
      }
    }

    return $data;
  }


  public function getParams(array $customParams = []) {
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
      //   $this->model->defaultFormParams ?? []
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
    try {
      return [
        'params' => ($this->params['returnParams'] ? $this->getParams() : []),
        'inputs' => ($this->params['returnData'] ? $this->loadData() : []),
      ];
    } catch (QueryException $e) {
      http_response_code(500);

      return [
        'status' => 'error',
        'message' => $e->getMessage() 
      ];
    } catch (\Exception $e) {
      http_response_code(400);

      return [
        'status' => 'error',
        'message' => $e->getMessage() 
      ];
    }
  }

}
