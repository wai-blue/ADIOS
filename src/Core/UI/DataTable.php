<?php


namespace ADIOS\Core\UI;

class DataTable extends \ADIOS\Core\UI\View {

  private array $tableColumnsEnums = [];

  public string $twigTemplate = "Core/UI/DataTable";
  private ?\ADIOS\Core\Model $model = null;

  /**
   * __construct
   *
   * @param  mixed $adios
   * @param  mixed $params
   * @return void
   */
  public function __construct(&$adios, $params = null) {
    $this->adios = &$adios;

    $this->params = parent::params_merge([
      'datatableName' => null,
      'model' => null,
      'loadDataAction' => 'UI/DataTable/LoadData',
      'refreshAction' => 'UI/DataTable/Refresh',
      'updateAction' => 'UI/DataTable/Update',
      'defaultValues' => [],
      'columns' => [],
      'data' => [],
      'showAddButton' => true,
      'showDeleteButton' => true,
      'itemsPerPage' => 10,
      'displayStart' => 0,
      'style' => 'padding:10px',
      'tooltip' => 'Editovať kliknutím',
      'placeholder' => 'Editovať kliknutím'
    ], $params);

    if ($this->params['model'] == '') {
      exit("UI/DataTable: Don't know what model to work with.");
      return;
    }

    if ($this->params['refresh'] == false) {
      $this->params['datatableName'] = 
        ($this->params['datatableName'] ?? $this->adios->uid) 
        . '_datatable'
      ;

      $this->params['loadDataActionFullUrl'] = $this->adios->config['url'] . '/' .
        $this->params['loadDataAction'] . '?uid=' . $this->params['datatableName']
      ;
    }

    if (empty($this->params['columnSettings']) && $this->params['model'] != null) {
      $tmpModel = $this->adios->getModel($this->params['model']);

      $this->params['columnSettings'] = $this->adios->db->tables[
        "{$this->adios->gtp}_{$tmpModel->sqlName}"
      ];

      foreach ($this->params['columnSettings'] as $columnName => $column) {
        if ($columnName != '%%table_params%%') {
  
          if ($columnName == 'id') continue;

          if (isset($column['enum_values'])) {
            $tmpEnums = [];
            
            foreach ($column['enum_values'] as $enumVal) {
              $tmpEnums[$enumVal] = $enumVal;
            }

            $column['enum_values'] = $tmpEnums;
          }

          $this->params['columns'][] = [
            'adiosColumn' => $column,
            'title' => $column['title'],
            'data' => $columnName,
            'editorType' => isset($column['enum_values']) ? 'select' : 'text'
          ];
        }
      }

      if ($this->params['showDeleteButton']) {
        $this->params['columns'][] = [
          'defaultContent' => '
            <button
              onclick="'. $this->params['datatableName'] . '_delete_row(this)"
            >
              <i class="fa fa-trash"></i class="fa fa-trash">
            </button>
          ',
          'orderable' => false,
          'className' => 'dt-delete-button dt-center'
        ];
      }
    }

    $this->saveParamsToSession($this->params['datatableName'], $this->params);

    if (empty($this->params['data'])) {
      $this->model = $this->adios->getModel($this->params['model']);

      $this->params['data'] = array_values($this->model->getAll());
    }
  }

  public function getTwigParams(): array {
    return array_merge(
      $this->params,
      [
        'ui' => $this->adios->ui
      ]
    );
  }
}