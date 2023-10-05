<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\UI\Table;

/**
 * @package UI\Controllers\Table
 */
class Copy extends \ADIOS\Core\Controller {
  public function render($params = []) {

    $model = $this->adios->getModel($params['model']);

    if (is_numeric($params['id'])) {
      $ids = [(int) $params['id']];
    } else {
      $ids = explode(',', $params['ids']);
    }

    $this->adios->db->startTransaction();

    foreach ($ids as $id) {
      $tmpResult = $model->copyRow((int) $id);
    }

    $this->adios->db->commit();

    return _count($ids) == 1 ? $tmpResult : 1;
  }
}
