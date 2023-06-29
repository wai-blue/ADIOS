<?php


namespace ADIOS\Core\Views;

class Chart extends \ADIOS\Core\View {

  public string $twigTemplate = "Core/UI/Chart";
  private ?\ADIOS\Core\Model $model = null;

  public function __construct(?\ADIOS\Core\Loader $adios, array $params = []) {
    $this->adios = $adios;

    $this->params = parent::params_merge([
      'type' => null, # pie, bar, line, radar, bubble, doughnut, polarArea, radar, scatter
      'datasets' => null,
      'function' => null,
    ], $params);

    if (empty($this->params['datasets'])) {
      exit("UI/Chart: Data not provided. Please specify datasets property.");
    }

    foreach ($this->params['datasets'] as &$dataset) {
      if ($dataset['model'] == '' && $dataset['data'] == '') {
        exit("UI/Chart: Data not provided. Please specify model or data properties in datasets.");
      }

      if ($dataset['dataColumns'] == '') {
        exit("UI/Chart: Data not provided. Please specify dataColumns property in datasets.");
      }

      if ($dataset['labelColumn'] == '' && $dataset['labels'] == '') {
        exit("UI/Chart: Labels not provided. Please specify labelColumn or labels properties in datasets.");
      }

      $this->params['labelModel'] = $this->adios
        ->getModel($dataset['model'])
      ;

      if ($dataset['where'] != '') {
        $this->params['labelModel'] = $this->params['labelModel']
          ->where(...array_merge_recursive($dataset['where']))
        ;
      }

      $this->params['labelModel'] = array_unique(
        array_merge_recursive(...
          $this
            ->params['labelModel']
            ->select($dataset['labelColumn']['column'])
            ->get()
            ->toArray())[$dataset['labelColumn']['column']] ?? [])
          ;

      # Determines the limit of the chart data, that needs to be fetched
      $limit = match ($dataset['function']) {
        'count' => $dataset['limit'] ?? 10,
        default => count($this->params['labelModel']),
      };

      # Data processing
      foreach ($dataset['dataColumns'] as $col_def) {
        $col = key($col_def);

        $dataset['data'] = $this->adios->getModel($dataset['model']);

        if ($dataset['where'] != '') {
          $dataset['data'] = $dataset['data']->where(...array_merge_recursive($dataset['where']));
        }

        switch ($dataset['function']) {
          # Counts all found rows, useful if there are more rows than labels
          case 'count':
            $data = [];

            foreach($this->params['labelModel'] as $label_value) {
              $data[] = $dataset['data']
                ->where($dataset['labelColumn']['column'], "=", $label_value)
                ->limit($limit)
                ->count()
              ;
            }

            $dataset['data'] = $data;
          break;

          # Sums all found rows, useful if there are more rows than labels
          case 'sum':
            $data = [];
            foreach($this->params['labelModel'] as $label_value) {
              $data[] = $dataset['data']
                ->where($dataset['labelColumn']['column'], "=", $label_value)
                ->limit($limit)
                ->sum($col)
              ;
            }

            $dataset['data'] = $data;
          break;

          # Target column in found rows is imported as data
          default:
            $dataset['data'] = $dataset['data']
              ->orderBy('id', 'desc')
              ->limit($limit)
            ;

            $dataset['data'] = array_merge_recursive(...$dataset['data']
              ->select($col)
              ->get()
              ->toArray())[$col] ?? []
            ;
        }
      }

      # Lastly, replace the label if it is a foreign ID
      if ($dataset['labelColumn']['lookup']) {
        foreach ($this->params['labelModel'] as &$label) {
          $labelModel = $this->adios->getModel($dataset['labelColumn']['model']);

          $labelData = $labelModel
            ->select($dataset['labelColumn']['lookup'])
            ->where('id', '=', $label)
            ->get()
            ->toArray()
          ;

          $label = $labelData[0][$dataset['labelColumn']['lookup']];
        }
      }
    }

    $this->params['uid'] = $this->adios->uid;
  }

  public function getTwigParams(): array {
    return $this->params;
  }
}
