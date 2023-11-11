<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\Table;
use Illuminate\Pagination\Paginator;
/**
 * @package Components\Controllers\Table
 */
class OnLoadData extends \ADIOS\Core\Controller {
  public static bool $hideDefaultDesktop = true;

  public function renderJson() { 
    try {
      //$tmpModel = $this->adios->getModel($this->params['model']);
      $xxx = \App\Widgets\Bookkeeping\Books\Models\Vat::paginate(5);
var_dump($xxx); exit;
      return [
        'columns' => $tmpModel->columns(),
        'data' => $data
      ];
    } catch (\ADIOS\Core\Exceptions\GeneralException $e) {
      // TODO: Error
    }
  }

}
