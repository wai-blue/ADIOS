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
class DataTypeTime extends DataType
{
  public function sqlCreateString($table_name, $col_name, $params = [])
  {
    $params['sql_definitions'] = '' != trim((string)$params['sql_definitions']) || $params['required'] ? $params['sql_definitions'] : ' default null ';
    $params['sql_definitions'] ??= '';
    return "`$col_name` time {$params['sql_definitions']}";
  }

  public function sqlValueString($table_name, $col_name, $value, $params = [])
  {
    $params = \ADIOS\Core\Helper::arrayMergeRecursively($params, [
      'null_value' => false,
      'dumping_data' => false,
    ]);

    if (false == $params['null_value']) {
      $value = date('H:i:s', strtotime($value));
      $sql = "$col_name='$value'";
    } else {
      $sql = "$col_name=NULL";
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

    if (isset($params['col_definition']['format'])) {
      $format = $params['col_definition']['format'];
    } else {
      $format = $this->adios->getConfig('m_datapub/columns/time/format', 'H:i:s');
    }

    $ts = strtotime($value);
    $html = (0 == $ts ? '' : date($format, $ts));

    return $html;
  }

  public function toCsv($value, $params = [])
  {
    return $this->_toHtmlOrCsv($value, $params);
  }
}
