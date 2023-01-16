<?php

namespace ADIOS\Prototype;

class Builder {
  protected $inputFile = "";
  protected $outputFolder = "";
  protected $prototype = [];
  protected $twig = NULL;
  protected $logFile = "";
  protected $logHandle = NULL;

  public function __construct($inputFile, $outputFolder, $logFile) {
    $this->inputFile = $inputFile;
    $this->outputFolder = $outputFolder;
    $this->logFile = $logFile;

    if (empty($this->outputFolder)) {
      throw new \Exception("No output folder for the prototype project provided.");
    }

    if (!is_dir($this->outputFolder)) {
      throw new \Exception("Output folder does not exist.");
    }

    $this->prototype = json_decode(file_get_contents($this->inputFile), TRUE);
    $this->logHandle = fopen($this->logFile, "w");

    $twigLoader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/Templates');
    $this->twig = new \Twig\Environment($twigLoader, [
      'cache' => FALSE,
      'debug' => TRUE,
    ]);
    $this->twig->addFunction(new \Twig\TwigFunction(
      'getVariableType',
      function ($var) {
        if (is_numeric($var)) $type = "numeric";
        else if (is_bool($var)) $type = "bool";
        else $type = "string";

        return $type;
      }
    ));

    $this->checkPrototype();
  }

  public function __destruct() {
    fclose($this->logHandle);
  }

  public function log($msg) {
    echo $msg . "\n";
    fwrite($this->logHandle, $msg . "\n");
  }

  public function checkPrototype() {
    if (!is_array($this->prototype)) throw new \Exception("Prototype definition must be an array.");
  }

  public function createFolder($folder) {
    $this->log("Creating folder {$folder}.");
    @mkdir($this->outputFolder . "/" . $folder);
  }

  public function renderFile($fileName, $template, $twigParams = NULL) {
    $this->log("Rendering file {$fileName} from {$template}.");
    file_put_contents(
      $this->outputFolder . "/" . $fileName,
      $this->twig->render($template, $twigParams ?? $this->prototype)
    );
  }

  public function copyFile($srcFile, $destFile) {
    $this->log("Copying file {$srcFile} to {$destFile}.");
    copy(
      __DIR__ . "/Templates/" . $srcFile,
      $this->outputFolder . "/" . $destFile
    );
  }

  public function buildPrototype() {

    // create folder structure
    $this->createFolder("src");
    $this->createFolder("src/Assets");
    $this->createFolder("src/Assets/images");
    $this->createFolder("src/Widgets");
    $this->createFolder("log");
    $this->createFolder("tmp");
    $this->createFolder("upload");

    // render files
    try {
      $this->copyFile("src/Assets/images/favicon.png", "src/Assets/images/favicon.png");
      $this->copyFile("src/Assets/images/logo.png", "src/Assets/images/logo.png");
      $this->copyFile("src/Assets/images/login-screen.jpg", "src/Assets/images/login-screen.jpg");
      $this->copyFile(".htaccess", ".htaccess");
      $this->copyFile(".htaccess-subfolder", "log/.htaccess");
      $this->copyFile(".htaccess-subfolder", "tmp/.htaccess");
      $this->copyFile(".htaccess-subfolder", "upload/.htaccess");

      $this->renderFile("src/ConfigApp.php", "src/ConfigApp.twig");
      $this->renderFile("src/Init.php", "src/Init.twig");

      $this->renderFile("index.php", "index.twig");
      $this->renderFile("ConfigEnv.php", "ConfigEnv.twig");
      $this->renderFile("install.php", "install.twig");

      // render widgets
      foreach ($this->prototype['Widgets'] as $widgetName => $widgetConfig) {
        $this->createFolder("src/Widgets/{$widgetName}");
        $this->renderFile(
          "src/Widgets/{$widgetName}/Main.php",
          "src/Widgets/WidgetMain.twig",
          array_merge(
            $this->prototype,
            [
              "thisWidget" => [
                "name" => $widgetName,
                "config" => $widgetConfig
              ]
            ]
          )
        );

        if (is_array($widgetConfig['models'] ?? NULL)) {
          $this->createFolder("src/Widgets/{$widgetName}/Models");
          foreach ($widgetConfig['models'] as $modelName => $modelConfig) {
            $this->renderFile(
              "src/Widgets/{$widgetName}/Models/{$modelName}.php",
              "src/Widgets/Model.twig",
              array_merge(
                $this->prototype,
                [
                  "thisWidget" => [
                    "name" => $widgetName,
                    "config" => $widgetConfig
                  ],
                  "thisModel" => [
                    "name" => $modelName,
                    "config" => $modelConfig
                  ]
                ]
              )
            );
          }
        }

        if (is_array($widgetConfig['actions'] ?? NULL)) {
          $this->createFolder("src/Widgets/{$widgetName}/Actions");
          foreach ($widgetConfig['actions'] as $actionName => $actionConfig) {
            $this->renderFile(
              "src/Widgets/{$widgetName}/Actions/{$actionName}.php",
              "src/Widgets/Actions/{$actionConfig['template']}.twig",
              array_merge(
                $this->prototype,
                [
                  "thisWidget" => [
                    "name" => $widgetName,
                    "config" => $widgetConfig
                  ],
                  "thisAction" => [
                    "name" => $actionName,
                    "config" => $actionConfig
                  ]
                ]
              )
            );
          }
        }
      }
    } catch (\Twig\Error\SyntaxError $e) {
      echo $e->getMessage();
    }
  }

  public function createEmptyDatabase() {
    $this->log("Creating empty database.");

    $dbCfg = $this->prototype['ConfigEnv']['db'];

    $db = new \mysqli(
      $dbCfg['host'],
      $dbCfg['login'],
      $dbCfg['password'],
      "",
      (int) ($dbCfg['port'] ?? 0)
    );

    $multiQuery = $this->twig->render("emptyDatabase.sql.twig", $this->prototype);
    $db->multi_query($multiQuery);
  }
}