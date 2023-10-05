<?php

/*
    This file is part of ADIOS Framework.

    This file is published under the terms of the license described
    in the license.md file which is located in the root folder of
    ADIOS Framework package.
*/

namespace ADIOS\Controllers\UI\DataTable;

use \Illuminate\Database\Eloquent\Builder;

/**
 * @package UI\Controllers\DataTable
 */
class LoadData extends \ADIOS\Core\Controller {
    
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

    private function getRelationshipsWhere(Builder $builder): Builder {
        if ($this->getSearchValue() != '') {
            foreach ($this->relationships as $relationship) {
                $builder = $builder->orWhereHas($relationship, function ($query) {
                    return $query->where('name', 'LIKE', '%' . $this->getSearchValue() . '%');
                });
            }
        }

        return $builder;
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

    private function formatInputsTypes(): void {
        foreach ($this->data as $rowKey => $rowData) {
            foreach ($rowData as $colName => $colVal) {
                $columnType = $this->sessionParams['columnSettings'][$colName]['type'];

                switch ($columnType) {
                    case 'boolean':
                        $this->data[$rowKey][$colName] = "
                            <div class='text-center w-100'>
                                <input 
                                    class='form-check-input' 
                                    type='checkbox'
                                    value='{$this->data[$rowKey][$colName]}'
                                    onclick='{$this->sessionParams['datatableName']}_update(
                                        \"{$this->data[$rowKey]['id']}\",
                                        \"{$colName}\",
                                        + $(this).is(\":checked\")
                                    )'
                                    " . ($this->data[$rowKey][$colName] ? "checked='checked'" : "") . "
                                />
                            </div>";
                    break;
                    case 'lookup':
                        foreach ($this->relationships as $relationshipColName => $relationshipVal) {
                            $lookupColumn = $this->sessionParams['columnSettings'][$relationshipColName]['lookupColumn'];
                            $this->data[$rowKey][$relationshipColName] = $rowData[
                                $this->relationships[$relationshipColName]][$lookupColumn];
                        }
                    break;
                }

                if (!empty($this->sessionParams['columnSettings'][$colName]['enum_values'])) {
                    $this->data[$rowKey][$colName] = 
                        $this->sessionParams['columnSettings'][$colName]['enum_values'][$colVal];
                }
            }
        }
    }

    private function loadData(): void {
        $this->model = $this->adios->getModel($this->sessionParams['model']);
        $this->table = $this->model->getFullTableSqlName();
        $this->columns = $this->params['columns'];

        $where = (empty($this->sessionParams['where']) ? 'TRUE' : $this->sessionParams['where']);
        $where = $this->getSearchInWhere($where);

        //$having = (empty($this->sessionParams['having']) ? 'TRUE' : $this->sessionParams['having']);

        $orderBy = $this->sessionParams['orderBy'];
        $orderBy = $this->getOrderBy();
        
        //$groupBy = $this->sessionParams['groupBy'];

        $this->setRelationships();

        $builderAllRecords = $this->model->with(array_values($this->relationships))
            ->whereRaw($where);
        
        $builderAllRecords = $this->getRelationshipsWhere($builderAllRecords);

        $this->recordsCount = $builderAllRecords->get()
            ->count();

        $builder = $this->model->with(array_values($this->relationships))
            ->whereRaw($where);
        
        $builder = $this->getRelationshipsWhere($builder);

        $this->data = $builder->orderByRaw($orderBy)
            ->skip((int) $this->params['start'])
            ->take((int) $this->params['length'])
            ->get()
            ->toArray();

        $this->selectedRecordCount = count($this->data);
    }

    public function renderJson() {
        $this->setSessionParams();

        $this->loadData();
        $this->formatInputsTypes();

        return    [
            'start'            => $this->params['start'],
            'recordsTotal'     => $this->selectedRecordCount,
            'recordsFiltered'  => $this->recordsCount,
            'data'             => $this->data
        ];
    }
}