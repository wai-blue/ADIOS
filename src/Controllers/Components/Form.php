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
  private array $tagsLists = [];

  public \ADIOS\Core\Model $model;

  function __construct(\ADIOS\Core\Loader $adios, array $params = []) {
    parent::__construct($adios, $params);
    $this->permission = $this->params['model'] . ':Read';
  }

  private function setTagsLists(array &$data) {
    foreach ($this->tagsLists as $tagListRelationship => $tagListData) {
      $data[$tagListRelationship] = [
        'selected' => $data[$tagListRelationship],
        'list' => $tagListData
      ];
    }
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

    foreach ($tmpColumns as $tmpColumnName => $tmpColumnDefinition) {
      if (isset($tmpColumnDefinition['relationship']) && $tmpColumnDefinition['type'] == 'tags') {
        $query->with($tmpColumnDefinition['relationship']);
        $this->tagsLists[$tmpColumnDefinition['relationship']] = $query->first()->roles()->getRelated()->get();
      }
    }

    return $query;
  }

  public function loadData() {
    $this->model = $this->adios->getModel($this->params['model']);

    $data = [];

    if (isset($this->params['id']) && (int) $this->params['id'] > 0) {
      $this->query = $this->prepareDataQuery();
      $data = $this->query->find($this->params['id']);
      //var_dump($data->roles()->pluck('_user_has_roles.id_role')->toArray()); exit;

      $this->setTagsLists($data);
    }

    return $data;
  }


  public function getParams() {
    try {
      $params = $this->params;
      $model = $this->adios->getModel($this->params['model']);
      $params = \ADIOS\Core\HelperFunctions::arrayMergeRecursively($params, $model->formParams ?? []);

      $params['columns'] = \ADIOS\Core\HelperFunctions::arrayMergeRecursively($params['columns'] ?? [], $model->columns());
      $params['columns'] = array_filter($params['columns'], function($column) {
        return ($column['show'] ?? FALSE);
      });

      $params['canRead'] = $this->adios->permissions->has($this->params['model'] . ':Read');
      $params['canCreate'] = $this->adios->permissions->has($this->params['model'] . ':Create');
      $params['canUpdate'] = $this->adios->permissions->has($this->params['model'] . ':Update');
      $params['canDelete'] = $this->adios->permissions->has($this->params['model'] . ':Delete');
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



  public function renderJson() {
    if (empty($this->params['view'])) {
      try {
        return [
          'params' => ($this->params['returnParams'] ? $this->getParams() : []),
          'data' => ($this->params['returnData'] ? $this->loadData() : []),
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
    } else {
      return null;
    }
  }

  public function prepareViewAndParams(): array {

    // configure view to be used
    $this->view = $this->params['view'];

    // build-up view params
    unset($this->params['view']);
    $this->viewParams = array_merge(
      $this->params,
      [
        'params' => $this->getParams(),
        'data' => $this->loadData(),
      ]
    );

    // return view and viewParams for ADIOS Twig renderer
    return [$this->view, $this->viewParams];
  }


}
