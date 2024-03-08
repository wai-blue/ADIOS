<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers;

/**
 * @package Components\Controllers
 */
class Search extends \ADIOS\Core\Controller {
  public function getViewParams() {
    $items = [];

    if (strlen($this->params['q']) >= 3) {

      foreach ($this->adios->db->tables as $table_name => $table_columns) {
        if ($table_columns['%%table_params%%']['model'] instanceof \ADIOS\Core\Model) {
          $tmp_items = $table_columns['%%table_params%%']['model']->search($this->params['q']);
          $items = array_merge(
            $items,
            is_array($tmp_items) ? $tmp_items : []
          );
        }
      }

    } else {
      $items = [
        ['name' => 'Zadajte aspoň 3 znaky pre vyhľadávanie.'],
      ];
    }

    return [
      "items" => $items,
    ];
  }

  public function render() {
     // TODO: Po zmene z \ADIOS\Core\UI na \ADIOS\Core\ViewWithController toto sposobuje nekonecnu rekurziu
    $content = parent::render();

    $window = $this->adios->view->Window([
      'title' => "Hľadanie: {$this->params['q']}",
      'content' => $content,
    ]);

    $window->params['header'] = [
      $this->adios->view->Button([
        'type' => 'close',
        'onclick' => "window_close('{$window->params['uid']}');",
      ]),
    ];
    
    return $window->render();
  }
}
