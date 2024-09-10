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
class DataTypeYear extends DataType
{
  public function sqlCreateString($table_name, $col_name, $params = [])
  {
    return "`$col_name` year " . $this->getSqlDefinitions($params);
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
          $sql = "$col_name=$value";
        }
      }
    } else {
      if (false == $params['null_value']) {
        if ('' == $value) {
          $sql = "$col_name=NULL";
        } else {
          $sql = "$col_name=$value";
        }
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

    $value = strip_tags($value);
    $html = mb_substr($value, 0, ($params['col_definition']['wa_list_char_length'] ? $params['col_definition']['wa_list_char_length'] : 80), 'utf-8');
    if (strlen($html) < strlen($value)) {
      $html .= '...';
    }

    return $html;
  }

  public function toCsv($value, $params = [])
  {
    return $this->_toHtmlOrCsv($value, $params);
  }


  public function validate(\ADIOS\Core\Model $model, $value): bool
  {
    return (int)$value >= 0;
  }
}
