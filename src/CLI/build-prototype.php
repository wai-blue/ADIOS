<?php

if (php_sapi_name() !== 'cli') exit();

$arguments = getopt(
  "I:A:O:L:",
  ["input:", "autoloader:", "output:", "log:"],
  $restIndex
);

$inputFile = $arguments["I"] ?? $arguments["input"] ?? "";
$autoloaderFile = $arguments["A"] ?? $arguments["autoloader"] ?? "";
$outputFolder = $arguments["O"] ?? $arguments["output"] ?? ".";
$logFile = $arguments["L"] ?? $arguments["log"] ?? "prototype.log";

if (
  empty($inputFile)
  || empty($autoloaderFile)
  || empty($outputFolder)
  || empty($logFile)
) {
  exit(<<<USAGE
ADIOS prototype builder.

Usage:

php build-prototype.php <options>

Options:
  -I, --input        Path to a prototype definition file
  -A, --autoloader   Path to composer's autoloader file
  -O, --output       Path to an output folder. Default: "."
  -L, --log          Path to a log file. Default: "prototype.log"
USAGE
  );
}
if (!is_file($inputFile)) exit("Input file does not exist.");

require_once($autoloaderFile);

$builder = new \ADIOS\Prototype\Builder($inputFile, $outputFolder, $logFile);

$builder->buildPrototype();
$builder->createEmptyDatabase();

echo "\n";
echo "SUCCESS: Prototype was successfuly build.\n";
echo "Run ROOT_DIR/install.php script from your browser now.\n";
echo "\n";
