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
class Data extends \ADIOS\Core\Controller {
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
        $tmpColumns = $tmpModel->columns();//getColumnsToShowInView('Form');

        foreach ($tmpColumns as $tmpColumnName => $tmpColumnDefinition) {
          if (!isset($tmpColumnDefinition['relationship'])) {
            $columnsToShowAsString .= ($columnsToShowAsString == '' ? '' : ', ') . $tmpColumnName;
          }
        }

        $query = $tmpModel->selectRaw($columnsToShowAsString);

        foreach ($tmpColumns as $tmpColumnName => $tmpColumnDefinition) {
          if (isset($tmpColumnDefinition['relationship']) && $tmpColumnDefinition['type'] == 'tags') {
            $query->with($tmpColumnDefinition['relationship']);
            $details[$tmpColumnDefinition['relationship']] = $query->first()->roles()->getRelated()->get()->toArray();
          }
        }

        $inputs = $query->find($this->params['id'])->toArray();

        foreach ($details as $key => $value) {
          $inputs[$key] = [
            'values' => $inputs[$key],
            'all' => $value
          ];
        }
      }

      return [
        'inputs' => $inputs
      ];
    } catch (QueryException $e) {
      http_response_code(500);

      return [
        'status' => 'error',
        'message' => $e->getMessage() 
      ];
    } catch (\Exception $e) {
      http_response_code(400);

      return [
        'status' => 'error',
        'message' => $e->getMessage() 
      ];
    }
  }

}