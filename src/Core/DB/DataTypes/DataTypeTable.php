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
class DataTypeTable extends \ADIOS\Core\DB\DataType
{
    public function sqlCreateString($table_name, $col_name, $params = [])
    {
        return '';
    }

    public function sqlValueString($table_name, $col_name, $value, $params = [])
    {
        return '';
    }

    public function lipsum($table_name, $col_name, $col_definition, $params = [])
    {
        return '';
    }

    private function _toHtmlOrCsv($value, $params = [])
    {
        return '';
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
