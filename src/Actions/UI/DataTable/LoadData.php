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
class LoadData extends \ADIOS\Core\Action {
  
  private array $sessionParams = [];
  private array $data = [];
  private array $columns = [];

  private string $table = '';

  private $model = NULL;

  private int $recordsCount = 0;
  private int $selectedRecordCount = 0;

  private function setSessionParams(): void {
    $this->sessionParams = (array) $_SESSION[_ADIOS_ID]['views'][$this->params['uid']];
  }

  private function getSearchValue(): string {
    return $this->params['search']['value'] ?? '';
  }

  private function getOrderBy(): string {
    $orderBy = $this->params['order'][0];

    return $this->columns[$orderBy['column']]['data'] . ' ' . $orderBy['dir'];
  }

  private function getSearchInWhere(string $where): string {
    if ($this->getSearchValue() == '') return $where;

    $where .= ' and (';
    foreach($this->columns as $column) {
      if (is_numeric($column['data'])) continue;

      $where .= "{$this->table}.{$column['data']} LIKE '%" . ads($this->getSearchValue()) . "%' or "; 
    }

    return substr($where, 0, -3) . ')';
  }

  private function loadData(): void {
    $this->model = $this->adios->getModel($this->sessionParams['model']);
    $this->table = $this->adios->gtp . '_' . $this->model->sqlName;
    $this->columns = $this->params['columns'];

    $where = (empty($this->sessionParams['where']) ? 'TRUE' : $this->sessionParams['where']);
    $where = $this->getSearchInWhere($where);


    /*if (!empty($this->sessionParams['foreignKey'])) {
      $fkColumnName = $this->sessionParams['foreignKey'];
      $fkColumnDefinition = $this->columns[$fkColumnName] ?? NULL;
      if ($fkColumnDefinition !== NULL) {
        $tmpModel = $this->adios->getModel($fkColumnDefinition['model']);
        $where .= "
          and
            `lookup_{$tmpModel->getFullTableSQLName()}_{$fkColumnName}`.`id`
            = ".((int) $this->sessionParams['form_data']['id'])
        ;
      }
    }*/

    // having
    $having = (empty($this->sessionParams['having']) ? 'TRUE' : $this->sessionParams['having']);
    /*if (_count($this->columnsFilter)) {
      $having .= " and " . $this->model->tableFilterSqlWhere($this->columnsFilter);
    }
    if (_count($this->search)) {
      $having .= " and " . $this->model->tableFilterSqlWhere($this->search);
    }*/

    $orderBy = $this->sessionParams['orderBy'];
    $orderBy = $this->getOrderBy();
    $groupBy = $this->sessionParams['groupBy'];

    $tmpColumnSettings = $this->adios->db->tables[$this->table];
    //$this->adios->db->tables[$this->sessionParams['table']] = $this->columns;

    $this->recordsCount = $this->adios->db->count_all_rows($this->table, [
      'where' => $where,
      'having' => $having,
      'group' => $groupBy,
    ]);

    //if (_count($tmpColumnSettings)) {
    //  $this->adios->db->tables[$this->sessionParams['table']] = $tmpColumnSettings;
    //}
  

    //if ($this->sessionParams['page'] * $this->sessionParams['items_per_page'] > $this->table_item_count) {
    //  $this->sessionParams['page'] = floor($this->table_item_count / $this->sessionParams['items_per_page']) + 1;
    //}
    //$limit_1 = ($this->sessionParams['show_paging'] ? max(0, ($this->sessionParams['page'] - 1) * $this->sessionParams['items_per_page']) : '');
    //$limit_2 = ($this->sessionParams['show_paging'] ? $this->sessionParams['items_per_page'] : '');

    $getAllRowsParams = [
      'where' => $where,
      'having' => $having,
      'order' => $orderBy,
      'group' => $groupBy,
      'limit_start' => (int) $this->params['start'],
      'limit_end' => (int) $this->params['length']
    ];

    //if (is_numeric($limit_1)) $get_all_rows_params['limit_start'] = $limit_1;
    //if (is_numeric($limit_2)) $get_all_rows_params['limit_end'] = $limit_2;


    //$tmpColumnSettings = $this->adios->db->tables[$this->sessionParams['table']];
    //$this->adios->db->tables[$this->sessionParams['table']] = $this->columns;
    $this->data = $this->adios->db->get_all_rows(
      $this->table,
      $getAllRowsParams
    );

    $this->selectedRecordCount = count($this->data);
  }

  public function renderJSON() {
    $this->setSessionParams();

    $this->loadData();

    /** Enums */
    foreach ($this->data as $rowKey => $rowData) {
      foreach ($rowData as $colName => $colVal) {
        if (!empty($params['columnSettings'][$colName]['enum_values'])) {
          $data[$rowKey][$colName] = 
            $params['columnSettings'][$colName]['enum_values'][$colVal]
          ;
        }
      }
    }

    return  [
      'start'           => $this->params['start'],
      'recordsTotal'    => $this->selectedRecordCount,
      'recordsFiltered' => $this->recordsCount,
      'data'            => $this->data
    ];
  }
}