<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\Inputs\Tags;

use Illuminate\Database\QueryException;

/**
 * @package Components\Controllers\Tags
 */
class Delete extends \ADIOS\Core\Controller {
  public bool $hideDefaultDesktop = true;

  function __construct(\ADIOS\Core\Loader $app, array $params = []) {
    parent::__construct($app, $params);
    //$this->permission = $this->params['model'] . ':Read';
  }

  public function renderJson(): ?array { 
    try {
      $id = (int) $this->params['id'];
      $model = (string) $this->params['model'];
      $junction = (string) $this->params['junction'];

      // Validate required params
      if ($model == '') throw new \Exception("Unknown model");
      if ($junction == '') throw new \Exception("Unknown junction model");
      if ($id == 0) throw new \Exception("Unknown id");

      $tmpModel = $this->app->getModel($model);
      $junctionData = $tmpModel->junctions[$junction] ?? null;

      if ($junctionData == null) {
        throw new \Exception("Junction {$junction} in {$model} not found");
      }

      $junctionModel = $this->app->getModel($junctionData['junctionModel']);
      $junctionOptionKeyColumn = $junctionModel->columns()[$junctionData['optionKeyColumn']];
      $junctionOptionKeyModel = $this->app->getModel($junctionOptionKeyColumn['model']);

      $junctionItemsToDelete = $junctionModel->where($junctionData['optionKeyColumn'], $id)
        ->get();

      foreach ($junctionItemsToDelete as $junctionItem) {
        $junctionModel->find($junctionItem->id)->delete();
      }

      $junctionOptionKeyModel->find($id)->delete();

      return [];
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
