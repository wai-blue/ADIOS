<?php

namespace ADIOS\Core\DB\DataTypes;

/**
 * Multiline text data type.
 *
 * Converted to **text** in the SQL.
 *
 * *Components/Input* renders *textarea* for this data type.
 *
 * Example of definition in \ADIOS\Core\Model's column() method:
 * ```
 *   "myColumn" => [
 *     "type" => "text",
 *     "title" => "My Text Column",
 *     "show_column" => FALSE,
 *   ]
 * ```
 *
 * @package DataTypes
 */
class DataTypeJson extends \ADIOS\Core\DB\DataType
{
  public function sqlCreateString($table_name, $col_name, $params = []) {
    return "`{$col_name}` json ".($params['rawSqlDefinitions'] ?? "");
  }

  public function sqlValueString($table_name, $col_name, $value, $params = []) {
    $params = \ADIOS\Core\Helper::arrayMergeRecursively($params, [
      'null_value' => false,
      'dumping_data' => false,
      'escape_string' => $this->app->getConfig('m_datapub/escape_string', true),
    ]);

    if ($params['null_value']) {
      $sql = "`{$col_name}` = NULL";
    } else {
      $sql = "`{$col_name}` = '".($params['escape_string'] ? $this->app->db->escape($value) : $value)."'";
    }

    return $sql;
  }

  public function columnDefinitionPostProcess(array $colDef): array
  {
    if (
      isset($colDef['schemaFileJson'])
      && strpos($colDef['schemaFileJson'], '..') === FALSE
    ) {
      $schemaFileJson =
        $this->app->config['dir']
        . '/'
        . $colDef['schemaFileJson']
      ;

      $colDef['schema'] = json_decode(file_get_contents($schemaFileJson), TRUE);
    }

    return $colDef;
  }

  public function toHtml($value, $params = []) {
    $html = "
      <pre>
        ".json_decode(json_encode($value), JSON_PRETTY_PRINT)."
      </pre>
    ";

    return $html;
  }
}
