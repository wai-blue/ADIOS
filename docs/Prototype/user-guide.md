# Prototype Builder - User Guide

1. Create your project folder (a "ROOT_DIR").
2. Create a prototype.json file in your ROOT_DIR (see prototype-sample.json).
3. In the ROOT_DIR run: 
    `php vendor/wai-blue/adios/src/CLI/build-prototype.php -I prototype.json -A {VENDOR_AUTOLOAD_FILE}`.
    where {VENDOR_AUTOLOAD_FILE} is a full path to your project's vendorautoload.php file
5. In a browser, open install.php from your ROOT_DIR folder (the file was created by the builder).
6. Log in.