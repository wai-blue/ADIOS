<?php


namespace ADIOS\Core\Views;

class Chart extends \ADIOS\Core\View {

  public string $twigTemplate = "Core/UI/Chart";
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
      'type' => null,     # pie, bar, line, radar, bubble, doughnut, polarArea, radar, scatter
      'labels' => null,   # array of labels
      'datasets' => null, # array of datasets, for examples visit https://www.chartjs.org/docs/latest/charts
                          #

      'datatableName' => null,
      'defaultValues' => [],
      'columns' => [],
      'data' => [],
      'displayStart' => 0,
      'search' => '',
      'style' => 'padding:10px',
    ], $params);

    /*
    if ($this->params['model'] == '') {
      exit("UI/Chart: Don't know what model to work with.");
      return;
    }

    if (empty($this->params['columnSettings']) && $this->params['model'] != null) {
      $tmpModel = $this->adios->getModel($this->params['model']);

      $this->params['columnSettings'] = $this->adios->db->tables[
        "{$this->adios->gtp}_{$tmpModel->sqlName}"
      ];
    }

    $this->saveParamsToSession($this->params['datatableName'], $this->params);

    if (empty($this->params['data'])) {
      $this->model = $this->adios->getModel($this->params['model']);

      $this->params['data'] = array_values($this->model->getAll());
    }
    */
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
