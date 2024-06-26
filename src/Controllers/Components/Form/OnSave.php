<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\Form;

/**
 * @package Components\Controllers\Table
 */
class OnSave extends \ADIOS\Core\Controller {
  public bool $hideDefaultDesktop = true;

  function __construct(\ADIOS\Core\Loader $app, array $params = []) {
    parent::__construct($app, $params);
    $this->permission = $this->params['model'] . ':'. ($this->params['data']['id'] <= 0 ? 'Create' : 'Update');
  }

  public function renderJson(): ?array {
    try {
      $params = $this->params;

      $tmpModel = $this->app->getModel($params['model']);

      $tmpModel->recordSave($params['data']);

      return [
        'status' => 'success',
        'message' => isset($params['data']['id']) ? 'Záznam uložený' : 'Pridaný nový záznam'
      ];
    } catch (\ADIOS\Core\Exceptions\RecordSaveException $e) {
      http_response_code(422);

      $invalidInputs = json_decode($e->getMessage());

      return [
        'status' => 'error',
        'message' => 'Neboli vyplnené všetky povinné polia',
        'invalidInputs' => $invalidInputs
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
