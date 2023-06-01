# Prototype Builder - User Guide

With the prototype builder, you can generate your application with a single script. You only need to create a JSON file (see [examples](examples)) and run the `ADIOS/src/CLI/build-prototype.php`

1. Create your project folder (a "ROOT_DIR").
2. Create composer.json file in the ROOT_DIR (see sample [composer.json](composer-non-adios-developer.json)).
3. Run `composer install`
4. Create a prototype.json file in the ROOT_DIR (see [examples](examples)).
5. Run: `php vendor/wai-blue/adios/src/CLI/build-prototype.php -I prototype.json -A vendor/autoload` from the ROOT_DIR.
6. In a browser, launch install.php script whic is now in the ROOT_DIR folder (the file was created by the prototype builder). If you use localhost, it may be something like http://localost/your_project/install.php.
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
