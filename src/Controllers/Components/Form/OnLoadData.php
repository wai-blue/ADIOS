<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\Form;

use ADIOS\Core\DB\DataTypes\DataTypeColor;

/**
 * @package Components\Controllers\Form
 */
class OnLoadData extends \ADIOS\Core\Controller {
  public static bool $hideDefaultDesktop = true;

  function __construct(\ADIOS\Core\Loader $adios, array $params = []) {
    parent::__construct($adios, $params);
    $this->permissionName = $this->params['model'] . ':Read';
  }

  public function renderJson() { 
    try {
      $tmpModel = $this->adios->getModel($this->params['model']);

      $inputs = [];
      $details = [];
      if (isset($this->params['id']) && (int) $this->params['id'] > 0) {
        $columnsToShowAsString = '';
        $tmpColumns = $tmpModel->getColumnsToShowInView('Form');

        foreach ($tmpColumns as $tmpColumnName => $tmpColumnDefinition) {
          if (!isset($tmpColumnDefinition['relationship'])) {
            $columnsToShowAsString .= ($columnsToShowAsString == '' ? '' : ', ') . $tmpColumnName;
          }
        }

        $query = $tmpModel->selectRaw($columnsToShowAsString);

        foreach ($tmpColumns as $tmpColumnName => $tmpColumnDefinition) {
          if (isset($tmpColumnDefinition['relationship']) && $tmpColumnDefinition['type'] == 'tags') {
            $query->with($tmpColumnDefinition['relationship']);
            $details[$tmpColumnDefinition['relationship'] . '_all'] = $query->first()->roles()->getRelated()->get()->toArray();
          }
        }

        $inputs = array_merge($query->find($this->params['id'])->toArray(), $details);
      }

      return [
        'inputs' => $inputs
      ];
    } catch (\ADIOS\Core\Exceptions\GeneralException $e) {
      // TODO: Error
    }
  }

}
