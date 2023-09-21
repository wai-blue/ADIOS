<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\DB\DataTypes;

/**
 * @package DataTypes
 */
class DataTypeEnum extends \ADIOS\Core\DB\DataType
{
    public function sqlCreateString($table_name, $col_name, $params = [])
    {
      $sql = "`$col_name` ENUM(";
      $e_vals = $params['enum_values'];
      $e_vals = str_replace('\,', '$%^@$%#$^%^$%#$^%$%@#$', $e_vals);
      $enum_values = explode(',', $e_vals);
      foreach ($enum_values as $key => $value) {
          $sql .= "'".trim(str_replace('$%^@$%#$^%^$%#$^%$%@#$', ',', $value))."', ";
      }
      $sql = substr($sql, 0, -2).") {$params['sql_definitions']}";

      return $sql;
    }

    public function sqlValueString($table_name, $col_name, $value, $params = [])
    {
      $e_vals = explode(',', $this->adios->db->tables[$table_name][$col_name]['enum_values']);
      if (in_array($value, $e_vals)) {
        $sql = "{$col_name}='".$this->adios->db->escape($value)."'";
      } else {
        $sql = "$col_name=NULL";
      }

      return $sql;
    }

    private function _toHtmlOrCsv($value, $params = [])
    {
      $html = '';

      $value = strip_tags((string) $value);
      $html = mb_substr($value, 0, ($params['col_definition']['wa_list_char_length'] ? $params['col_definition']['wa_list_char_length'] : 80), 'utf-8');
      if (strlen($html) < strlen($value)) {
        $html .= '...';
      }

      return $html;
    }

    public function toHtml($value, $params = [])
    {
      return $this->_toHtmlOrCsv($value, $params);
    }

    public function toCsv($value, $params = [])
    {
      return $value;
    }
}
