<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\ViewsWithController\Inputs;

class CheckboxField extends \ADIOS\Core\ViewsWithController\Input {

  public function render(string $panel = ''): string
  {

    if (isset($this->params['crossTableAssignment'])) {
      $this->uid = $this->params['crossTableAssignment'];

      $initiatingModel = $this->app->getModel($this->params['initiating_model']);
      $cta = $initiatingModel->junctions[$this->params['junctions']];

      $junctionModel = $this->app->getModel($cta['junctionModel']);
      $masterKeyColumn = $cta['masterKeyColumn'];
      $optionKeyColumn = $cta['optionKeyColumn'];
      $options = $this->app->getModel($cta['optionsModel'])->getEnumValues();

    } else { // 2023-01-20 DEPRECATED
      $junctionModel = $this->app->getModel($this->params['model']);
      $masterKeyColumn = $this->params['key_column'] ?? "";
      $optionKeyColumn = $this->params['assignment_column'] ?? $this->params['value_column'] ?? "";
      $options = $this->params['values'];
    }

    if (isset($this->params['form_data'])) {
      $keyValue = (int) $this->params['form_data']['id'];
    } else {
      $keyValue = $this->params['key_value'];
    }

    switch ($this->params['columns'] ?? 3) {
      case 1: default: $bootstrapColumnSize = 12; break;
      case 2: $bootstrapColumnSize = 6; break;
      case 3: $bootstrapColumnSize = 4; break;
      case 4: $bootstrapColumnSize = 3; break;
      case 6: $bootstrapColumnSize = 2; break;
    }
    
    $columns = $junctionModel->columns();

    if (empty($junctionModel) || !is_array($columns)) {
      throw new \ADIOS\Core\Exceptions\GeneralException("CheckboxField Input: Error #1");
    }

    $assignmentsRaw = $this->app->db->select($junctionModel)
      ->columns(\ADIOS\Core\DB\Query::allColumnsWithLookups)
      ->where([
        [$masterKeyColumn, '=', $keyValue]
      ])
      ->fetch()
    ;

    $assignments = [];
    foreach ($assignmentsRaw as $assignmentRaw) {
      $assignments[] = $assignmentRaw[$optionKeyColumn];
    }
    $assignments = array_unique($assignments);
    
    $html = "
      <div class='adios ui Input checkbox-field'>
        <input type='hidden' id='{$this->uid}' data-is-adios-input='1' data-adios-input-class='CheckboxField'>
        <div class='row'>
    ";
    $i = 0;


    foreach ($options as $optionId => $optionDisplayValue) {
      $html .= "
        <div class='col-lg-{$bootstrapColumnSize} col-md-12'>
          <input
            type='checkbox'
            data-key='".ads($optionId)."'
            adios-do-not-serialize='1'
            id='{$this->uid}_checkbox_{$i}'
            onchange='{$this->uid}_serialize();'
            ".(in_array($optionId, $assignments) ? "checked" : "")."
          >
          <label for='{$this->uid}_checkbox_{$i}'>
            ".hsc($optionDisplayValue)."
          </label>
        </div>
      ";
      $i++;
    }
    $html .= "
        </div>
      </div>
      <script>
        function {$this->uid}_serialize() {
          let data = [];
          $('#{$this->uid}').closest('.checkbox-field').find('input[type=checkbox]:checked').each(function() {
            data.push($(this).data('key'));
          });
          $('#{$this->uid}').val(JSON.stringify(data));
        }

        {$this->uid}_serialize();
      </script>
    ";


    return $html;
  }
}
