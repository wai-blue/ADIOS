<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\DB\DataTypes;

use ADIOS\Core\DB\DataType;

/**
 * @package DataTypes
 */
class DataTypeInt extends DataType
{
  public function sqlCreateString($table_name, $col_name, $params = [])
  {
    $params['sql_definitions'] = '' != trim((string)$params['sql_definitions']) || $params['required'] ? $params['sql_definitions'] : ' default null ';
    $params['sql_definitions'] ??= '';

    return "`$col_name` int({$params['byte_size']}) {$params['sql_definitions']}";
  }

  public function sqlValueString($table_name, $col_name, $value, $params = [])
  {
    $params = _put_default_params_values($params, [
      'null_value' => false,
      'dumping_data' => false,
      'escape_string' => $this->adios->getConfig('m_datapub/escape_string', true),
    ]);

    if ($params['dumping_data'] && '' == $value) {
      $value = '-1';
    }

    if ($params['null_value']) {
      $sql = "$col_name=NULL";
    } else {
      if (is_numeric($value) && '' != $value) {
        $sql = "$col_name='" . ($params['escape_string'] ? $this->adios->db->escape($value + 0) : $value + 0) . "'";
      } else {
        $sql = "$col_name=null";
      }
    }

    return $sql;
  }

  public function toHtml($value, $params = [])
  {
    return $this->_toHtmlOrCsv($value, $params);
  }

  private function _toHtmlOrCsv($value, $params = [])
  {
    $html = '';

    if (is_array($params['col_definition']['code_list'])) {
      if (is_numeric($value)) {
        $html = $params['col_definition']['code_list'][$value];
      } else {
        $html = $value;
      }
    } elseif (is_array($params['col_definition']['enum_values'])) {
      $html = l(
        $params['col_definition']['enum_values'][$value],
        [],
        ['input_column_settings_enum_translation' => true]
      );
    } else {
      $value_number = number_format((int)strip_tags($value) + 0, 0, '', ' ');

      if ('' == $params['col_definition']['format']) {
        $value = $value_number;
      } else {
        $value = str_replace('{%VALUE%}', $value_number, $params['col_definition']['format']);
      }

      if ($params['col_definition']['unit'] != "") $html .= " {$params['col_definition']['unit']}";

      $html = $value;
    }

    return $html;
  }

  public function toCsv($value, $params = [])
  {
    return $this->_toHtmlOrCsv($value, $params);
  }

  public function fromString(?string $value)
  {
    return (int)$value;
  }

  public function validate($value): bool
  {
    return empty($value) || is_numeric($value);
  }
}
