<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Api;

class Get extends \ADIOS\Core\Controller {
  public bool $hideDefaultDesktop = true;

  public function renderJson(): ?array {
    $record = [];

    $model = $this->params['model'] ?? '';
    if (!empty($model)) {
      
      $query = $this->app->db->select($this->app->getModel($model))->columns(['*']);

      if (isset($this->params['id'])) {
        $query = $query->where([['id', '=', (int) $this->params['id']]]);
      } else if (isset($this->params['column'])) {
        $query = $query->where([[$this->params['column'], '=', $this->params['value'] ?? '']]);
      }


      $record = $query->fetchOne();
    } else {
      $record = [];
    }

    return $record;
  }
}
