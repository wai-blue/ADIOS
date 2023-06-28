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
class DataTypeDate extends \ADIOS\Core\DB\DataType
{

  public function get_sql_create_string($table_name, $col_name, $params = []) {
    $params['sql_definitions'] = '' != trim((string) $params['sql_definitions']) ? $params['sql_definitions'] : ' default null ';
    return "`{$col_name}` date {$params['sql_definitions']}";
  }

  public function get_sql_column_data_string($table_name, $col_name, $value, $params = []) {
    $dateStr = str_replace(' ', '', $value);

    if (empty($dateStr) || strtotime((string) $dateStr) == 0) {
      $sql = "`{$col_name}` = NULL";
    } else {
      $dateStr = date('Y-m-d', strtotime((string) $dateStr));

      if (!preg_match('/^\d\d\d\d-\d\d-\d\d$/', $dateStr)) {
        $sql = "`{$col_name}` = NULL";
      } else {
        $sql = "`{$col_name}` = '{$dateStr}'";
      }
    }

    return $sql;
  }

  public function get_html_or_csv($value, $params = []) {
    if (!empty($params['col_definition']['format'])) {
      $format = $params['col_definition']['format'];
    } else {
      $format = $this->adios->locale->dateFormat();
    }

    $ts = strtotime((string) $value);
    $html = (0 == $ts ? '' : date($format, $ts));

    return $html;
  }

  public function get_html($value, $params = []) {
    return $this->get_html_or_csv($value, $params);
  }

  public function get_csv($value, $params = []) {
    return $this->get_html_or_csv($value, $params);
  }
}
