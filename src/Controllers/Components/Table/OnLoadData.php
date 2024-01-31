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
class OnLoadData extends \ADIOS\Core\Controller {
  public static bool $hideDefaultDesktop = true;

  protected ?\Illuminate\Database\Eloquent\Builder $query = null;

  public \ADIOS\Core\Model $model;
  public array $data = [];

  function __construct(\ADIOS\Core\Loader $adios, array $params = []) {
    parent::__construct($adios, $params);
    $this->permissionName = $this->params['model'] . ':Read';
  }

  public function prepareQuery(): \Illuminate\Database\Eloquent\Builder {
    $params = $this->params;
    $pageLength = (int) $params['pageLength'] ?? 15;

    $this->model = $this->adios->getModel($this->params['model']);

    $tableTitle = $this->model->tableTitle;
    $tmpColumns = $this->model->getColumnsToShowInView('Table');

    $columnsToShowAsString = '';
    foreach ($tmpColumns as $tmpColumnName => $tmpColumnDefinition) {
      if (!isset($tmpColumnDefinition['relationship'])) {
        $columnsToShowAsString .= ($columnsToShowAsString == '' ? '' : ', ') . $tmpColumnName;
      }
    }

    // TODO: Toto je pravdepodobne potencialna SQL injection diera. Opravit.
    $query = $this->model->selectRaw($columnsToShowAsString);

    // LOOKUPS and RELATIONSHIPS
    foreach ($tmpColumns as $columnName => $column) {
      if ($column['type'] == 'lookup') {
        $lookupModel = $this->adios->getModel($column['model']);

        $lookupSqlValue = "(" .
          str_replace("{%TABLE%}.", '', $lookupModel->lookupSqlValue())
          . ") as lookupSqlValue";

        $query->with([$columnName => function ($query) use ($lookupSqlValue) {
          $query->selectRaw('id, ' . $lookupSqlValue);
        }]);
      }

      if (isset($column['relationship'])) {
        $query->with($column['relationship']);
      }
    }

    // FILTER BY
    if (isset($params['filterBy'])) {
      // TODO
    }

    // WHERE
    if (isset($params['where']) && is_array($params['where'])) {
      foreach ($params['where'] as $where) {
        $query->where($where[0], $where[1], $where[2]);
      }
    }

    // Search
    if (isset($params['search'])) {
      $query->where(function ($query) use ($params, $tmpColumns) {
        foreach ($tmpColumns as $columnName => $column) {
          if (!isset($column['relationship'])) {
            $query->orWhere($columnName, 'like', "%{$params['search']}%");
          }
        }
      });
    }
    // ORDER BY
    if (isset($params['orderBy'])) {
      $query->orderBy(
        $params['orderBy']['field'],
        $params['orderBy']['sort']);
    } else {
      $query->orderBy('id', 'DESC');
    }

    return $query;
  }

  public function loadData(): array {
    // Laravel pagination
    return $this->query->paginate(
      $pageLength, ['*'],
      'page',
      $this->params['page'])->toArray();
  }

  public function postprocessData(array $data): array {
    return $data;
  }

  public function renderJson() {
    try {

      $this->query = $this->prepareQuery();
      $data = $this->loadData();
      $data['data'] = $this->postprocessData($data['data']);

      return [
        'data' => $data,
        'title' => $tableTitle,
      ];
    } catch (\ADIOS\Core\Exceptions\GeneralException $e) {
      // TODO: Error
    }
  }

}
