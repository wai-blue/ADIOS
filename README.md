# ADIOS

Light-weight rapid application development framework for PHP 8

## Features

  * Bootstrap base user interface
  * In-built configuration management, file-based or DB-based
  * In-build models for user management, permissions and ACL
  * In-built default routing
    * Simply create a PHP file for the controller and the routing table will automatically update
  * Full-featured set of in-built UI components (tables, forms, tabs, inputs, charts, dashboard, ...)
  * Flexible templating engine thanks to TWIG
  * Compatible with Laravel's Eloquent
  * Powerful prototype builder

## Prototype builder

Build your application in a second:

  * create a prototype.json file, e.g:

```
{
  "ConfigEnv": {
    "db": {
      "host": "localhost",
      "login": "root",
      "password": "",
      "name": "prototype_crm"
    },
    "globalTablePrefix": "proto",
    "rewriteBase": "/prototype_crm/"
  },
  "ConfigApp": {
    "applicationName": "Prototype CRM - One data model",
    "defaultAction": "Home/Dashboard"
  },
  "Widgets": {
    "Home": {
      "faIcon": "fa-home",
      "sidebar": {
        "Home": {
          "url": "Home/Dashboard"
        }
      },
      "actions": {
        "Dashboard": {
          "template": "Dashboard"
        }
      }
    },
    "Customers": {
      "faIcon": "fa-address-book",
      "sidebar": {
        "Customers": {
          "url": "Customers"
        }
      },

      "models": {
        "Customer": {
          "sqlName": "customers",
          "urlBase": "Customers",
          "tableTitle": "Customers",
          "formTitleForInserting": "New customer",
          "formTitleForEditing": "Edit customer",
          "lookupSqlValue": "{%TABLE%}.name",
          "columns": {
            "name": {
              "type": "varchar",
              "title": "Customer name",
              "show_column": true
            },
            "priority": {
              "type": "int",
              "title": "Priority",
              "enum_values": ["High", "Middle", "Low"],
              "show_column": true
            }
          }
        }
      }
    }
  }
}
```

  * run prototype builder in your project's folder:

```
php vendor/wai-blue/adios/src/CLI/build-prototype -I prototype.json -A vendor/autoload
```

More JSON examples and usage details are available [here](docs/Prototype/user-guide.md).