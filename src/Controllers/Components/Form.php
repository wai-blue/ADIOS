<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components;

/**
 * @package Components\Controllers\Form
 */
class Form extends \ADIOS\Core\Controller {
  protected ?\Illuminate\Database\Eloquent\Builder $query = null;

  public \ADIOS\Core\Model $model;

  function __construct(\ADIOS\Core\Loader $app, array $params = []) {
    parent::__construct($app, $params);
    $this->permission = $this->params['model'] . ':Read';
  }

  public function prepareDataQuery(): \Illuminate\Database\Eloquent\Builder {
    $columnsToShowAsString = '';
    $tmpColumns = $this->model->columns();//getColumnsToShowInView('Form');

    foreach ($tmpColumns as $tmpColumnName => $tmpColumnDefinition) {
      if (!isset($tmpColumnDefinition['relationship'])) {
        $columnsToShowAsString .= ($columnsToShowAsString == '' ? '' : ', ') . $tmpColumnName;
      }
    }

    // TODO: Toto je pravdepodobne potencialna SQL injection diera. Opravit.
    $query = $this->model->selectRaw($columnsToShowAsString);
    $query = $query->where('id', $this->params['id']);

    //foreach ($tmpColumns as $tmpColumnName => $tmpColumnDefinition) {
    //  if (isset($tmpColumnDefinition['relationship']) && $tmpColumnDefinition['type'] == 'tags') {
    //    $query->with($tmpColumnDefinition['relationship']);
    //    $this->tagsLists[$tmpColumnDefinition['relationship']] = $query->first()->roles()->getRelated()->get();
    //  }
    //}

    return $query;
  }

  public function loadRecord() {
    $this->model = $this->app->getModel($this->params['model']);

    $data = [];

    if (isset($this->params['id']) && (int) $this->params['id'] > 0) {
      $this->query = $this->prepareDataQuery();
      $data = $this->query->first()->toArray();
    }

    return $data;
  }


  public function getParams() {
    try {
      $params = $this->params;
      unset($params['returnParams']);
      unset($params['__IS_AJAX__']);

      $model = $this->app->getModel($this->params['model']);
      $params = \ADIOS\Core\Helper::arrayMergeRecursively($params, $model->formParams ?? []);

      $params['columns'] = \ADIOS\Core\Helper::arrayMergeRecursively($params['columns'] ?? [], $model->columns());
      $params['columns'] = \ADIOS\Core\Helper::arrayMergeRecursively($params['columns'] ?? [], $model->inputs());
      // $params['columns'] = array_filter($params['columns'], function($column) {
      //   return ($column['show'] ?? FALSE);
      // });

      $params['canRead'] = $this->app->permissions->granted($this->params['model'] . ':Read');
      $params['canCreate'] = $this->app->permissions->granted($this->params['model'] . ':Create');
      $params['canUpdate'] = $this->app->permissions->granted($this->params['model'] . ':Update');
      $params['canDelete'] = $this->app->permissions->granted($this->params['model'] . ':Delete');
      $params['readonly'] = !($params['canUpdate'] || $params['canCreate']);

      $params['folderUrl'] = $model->getFolderUrl();

      return $params;
    } catch (\Exception $e) {
      http_response_code(400);

      return [
        'status' => 'error',
        'message' => $e->getMessage() 
      ];
    }
  }


  public function saveRecord(): ?array {
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

  public function renderJson(): ?array {
    try {
      $return = [];
      switch ($this->params['action']) {
        case 'getParams': return $this->getParams(); break;
        case 'loadRecord': return $this->loadRecord(); break;
        case 'saveRecord': return $this->saveRecord(); break;
      }
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

  // public function prepareViewParams() {
  //   parent::prepareViewParams();

  //   // build-up view params
  //   unset($this->params['view']);
  //   $this->viewParams = array_merge(
  //     $this->app->params,
  //     [
  //       'params' => $this->getParams(),
  //       'data' => $this->loadRecord(),
  //     ]
  //   );
  // }


}
