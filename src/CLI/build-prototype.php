<?php

if (php_sapi_name() !== 'cli') exit();

require(__DIR__."/../Core/Loader.php");
$adios = new \ADIOS\Core\Loader(NULL, \ADIOS\Core\Loader::ADIOS_MODE_LITE);

$arguments = getopt(
  "I:A:O:S:L:",
  ["input:", "autoloader:", "output:", "salt:", "log:"],
  $restIndex
);

$inputFile = $arguments["I"] ?? $arguments["input"] ?? "";
$autoloaderFile = $arguments["A"] ?? $arguments["autoloader"] ?? "";
$outputFolder = $arguments["O"] ?? $arguments["output"] ?? "";
$sessionSalt = $arguments["S"] ?? $arguments["salt"] ?? "";
$logFile = $arguments["L"] ?? $arguments["log"] ?? "{$outputFolder}/prototype.log";

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
  -I, --input        Required. Path to a prototype definition file.
  -A, --autoloader   Required. Path to composer's autoloader file.
  -O, --output       Required. Path to an output folder.
  -S, --salt         Required. Session salt for the application's session data.
  -L, --log          Path to a log file. Default: "{% outputFolder %}/prototype.log".

Example: php vendor/wai-blue/adios/src/CLI/build-prototype -I prototype.json -A vendor/autoload.php -S my-first-adios-app

Try sample prototype.json file is in **docs/Prototype/prototype-sample.json**.
or refer to **docs/Prototype/user-guide.md** for more information.

USAGE
  );
}
if (!is_file($inputFile)) exit("Input file does not exist.");

require_once($autoloaderFile);

$builder = new \ADIOS\Prototype\Builder($inputFile, $outputFolder, $sessionSalt, $logFile);

$builder->buildPrototype();
$builder->createEmptyDatabase();

echo "\n";
echo "SUCCESS: Prototype was successfuly built.\n";
echo "Run ROOT_DIR/install.php script from your browser now.\n";
echo "\n";
