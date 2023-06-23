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
      'datasets' => null, # array of datasets, for examples visit https://www.chartjs.org/docs/latest/charts
                          # requires:
                          # either data property or model with data_columns property
                          # either labels or label_column property
    ], $params);

    if (empty($this->params['datasets'])) exit("UI/Chart: Data not provided. Please specify datasets property.");

    # Iterates over all datasets
    foreach ($this->params['datasets'] as &$dataset) {
      if ($dataset['model'] == '' && $dataset['data'] == '') exit("UI/Chart: Data not provided. Please specify model or data properties in datasets.");
      if ($dataset['data_columns'] == '') exit("UI/Chart: Data not provided. Please specify data_columns property in datasets.");
      if ($dataset['label_column'] == '' && $dataset['labels'] == '') exit("UI/Chart: Labels not provided. Please specify label_column or labels properties in datasets.");

      # If model isn't supplied, it is still possible that data property is provided
      if (isset($dataset['model'])) {
        /* ------------ */
        /* LABEL COLUMN */
        /* ------------ */

        # Gets the target model based on column specification
        $this->params['labels'] = $this
          ->adios
          ->getModel($dataset['model']);

        # Determines whether there is a condition which exact models should be filtered out
        if ($dataset['where'] != '') {
          $this->params['labels'] = $this->params['labels']
            ->where(...array_merge_recursive($dataset['where']));
        }

        # Gets the labels
        $this->params['labels'] = array_unique(
          array_merge_recursive(...
            $this
              ->params['labels']
              ->select($dataset['label_column']['column'])
              ->get()
              ->toArray())[$dataset['label_column']['column']] ?? []);

        # TODO: Replace the label if it is a foreign ID
        foreach ($this->params['labels'] as &$label);

        # Determines the limit of the chart data, that needs to be fetched
        $limit = match ($dataset['function']) {
          'count' => $dataset['limit'] ?? 10,
          default => count($this->params['labels']),
        };


        /* ------------ */
        /* DATA COLUMNS */
        /* ------------ */

        # Iterates over all data_columns
        foreach ($dataset['data_columns'] as $col_def) {
          $col = key($col_def); # retrieves the name of the data_column

            # Gets the target model based on column specification
            $dataset['data'] = $this->adios->getModel($dataset['model']);

            # Determines whether there is a condition which exact models should be filtered out
            if ($dataset['where'] != '') {
              $dataset['data'] = $dataset['data']->where(...array_merge_recursive($dataset['where']));
            }

            # Determines the function of the supplied data
            switch ($dataset['function']) {
              # Counts all found rows, useful if there are more rows than labels
              case 'count':
                $data = [];
                foreach($this->params['labels'] as $label_value) {
                  $data[] = $dataset['data']
                    ->where($dataset['label_column']['column'], "=", $label_value)
                    ->limit($limit)
                    ->count();
                }
                $dataset['data'] = $data;
                break;

              # Sums all found rows, useful if there are more rows than labels
              case 'sum':
                $data = [];
                foreach($this->params['labels'] as $label_value) {
                  $data[] = $dataset['data']
                    ->where($dataset['label_column']['column'], "=", $label_value)
                    ->limit($limit)
                    ->sum($col);
                }
                $dataset['data'] = $data;
                break;

              # Target column in found rows is imported as data
              default:
                $dataset['data'] = $dataset['data']
                  ->orderBy('id', 'desc')
                  ->limit($limit);

                $dataset['data'] = array_merge_recursive(...
                  $dataset['data']
                    ->select($col)
                    ->get()
                    ->toArray())[$col] ?? [];
            }
        }
      }
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
