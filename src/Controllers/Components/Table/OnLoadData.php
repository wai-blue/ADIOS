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

  public function renderJson() { 
    try {
      $params = $this->params;
      $pageLength = (int) $params['pageLength'] ?? 15;

      $tmpModel = $this->adios->getModel($this->params['model']);

      $tableTitle = $tmpModel->tableTitle;
      $tmpColumns = $tmpModel->getColumnsToShowInView('Table');

      $columnsToShowAsString = implode(', ', array_keys($tmpColumns));

      // TODO: Toto je pravdepodobne potencialna SQL injection diera. Opravit.
      $tmpQuery = $tmpModel->selectRaw($columnsToShowAsString);

      //LOOKUPS
      foreach ($tmpColumns as $columnName => $column) {
        if ($column['type'] == 'lookup') {
          $lookupModel = $this->adios->getModel($column['model']);

          $lookupSqlValue = "(" .
            str_replace("{%TABLE%}.", '', $lookupModel->lookupSqlValue())
            . ") as lookupSqlValue";

          $tmpQuery->with([$columnName => function ($query) use ($lookupSqlValue) {
            $query->selectRaw('id, ' . $lookupSqlValue);
          }]);
        }
      }

      // FILTER BY
      if (isset($params['filterBy'])) {
        // TODO
      }

      // WHERE
      if (isset($params['where']) && is_array($params['where'])) {
        foreach ($params['where'] as $where) {
          $tmpQuery->where($where[0], $where[1], $where[2]);
        }
      }

      // Search
      if (isset($params['search'])) {
        $tmpQuery->where(function ($query) use ($params, $tmpColumns) {
          foreach ($tmpColumns as $columnName => $column) {
            $query->orWhere($columnName, 'like', "%{$params['search']}%");
          }
        });
      }
      // ORDER BY
      if (isset($params['orderBy'])) {
        $tmpQuery->orderBy(
          $params['orderBy']['field'],
          $params['orderBy']['sort']);
      } else {
        $tmpQuery->orderBy('id', 'DESC');
      }

      if (isset($params['loadDataTag'])) {
        $tmpQuery = $tmpModel->modifyTableLoadDataQuery($tmpQuery, $params['loadDataTag']);
      }

      // Laravel pagination
      $data = $tmpQuery->paginate(
        $pageLength, ['*'], 
        'page', 
        $this->params['page']);

      return [
        'data' => $data,
        'title' => $tableTitle
      ];
    } catch (\ADIOS\Core\Exceptions\GeneralException $e) {
      // TODO: Error
    }
  }

}
