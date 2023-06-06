# Query Builder

ADIOS comes with in-built SQL query builder which easy to read and understand. For example:

```php
$adios->db->update($model)
  ->set($data)
  ->whereId((int) $id)
  ->execute()
```

Updates the row in the `$model` with `ID = $id` and sets new `$data`.

Or:

```php
$data = $db->select($model)
  ->columns([\ADIOS\Core\DB\Query::allColumnsWithLookups])
  ->where([ ['id', '=', $id] ])
  ->fetchOne()
```

Fetches one row from the `$model` with `ID = $id`, including data of all models bind with the foreign keys (so-called `lookups`). For example, if it were the model for the customer categorized to some category, the result would look like:

```json
{
  "1": {
    "company_name": "My Company Ltd.",
    "company_id": "123456789",
    "id_category": 1,
    "id_category:LOOKUP": "Golden Customer",
    "id_category:LOOKUP:name": "Golden Customer",
    "id_category:LOOKUP:color": "#FF0000",
    "id": 1
  }
}
```

## Basic examples


### Simple select

```php
$data = $db->select($model)
  ->columns([\ADIOS\Core\DB\Query::allColumnsWithLookups])
  ->where([ ['id', '=', $id] ])
  ->fetchOne()
```

### Update one row
```php
$adios->db->update($model)
  ->set($data)
  ->whereId((int) $id)
  ->execute()
```

### Insert row

```php
$adios->db->insert($model)
  ->set($data)
  ->execute()
```

See more [complex examples here](query-builder-examples.md).