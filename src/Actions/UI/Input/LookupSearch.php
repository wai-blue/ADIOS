<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Actions\UI\Input;
/**
 * @package UI\Actions\Input
 */
class LookupSearch extends \ADIOS\Core\Action {
  public function render() {

    // $this->adios->getUid("{$this->params['uid']}_lookup_select_window_action");

    $tableUid = $this->params['uid'] ?? $this->adios->getUid("{$this->params['model']}_LookupSearch");
    $windowUid = "{$tableUid}_lookup_select_window";

    $lookupModel = $this->adios->getModel($this->params['model']);
    $content = $this->adios->view->Table([
      "uid" => $tableUid,
      "model" => $this->params['model'],
      "where" => $lookupModel->lookupWhere(
        $this->params['initiating_model'],
        $this->params['initiating_column'],
        @json_decode($this->params['form_data'], TRUE) ?? [], // formData
        [], // params
      ),
      "list_type" => "lookup_select",
      "onclick" => "
        ui_input_lookup_set_value('{$this->params['inputUid']}', id, '');
        window_close('{$windowUid}');
      ",
    ]);

    $windowParams = [
      'uid' => $windowUid,
      'content' => $content->render(),
      'title' => $this->translate("Select"),
    ];

    $window = $this->adios->view->Window($windowParams);
    $window->setHeaderLeft([
      $this->adios->view->Button([
        'type' => 'close',
        'onclick' => "window_close('{$windowUid}');"
      ]),
    ]);

    return $window->render();
  }
}