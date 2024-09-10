<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\DB\DataTypes;

/**
 * @todo Format the code properly
 * @package DataTypes
 */
class DataTypeColor extends \ADIOS\Core\DB\DataType
{
    public function sqlCreateString($table_name, $col_name, $params = [])
    {
      return "`{$col_name}` char(10) " . $this->getSqlDefinitions($params);
    }

    public function sqlValueString($table_name, $col_name, $value, $params = [])
    {
      return "`{$col_name}`= '" . $this->app->db->escape($value) . "'";
    }

    public function get_control_params($table_name, $col_name, $value, $col_definition, $params = [])
    {
      return [];
    }

    public function get_control($params = [])
    {
      extract($params, EXTR_OVERWRITE);

      if ('yes' == $only_display) {
        return "
          <input
            type=hidden
            name='$name'
            id='$name'
            value='".addslashes($value)."'
          >
          <div style='width:25px;background:$value'>&nbsp;&nbsp;&nbsp;</div>
        ";
      } else {
        return "
          <input type='color' id='{$name}' value='".ads($value)."' style='width:80px' onchange='{$name}_onchange();'>

          <div class='{$name}_div' farba='#CC0000'>&nbsp;</div>
          <div class='{$name}_div' farba='#FB940B'>&nbsp;</div>
          <div class='{$name}_div' farba='#FFFF00'>&nbsp;</div>
          <div class='{$name}_div' farba='#00CC00'>&nbsp;</div>
          <div class='{$name}_div' farba='#03C0C6'>&nbsp;</div>
          <div class='{$name}_div' farba='#0000FF'>&nbsp;</div>
          <div class='{$name}_div' farba='#762CA7'>&nbsp;</div>
          <div class='{$name}_div' farba='#FF98BF'>&nbsp;</div>
          <div class='{$name}_div' farba=''>&nbsp;</div>
          <div class='{$name}_div' farba='#999999'>&nbsp;</div>
          <div class='{$name}_div' farba='#000000'>&nbsp;</div>
          <div class='{$name}_div' farba='#885418'>&nbsp;</div>
          <script>
            $('.{$name}_div').each(function() {
              $(this).css({'background': $(this).attr('farba'), 'cursor': 'pointer', 'width': '12px', 'height': '12px', 'border': '2px solid white', 'display': 'inline-block'});
              $(this).click(function() {
                $('.{$name}_div').css({'border': '2px solid white', 'margin': '0px'});
                $(this).css({'border': '2px solid #494949', 'margin': '0px'});
                $('#{$name}').val($(this).attr('farba'));
                {$name}_onchange();
              });
            });

            $('.{$name}_div[farba='+$('#{$name}').val()+']').trigger('click');
          </script>
        ";
      }
    }

    public function toHtml($value, $params = [])
    {
      $value = htmlspecialchars($value);

      $html = '';

      if ('' != $value) {
        $html = "<span style='width:15px;background:{$value};border:1px solid black'>&nbsp;&nbsp;&nbsp;&nbsp;</span>";
      }

      return $html;
    }

    public function toCsv($value, $params = [])
    {
      return $value;
    }
}
