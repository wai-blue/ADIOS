<?php

if (php_sapi_name() !== 'cli') exit();

require(__DIR__."/../Core/Loader.php");
$adios = new \ADIOS\Core\Loader(NULL, \ADIOS\Core\Loader::ADIOS_MODE_LITE);

$arguments = getopt(
  "I:A:O:S:L:U:B:",
  ["input:", "autoloader:", "output-folder:", "salt:", "log:", "root-url:", "rewrite-base:"],
  $restIndex
);

$inputFile = $arguments["I"] ?? $arguments["input"] ?? "";
$autoloaderFile = $arguments["A"] ?? $arguments["autoloader"] ?? "";
$outputFolder = $arguments["O"] ?? $arguments["output-folder"] ?? "";
$sessionSalt = $arguments["S"] ?? $arguments["salt"] ?? "";
$logFile = $arguments["L"] ?? $arguments["log"] ?? "";
$rootUrl = $arguments["U"] ?? $arguments["root-url"] ?? "http://localhost";
$rewriteBase = $arguments["B"] ?? $arguments["rewrite-base"] ?? "http://localhost";

if (empty($outputFolder)) $outputFolder = ".";
if (empty($inputFile)) $inputFile = __DIR__."/../../docs/Prototype/examples/01-one-widget.json";
if (empty($autoloaderFile)) $autoloaderFile = "{$outputFolder}/vendor/autoload.php";
if (empty($sessionSalt)) $sessionSalt = "random-".rand(1000, 9999);
if (empty($logFile)) $logFile = "{$outputFolder}/prototype.log";

if (
  empty($inputFile)
  || empty($autoloaderFile)
  || empty($outputFolder)
  || empty($sessionSalt)
  || empty($logFile)
) {
  exit(<<<USAGE

ADIOS v{$adios->version} PROTOTYPE BUILDER.

Creates folder structure and files of an ADIOS project based on provided prototype definition file.

Requires some composer packages installed. Copy **docs/Prototype/composer-sample-non-adios-developer.json** or
**docs/Prototype/composer-sample-adios-developer.json** to your project folder run 'composer install' before
running the prototype builder.

Usage: php build-prototype.php <options>
Options:
  -O, --output       Path to an output folder. Default: "."
  -I, --input        Path to a prototype definition file. Default: prototype-sample.json
  -A, --autoloader   Path to composer's autoloader file. Default: {% outputFolder %}/vendor/autoload.php
  -S, --salt         Session salt for the application's session data. Default: random generated.
  -L, --log          Path to a log file. Default: "{% outputFolder %}/prototype.log".

Example: php vendor/wai-blue/adios/src/CLI/build-prototype -I prototype.json -A vendor/autoload.php -S my-first-adios-app

Try sample prototype.json file is in **docs/Prototype/prototype-sample.json**.
or refer to **docs/Prototype/user-guide.md** for more information.

USAGE
  );
}


require_once($autoloaderFile);

$builder = new \ADIOS\Prototype\Builder($inputFile, $outputFolder, $sessionSalt, $logFile);

if (!empty($rewriteBase)) $builder->setRewriteBase($rewriteBase);

$builder->buildPrototype();
$builder->createEmptyDatabase();

echo "\n";
echo "SUCCESS: Prototype was successfuly built.\n";
echo "Run {$rootUrl}/install.php script from your browser now.\n";
echo "\n";
