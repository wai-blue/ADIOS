# Query Builder - Examples

Here you can find some examples of the query builder. Each example has two parts:

  * the first part shows how the DB request is written in the builder
  * the second part shows the equivalent PHP code using `fetchRaw()` method and the `MySQL` format

## Select + Custom columns + Having + fetchOne

This:

```php
$this->adios->db->select($model)
  ->columns([
    [ 'id', 'id' ],
    [ $model->lookupSqlValue(), 'input_lookup_value' ]
  ])
  ->having([ ['input_lookup_value', '=', $value] ])
  ->fetchOne()
```

is equivalent to:

```php
  $row = reset($this->adios->db->fetchRaw("
    select
      id,
      " . $model->lookupSqlValue("t") . " as `input_lookup_value`
    from `" . $model->getFullTableSqlName() . "` t
    having `input_lookup_value` = '" . $this->adios->db->escape($value) . "'
  "));
```