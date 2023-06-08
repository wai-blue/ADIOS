# ADIOS Database

```php
$data = $adios->db->select($model, [\ADIOS\Core\DB\Query::countRows])
  ->columns([\ADIOS\Core\DB\Query::allColumnsWithLookups])
  ->where([ ['id', '=', 1] ])
  ->having([ ['amount', '>', 0] )
  ->order($orderBy)
  ->limit(0, 1)
  ->fetch()
```

This PHP call results in a `$data` structured like this:

```json
{
  "1": {
    "date": "2023-06-01",
    "description": "Test log",
    "amount": 1.00,
    "is_accounted": true,
    "id_fin_accounting_periods": 1,
    "id_fin_accounting_periods:LOOKUP": "Test accounting period (2022-09-19)",
    "id_fin_accounting_periods:LOOKUP:name": "Test accounting period",
    "id_fin_accounting_periods:LOOKUP:start_date": "2022-09-19",
    "id_fin_accounting_periods:LOOKUP:end_date": "2022-11-27",
    "id_fin_accounting_periods:LOOKUP:is_open": true,
    "id_fin_accounting_periods:LOOKUP:id_fin_accounting_periods": null,
    "id_fin_accounting_periods:LOOKUP:id": 1,
    "number": "123456",
    "id": 1
  }
}
```

You can see that on of the the `select()` arguments is `[\ADIOS\DB\Core\Query:countRows]`. This is the 'select modifier'. With this modifier, you can simply call:

```php
$count = $adios->countRowsFromLastSelect();
```

And you will get the number of rows for that query.

## Query Builder

The example above was the example of the in-built [query builder](query-builder.md). It has following types of queries implemented: `select`, `insert`, `update` and `delete` (see [Query.php](/src/Core/DB/Query.php)).

Each of these queries can be customized with many available statements, e.g. `left join`, `where`, `having`, `order`, `set` etc.

For more details refer to [this documentation](query-builder.md).