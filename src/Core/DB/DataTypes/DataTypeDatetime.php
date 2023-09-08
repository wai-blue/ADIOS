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
class DataTypeDatetime extends \ADIOS\Core\DB\DataType
{

    public function get_sql_create_string($table_name, $col_name, $params = []) {
      $params['sql_definitions'] = '' != trim((string) $params['sql_definitions']) || $params['required'] ? $params['sql_definitions'] : ' default null ';
      $params['sql_definitions'] ??= '';
      return "`$col_name` datetime {$params['sql_definitions']}";
    }

    public function get_sql_column_data_string($table_name, $col_name, $value, $params = []) {
      if (strtotime($value) == 0) {
        $sql = "`{$col_name}` = NULL";
      } else {
        $ts = date('Y-m-d H:i:s', strtotime($value));
        $sql = "`{$col_name}` = '{$ts}'";
      }

      return $sql;
    }

    public function get_html_or_csv($value, $params = []) {
      $dateFormat = $this->adios->locale->dateFormat();
      $timeFormat = $this->adios->locale->timeFormat();

      $ts = strtotime($value);
      $dateStr = date($dateFormat, $ts);
      $timeStr = date($timeFormat, $ts);

      if ($ts <= 0) {
        return "";
      } else {
        return "{$dateStr} <span style='color:var(--cl-gray-4)'>{$timeStr}</span>";
      }
    }

    public function get_html($value, $params = []) {
      return $this->get_html_or_csv($value, $params);
    }

    public function get_csv($value, $params = []) {
      return $this->get_html_or_csv($value, $params);
    }
}
