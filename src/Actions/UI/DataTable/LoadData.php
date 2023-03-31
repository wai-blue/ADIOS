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
    private array $relationships = [];

    private string $table = '';

    private ?\ADIOS\Core\Model $model = null;

    private int $recordsCount = 0;
    private int $selectedRecordCount = 0;

    private function setSessionParams(): void {
        $this->sessionParams = (array) $_SESSION[_ADIOS_ID]['views'][$this->params['uid']];

        $_SESSION[_ADIOS_ID]['views'][$this->params['uid']]['itemsPerPage'] = 
            $this->params['length'];

        $_SESSION[_ADIOS_ID]['views'][$this->params['uid']]['displayStart'] = 
            $this->params['start'];

        $_SESSION[_ADIOS_ID]['views'][$this->params['uid']]['search'] = 
            $this->getSearchValue();
    }

    private function setRelationships(): void {
        foreach ($this->sessionParams['columnSettings'] as $columnName => $columnSetting) {
            if ($columnSetting['type'] == 'lookup') {
                $relationship = strtolower(end(explode('/', $columnSetting['model'])));

                if (method_exists($this->model, $relationship)) {
                    $this->relationships[$columnName] = $relationship;
                }
            }
        }
    }
    
    private function getSearchValue(): string {
        return $this->params['search']['value'] ?? '';
    }

    private function getOrderBy(): string {
        $orderBy = $this->params['order'][0];

        return $this->columns[$orderBy['column']]['data'] . ' ' . $orderBy['dir'];
    }

    private function getRelationshipsData(): array {
        foreach ($this->relationships as $relationshipColName => $relationshipVal) {
            $lookupColumn = $this->sessionParams['columnSettings'][$relationshipColName]['lookupColumn'];

            foreach ($this->data as $recordKey => $record) {
                $this->data[$recordKey][$relationshipColName] = $record[
                    $this->relationships[$relationshipColName]][$lookupColumn];
            }
        }
       
        return $this->data;
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

    private function addEnums(): void {
        foreach ($this->data as $rowKey => $rowData) {
            foreach ($rowData as $colName => $colVal) {
                if (!empty($this->sessionParams['columnSettings'][$colName]['enum_values'])) {
                    $this->data[$rowKey][$colName] = 
                        $this->sessionParams['columnSettings'][$colName]['enum_values'][$colVal];
                }
            }
        }
    }

    private function loadData(): void {
        $this->model = $this->adios->getModel($this->sessionParams['model']);
        $this->table = $this->adios->gtp . '_' . $this->model->sqlName;
        $this->columns = $this->params['columns'];

        $where = (empty($this->sessionParams['where']) ? 'TRUE' : $this->sessionParams['where']);
        $where = $this->getSearchInWhere($where);

        //$having = (empty($this->sessionParams['having']) ? 'TRUE' : $this->sessionParams['having']);

        $orderBy = $this->sessionParams['orderBy'];
        $orderBy = $this->getOrderBy();
        
        //$groupBy = $this->sessionParams['groupBy'];

        $this->setRelationships();

        $this->recordsCount = $this->model->whereRaw($where)
            ->get()
            ->count();

        $this->data = $this->model->with(array_values($this->relationships))
            ->whereRaw($where)
            ->orderByRaw($orderBy)
            ->skip((int) $this->params['start'])
            ->take((int) $this->params['length'])
            ->get()
            ->toArray();

        $this->data = $this->getRelationshipsData();

        $this->selectedRecordCount = count($this->data);
    }

    public function renderJSON() {
        $this->setSessionParams();

        $this->loadData();
        $this->addEnums();

        return    [
            'start'            => $this->params['start'],
            'recordsTotal'     => $this->selectedRecordCount,
            'recordsFiltered'  => $this->recordsCount,
            'data'             => $this->data
        ];
    }
}