<?php


namespace ADIOS\Core\ViewsWithController;

use ADIOS\Core\Loader;
use ADIOS\Core\Model;
use ADIOS\Core\View;

class Chart extends View
{

  public string $twigTemplate = "ADIOS/Core/Components/Chart";
  private ?Model $model = null;

  public function __construct(?Loader $adios, array $params = []) {
    $this->adios = $adios;

    $this->params = parent::params_merge([
      'type' => null, # pie, bar, line, radar, bubble, doughnut, polarArea, radar, scatter
      'datasets' => null, # { data/model: .., dataColumns: .., labelColumn/labels: .., limit: .., function: ..}
    ], $params);

    if (empty($this->params['datasets'])) {
      exit("Components/Chart: Data not provided. Please specify datasets property.");
    }

    foreach ($this->params['datasets'] as &$dataset) {
      if ($dataset['model'] == '' && $dataset['data'] == '') {
        exit("Components/Chart: Data not provided. Please specify model or data properties in datasets.");
      }

      if ($dataset['labelColumn'] == '' && $dataset['labels'] == '') {
        exit("Components/Chart: Labels not provided. Please specify labelColumn or labels properties in datasets.");
      }

      if ($dataset['model'] != '') {

        if ($dataset['dataColumns'] == '') {
          exit("Components/Chart: Data not provided. Please specify dataColumns property in datasets.");
        }

        # Determines the amount of chart data rows, that need to be fetched
        $limit = match ($dataset['function']) {
          'count' => $dataset['limit'] ?? 10,
          default => -1,
        };

        $labelQuery = $this->adios
          ->getModel($dataset['model'])
        ;

        if ($dataset['where'] != '') {
          $labelQuery = $labelQuery
            ->where(...array_merge_recursive($dataset['where']))
          ;
        }

        $labelQuery = $labelQuery
          ->select($dataset['labelColumn']['column'])
          ->get()
        ;

        $this->params['labelModel'] = array_unique(
          array_merge_recursive(
            ...$labelQuery->toArray()
          )[$dataset['labelColumn']['column']] ?? [])
        ;

        # Fills in the value of the limit, if it could not be determined before
        if ($limit = -1) {
          $limit = count($this->params['labelModel']);
        }

        # Data processing
        foreach ($dataset['dataColumns'] as $col_def) {
          $col = key($col_def);

          $dataQuery = $this->adios->getModel($dataset['model']);

          if ($dataset['where'] != '') {
            $dataQuery = $dataQuery->where(...array_merge_recursive($dataset['where']));
          }

          switch ($dataset['function']) {
            # Counts all found rows, useful if there are more rows than labels
            case 'count':
              $data = [];

              foreach ($this->params['labelModel'] as $label_value) {
                $data[] = $dataQuery
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
              foreach ($this->params['labelModel'] as $label_value) {
                $data[] = $dataQuery
                  ->where($dataset['labelColumn']['column'], "=", $label_value)
                  ->limit($limit)
                  ->sum($col)
                ;
              }

              $dataset['data'] = $data;
            break;

            # Target column in found rows is imported as data
            default:
              $dataQuery = $dataQuery
                ->orderBy('id', 'desc')
                ->limit($limit)
              ;

              $dataset['data'] = array_merge_recursive(...$dataQuery
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
      if ($this->params['labelModel'] == '') {
        $this->params['labelModel'] = $dataset['labels'];
      }
    }

    $this->params['uid'] = $this->adios->uid;
  }

  public function getTwigParams(): array
  {
    return $this->params;
  }
}
