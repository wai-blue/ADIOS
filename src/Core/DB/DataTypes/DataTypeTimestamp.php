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
class DataTypeTimestamp extends \ADIOS\Core\DB\DataType
{
  public function sqlCreateString($table_name, $col_name, $params = [])
  {
    return "`$col_name` timestamp " . $this->getSqlDefinitions($params);
  }

  public function sqlValueString($table_name, $col_name, $value, $params = [])
  {
    $params = \ADIOS\Core\Helper::arrayMergeRecursively($params, [
      'null_value' => false,
      'dumping_data' => false,
    ]);

    if ($params['dumping_data']) {
      if (false == $params['null_value']) {
        if ('' == $value) {
          $sql = "$col_name=NULL";
        } else {
          $sql = "$col_name='$value'";
        }
      }
    } else {
      if (false == $params['null_value']) {
        if (0 == strtotime($value)) {
          $sql = "$col_name=null";
        } else {
          $end_value = date('Y-m-d H:i:s', strtotime($value));
          $sql = "$col_name='$end_value'";
        }
      }
    }

    return $sql;
  }

  private function _toHtmlOrCsv($value, $params = [])
  {
    if (isset($params['col_definition']['format'])) {
      $format = $params['col_definition']['format'];
    } else {
      $format = $this->app->getConfig('m_datapub/columns/timestamp/format', 'd.m.Y H:i:s');
    }

    $ts = strtotime($value);
    $date_formatted = date($format, $ts);
    $html = (0 == $ts ? '' : $date_formatted);

    return $html;
  }

  public function toHtml($value, $params = [])
  {
    return $this->_toHtmlOrCsv($value, $params);
  }

  public function toCsv($value, $params = [])
  {
    return $this->_toHtmlOrCsv($value, $params);
  }
}
