<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/


namespace ADIOS\Controllers\Components\Form;

use Illuminate\Database\QueryException;

/**
 * @package Components\Controllers\Table
 */
class OnDelete extends \ADIOS\Core\Controller {
  public bool $hideDefaultDesktop = true;

  function __construct(\ADIOS\Core\Loader $app, array $params = []) {
    parent::__construct($app, $params);
    $this->permission = $this->params['model'] . ':Delete';
  }

  public function renderJson(): ?array {
    try {
      $params = $this->params;

      $tmpModel = $this->app->getModel($params['model']);
      
      $tmpModel->eloquent->find($params['id'])->delete();

      return [
        'status' => 'success'
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
