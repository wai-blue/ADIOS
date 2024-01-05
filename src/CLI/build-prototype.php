<?php

if (php_sapi_name() !== 'cli') exit();

require(__DIR__."/../Core/Loader.php");
$adios = new \ADIOS\Core\Loader(NULL, \ADIOS\Core\Loader::ADIOS_MODE_LITE);

$isWindows = strpos(strtolower(php_uname('s')), "windows") !== FALSE;

$colorBlue = ($isWindows ? "\033[94m" : "");
$colorGreen = ($isWindows ? "\033[32m" : "");
$colorWhite = ($isWindows ? "\033[0m" : "");
$colorYellow = ($isWindows ? "\033[93m" : "");

$arguments = getopt(
  "",
  [
    "input:",
    "autoloader:",
    "output-folder:",
    "salt:",
    "log:",
    "root-url:",
    "rewrite-base:",
    "db-host:",
    "db-port:",
    "db-user:",
    "db-password:",
    "db-name:",
    "db-codepage:",
    "db-provider:",
    "db-dsn:",
    "admin-password:",
  ],
  $restIndex
);

$inputFile = $arguments['input'] ?? '';
$autoloaderFile = $arguments['autoloader'] ?? '';
$outputFolder = $arguments['output-folder'] ?? '';
$sessionSalt = $arguments['salt'] ?? '';
$logFile = $arguments['log'] ?? '';
$rootUrl = $arguments['root-url'] ?? '';
$rewriteBase = $arguments['rewrite-base'] ?? '';
$dbHost = $arguments['db-host'] ?? '';
$dbPort = $arguments['db-port'] ?? '';
$dbUser = $arguments['db-user'] ?? '';
$dbPassword = $arguments['db-password'] ?? '';
$dbName = $arguments['db-name'] ?? '';
$dbCodepage = $arguments['db-codepage'] ?? '';
$dbProvider = $arguments['db-provider'] ?? '';
$dbDsn = $arguments['db-dsn'] ?? '';
$adminPassword = $arguments['admin-password'] ?? '';

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

Creates an ADIOS application based on prototype definition file.

Usage: php build-prototype.php <options>
Options:
  --output-folder Path to an output folder. Default: "."
  --input         Path to a prototype definition file. Default: prototype-sample.json
  --autoloader    Path to composer's autoloader file. Default: {% outputFolder %}/vendor/autoload.php
  --salt          Session salt for the application's session data. Default: random generated.
  --log           Path to a log file. Default: "{% outputFolder %}/prototype.log".

Example:
php vendor/wai-blue/adios/src/CLI/build-prototype.php \
  --input prototype.json \
  --autoloader vendor/autoload.php

USAGE
  );
}

echo "ADIOS v{$adios->version} PROTOTYPE BUILDER\n";

if (
  empty($rewriteBase)
  || empty($rootUrl)
  || empty($dbHost)
) {
  echo $colorYellow."Some environment configuration is missing.\n";
  echo "\n";

  if (empty($rewriteBase)) {
    $rewriteBase = readline($colorYellow."RewriteBase = ");
    $rewriteBase = "/".trim($rewriteBase, "/")."/";
  }

  if (empty($rootUrl)) {
    $rootUrl = "http://localhost/".trim($rewriteBase, "/");
    $tmp = readline($colorYellow."RootURL (Enter for '{$rootUrl}') = ");
    if (!empty($tmp)) $rootUrl = $tmp;
  }

  if (empty($dbHost)) {
    $dbHost = 'localhost';
    $tmp = readline($colorYellow."DB host (Enter for 'localhost') = ");
    if (!empty($tmp)) $dbHost = $tmp;
  }

  if (empty($dbPort)) {
    $dbPort = 3306;
    $tmp = readline($colorYellow."DB host (Enter for '3306') = ");
    if (!empty($tmp)) $dbPort = (int) $tmp;
  }

  if (empty($dbUser)) {
    $dbUser = 'root';
    $tmp = readline($colorYellow."DB user (Enter for 'root') = ");
    if (!empty($tmp)) $dbUser = $tmp;
  }

  if (empty($dbPassword)) {
    $dbPassword = readline($colorYellow."DB password = ");
  }

  if (empty($dbName)) {
    $dbName = readline($colorYellow."DB name = ");
  }

  if (empty($dbCodepage)) {
    $dbCodepage = readline($colorYellow."DB codepage = ");
  }
}


require_once($autoloaderFile);

$builder = new \ADIOS\Prototype\Builder($inputFile, $outputFolder, $sessionSalt, $logFile);

$builder->setConfigEnv([
  "db" => [
    "host" => $dbHost,
    "port" => $dbPort,
    "user" => $dbUser,
    "password" => $dbPassword,
    "database" => $dbName,
    "codepage" => $dbCodepage,
    "provider" => $dbProvider,
    "dsn" => $dbDsn,
  ],
  "globalTablePrefix" => "",
  "rewriteBase" => $rewriteBase,
  "rootUrl" => $rootUrl
]);

if (!empty($adminPassword)) {
  $builder->setAdminPassword($adminPassword);
}

try {
  $builder->buildPrototype();

  echo "\n";
  echo $colorGreen."ADIOS application was successfuly built.\n";
  echo $colorWhite."Check ".realpath($logFile)." for details.\n";
  echo "\n";
  echo $colorYellow."  Now open {$rootUrl}/install.php in your browser\n";
  echo $colorYellow."  or run `php install.php` in your project's folder.\n";
  echo "\n";
  echo $colorWhite."More examples and documentation is at {$colorBlue}https://github.com/wai-blue/adios{$colorWhite}.\n";
  echo "\n";

} catch (\Twig\Error\SyntaxError $e) {
  echo 'ERROR: ' . $e->getMessage() . "\n";
  exit(1);
}

