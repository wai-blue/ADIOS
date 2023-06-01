# ADIOS

Light-weight rapid application development framework for PHP 8.

Easy to use and completely free.

## Why ADIOS?

ADIOS is a framework with a **small footprint**. With only a little coding, you can create application with complete CRUD functionality, containing complex inputs (like dates, WYSIWYG editors or autocomplete) and able to manage complex tables.

You do not need to waste time configuring the router. Simply create a file and the **routing table will update automatically**.

You do not need to import UI components for the CRUD operations. **UI/Table and UI/Form components are in-built.**

You can very easily create very complex forms, thanks to the **strong form templating engine**. Tabs, grids, inputs, dashboards, ... it's done in few lines of code.

<img src="docs/Assets/images/dashboard.png" />

## Features

  * Small footprint
  * [Bootstrap](https://getbootstrap.com)-based user interface
  * In-built configuration management
  * In-build models for user management, permissions and ACL
  * In-built routing
  * Multi-language support
  * Skinnable
  * Full-featured set of in-built UI components (tables, forms, tabs, inputs, charts, dashboard, ...)
  * Strong form-templating engine
  * Flexible templating engine thanks to [Twig](https://twig.symfony.com)
  * Compatible with Laravel's [Eloquent](https://laravel.com/docs/eloquent)
  * Powerful prototype builder
  * Nice formatted exception handling including fatal errors

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

## Want to contribute?

ADIOS is an open-source MIT licensed framework. You can use it for free for both personal and commercial projects.

We will be happy for any contributions to the project:

  * UI componets
  * Language dictionaries
  * Skins
  * Plugins
  * Prototype builder templates
  * Sample applications
  * Documentation
  * Unit tests
  * And anything else...

Enjoy!

## Want to donate? Buy us a beer.

Thank you :-)
