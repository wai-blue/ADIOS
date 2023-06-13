# Prototype Builder - User Guide

With the prototype builder, you can generate your application with a single script. You only need to create a JSON file (see [examples](examples)) and run the `ADIOS/src/CLI/build-prototype.php`.

Prototype builder generates:
  * Folder structure
  * **Configuration files** for the application and for the environment
  * **Widgets** with sidebar links
  * **Models** with placeholders for the callbacks
  * **Actions** (a.k.a. controllers)
  * Initial **database** (configuration, admin user)

### Descriptions of each of the prototype examples

1. [01-one-widget.json](examples/01-one-widget.json) - shows a basic CRM application with a dashboard and no additional database tables created
2. [02-customers-data-model.json](examples/02-customers-data-model.json) - in addition to the previous prototype, also creates a database table with customers that have a name and a priority
3. [03-customized-form.json](examples/03-customized-form.json) - creates a database of customers similar to the previous prototype but with the addition of best-selling products and monthly turnover statistics for each customer
4. [04-second-model-in-a-widget.json](examples/04-second-model-in-a-widget.json) - creates a database of customers, best-selling products and monthly turnover statistics for each customer and allows you to add bids to each customer
5. [05-nested-table-in-a-form.json](examples/05-nested-table-in-a-form.json) - similar to the previous prototype but adds the ability to view a customer's bids by viewing their settings
6. [06-widget-for-settings.json](examples/06-widget-for-settings.json) - 
7. [07-customer-categories-cross-table.json](examples/07-customer-categories-cross-table.json) - creates a database of all customers, their bids and categories, which you can add customers to
8. [10-simple-crm.json](examples/10-simple-crm.json) - creates a complete CRM showcase of what ADIOS can do, including tables for customers, customer bids, categories, plus settings and a calendar (work in progress)

## Steps

1. Create your project folder (a "ROOT_DIR").
2. Create composer.json file in the ROOT_DIR (see sample [composer.json](composer-non-adios-developer.json)).
3. Run `composer install`
4. Create a prototype.json file in the ROOT_DIR (see [examples](examples)).
5. Run: `php vendor/wai-blue/adios/src/CLI/build-prototype.php -I prototype.json -A vendor/autoload` from the ROOT_DIR.
6. In a browser, launch install.php script which is now in the ROOT_DIR folder (the file was created by the prototype builder). If you use localhost, it may be something like http://localost/your_project/install.php.
7. Log in as administrator. Login and password are shown in the browser.

## Shell script examples

### Windows (build-prototype.bat)

```
php <PATH_TO_ADIOS>\src\CLI\build-prototype.php ^
  --salt myfirstapp ^
  --input myfirstapp.json ^
  --rewrite-base /myfirstapp/ ^
  --root-url http://localhost/myfirstapp ^
  --output-folder <PATH_TO_PROJECT_ROOT_FOLDER> ^
```

### Linux (build-prototype.sh)

```
#!/bin/bash

php <PATH_TO_ADIOS>/src/CLI/build-prototype.php \
  --salt myfirstapp \
  --input myfirstapp.json \
  --rewrite-base /myfirstapp/ \
  --root-url http://localhost/myfirstapp \
  --output-folder <PATH_TO_PROJECT_ROOT_FOLDER>
```

## Troubleshooting

If you have some troubles, see [troubleshooting](troubleshooting.md) section.
