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
class DataTypePassword extends \ADIOS\Core\DB\DataType
{
    public function sqlCreateString($table_name, $col_name, $params = [])
    {
        $params['sql_definitions'] = '' != trim((string) $params['sql_definitions']) ? $params['sql_definitions'] : " default '' ";

        return "`$col_name` varchar({$params['byte_size']}) {$params['sql_definitions']}";
    }

    public function sqlValueString($table_name, $col_name, $value, $params = []) {
      $params = \ADIOS\Core\Helper::arrayMergeRecursively($params, [
        'null_value' => false,
        'dumping_data' => false,
        'escape_string' => $this->adios->getConfig('m_datapub/escape_string', true),
      ]);

      if ($params['null_value']) {
        $sql = "$col_name=NULL";
      } else {
        $pswd_1 = $params["data"]["{$col_name}_1"] ?? "";
        $pswd_2 = $params["data"]["{$col_name}_2"] ?? "";

        if ($pswd_1 != "" && $pswd_1 == $pswd_2) {
          $sql = "`{$col_name}` = '".password_hash($pswd_1, PASSWORD_DEFAULT)."'";
        }
      }

      return $sql;
    }

    public function toHtml($value, $params = []) {
      return "...".substr(hsc($value), 8, 8)."...";
    }

    public function toCsv($value, $params = []) {
      return '';
    }
}
