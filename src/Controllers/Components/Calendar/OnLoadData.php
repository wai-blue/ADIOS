<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\Calendar;

/**
 * @package Components\Controllers\Calendar
 */
class OnLoadData extends \ADIOS\Core\Controller {
  public static bool $hideDefaultDesktop = true;

  public function renderJson() { 
    try {
      $tmpModel = $this->adios->getModel($this->params['model']);
      
      $data = $tmpModel->getAll();
      
      // TODO Implementovat logiku pre zobrazovaie v kalen.
      //var_dump($data); exit;

      return [
        'data' => [
          [[75, 15, '#ff8c8c', 'Muži'], [60, 0, '', ''], [180, 15, '#ffffa5', 'TT vs BA'], [45, 15, [['#ff8c8c', '7 roc'], ['#1f4c8c', '8 roc']]], [60, 15, '#bbffce', 'VUJE'], [90, 0, '', ''], [60, 15, '#bbffce', 'Deti 1-8'], [75, 15, '#ff8c8c', 'Muži'], [30, 15, '#bbffce', 'Deti 1-8'], [60, 0, '', ''], [165, 15, '#a8b3ff', 'Verejko']],
          [[60, 0, '', ''], [165, 15, '#a8b3ff', 'Verejko'], [75, 15, '#ff8c8c', 'Muži'], [60, 0, '', ''], [180, 15, '#ffffa5', 'TT vs BA'], [45, 15, '#ff8c8c', 'Kraso'], [60, 15, '#bbffce', 'VUJE'], [90, 0, '', ''], [60, 15, '#bbffce', 'Deti 1-8'], [75, 15, '#ff8c8c', 'Muži'], [30, 15, '#bbffce', 'Deti 1-8']],
          [[60, 15, '#bbffce', 'VUJE'], [90, 0, '', ''], [60, 15, '#bbffce', 'Deti 1-8'], [75, 15, '#ff8c8c', 'Muži'], [30, 15, '#bbffce', 'Deti 1-8'], [60, 0, '', ''], [165, 15, '#a8b3ff', 'Verejko'], [75, 15, '#ff8c8c', 'Muži'], [60, 0, '', ''], [180, 15, '#ffffa5', 'TT vs BA'], [45, 15, '#ff8c8c', 'Kraso']],
          [[60, 15, '#bbffce', 'Deti 1-8'], [75, 15, [['#ff8c8c', '7 roc'], ['#1f4c8c', '8 roc'], ['#13cc43', '9 roc']]], [30, 15, '#bbffce', 'Deti 1-8'], [60, 0, '', ''], [165, 30, '#a8b3ff', 'Verejko'], [60, 15, '#ff8c8c', 'Muži'], [60, 0, '', ''], [180, 15, '#ffffa5', 'TT vs BA'], [45, 15, '#ff8c8c', 'Kraso'], [60, 15, '#bbffce', 'VUJE'], [90, 0, '', '']],
          [[90, 0, '', ''], [60, 15, '#bbffce', 'Deti 1-8'], [75, 15, '#ff8c8c', 'Muži'], [30, 15, '#bbffce', 'Deti 1-8'], [60, 15, '#bbffce', 'VUJE'], [60, 0, '', ''], [165, 15, '#a8b3ff', 'Verejko'], [75, 15, '#ff8c8c', 'Muži'], [60, 0, '', ''], [180, 15, '#ffffa5', 'TT vs BA'], [45, 15, '#ff8c8c', 'Kraso']],
          [[180, 15, '#ffffa5', 'TT vs BA'], [75, 15, '#ff8c8c', 'Muži'], [60, 0, '', ''], [45, 15, '#ff8c8c', 'Kraso'], [60, 15, '#bbffce', 'VUJE'], [90, 0, '', ''], [60, 15, '#bbffce', 'Deti 1-8'], [75, 15, '#ff8c8c', 'Muži'], [30, 15, '#bbffce', 'Deti 1-8'], [60, 0, '', ''], [165, 15, '#a8b3ff', 'Verejko']],
          [[60, 15, '#bbffce', 'Deti 1-8'], [75, 15, '#ff8c8c', 'Muži'], [30, 15, '#bbffce', 'Deti 1-8'], [60, 0, '', ''], [165, 15, '#a8b3ff', 'Verejko'], [75, 15, '#ff8c8c', 'Muži'], [60, 0, '', ''], [180, 15, '#ffffa5', 'TT vs BA'], [45, 15, '#ff8c8c', 'Kraso'], [60, 15, '#bbffce', 'VUJE'], [90, 0, '', '']]
        ]
      ];
    } catch (\ADIOS\Core\Exceptions\GeneralException $e) {
      // TODO: Error
    }
  }

}
