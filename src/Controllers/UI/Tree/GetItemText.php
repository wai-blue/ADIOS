<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\UI\Tree;

/**
 * @package UI\Controllers\Tree
 */
class GetItemText extends \ADIOS\Core\Controller {
  public function render() {
    $model = $this->adios->getModel($this->params['model']);

    $tmp = reset($model
      ->selectRaw($model->getFullTableSqlName().".id")
      ->selectRaw("(".str_replace("{%TABLE%}", $model->getFullTableSqlName(), $model->lookupSqlValue).") as ___lookupSqlValue")
      ->where('id', (int) $this->params['id'])
      ->get()
      ->toArray()
    );

    return $tmp['___lookupSqlValue'];
  }
}