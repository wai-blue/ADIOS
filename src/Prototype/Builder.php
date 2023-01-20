<?php

namespace ADIOS\Prototype;

class Builder {
  protected string $inputFile = "";
  protected string $outputFolder = "";
  protected string $sessionSalt = "";
  protected string $logFile = "";

  protected array $prototype = [];
  protected $twig = NULL;
  protected $logHandle = NULL;

  public function __construct(string $inputFile, string $outputFolder, string $sessionSalt, string $logFile) {
    $this->inputFile = $inputFile;
    $this->outputFolder = $outputFolder;
    $this->sessionSalt = $sessionSalt;
    $this->logFile = $logFile;

    if (empty($this->outputFolder)) {
      throw new \Exception("No output folder for the prototype project provided.");
    }

    if (!is_dir($this->outputFolder)) {
      throw new \Exception("Output folder does not exist.");
    }

    $this->prototype = json_decode(file_get_contents($this->inputFile), TRUE);
    $this->logHandle = fopen($this->logFile, "w");

    if (!is_array($this->prototype["ConfigApp"])) throw new \Exception("ConfigApp is missing in prototype definition.");

    $this->prototype["ConfigApp"]["sessionSalt"] = $this->sessionSalt;

    $twigLoader = new \Twig\Loader\FilesystemLoader(__DIR__.'/Templates');
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
    $this->twig->addFunction(new \Twig\TwigFunction(
      'varExport',
      function ($var) {
        return var_export($var);
      }
    ));

    $this->checkPrototype();
  }

  public function __destruct() {
    fclose($this->logHandle);
  }

  public function log($msg) {
    echo $msg."\n";
    fwrite($this->logHandle, $msg."\n");
  }

  public function checkPrototype() {
    if (!is_array($this->prototype)) throw new \Exception("Prototype definition must be an array.");
  }

  public function createFolder($folder) {
    $this->log("Creating folder {$folder}.");
    @mkdir($this->outputFolder."/".$folder);
  }

  public function removeFolder($dir) { 
   if (is_dir($dir)) { 
      $objects = scandir($this->outputFolder.DIRECTORY_SEPARATOR.$dir);
      foreach ($objects as $object) {
        if (in_array($object, [".", ".."])) continue;
        if (
          is_dir($dir.DIRECTORY_SEPARATOR.$object)
          && !is_link($dir.DIRECTORY_SEPARATOR.$object)
        ) {
          $this->removeFolder($dir.DIRECTORY_SEPARATOR.$object);
        } else {
          unlink($dir.DIRECTORY_SEPARATOR.$object);
        }
      }
      rmdir($this->outputFolder.DIRECTORY_SEPARATOR.$dir);
    }
  }

  public function renderFile($fileName, $template, $twigParams = NULL) {
    $this->log("Rendering file {$fileName} from {$template}.");
    file_put_contents(
      $this->outputFolder."/".$fileName,
      $this->twig->render($template, $twigParams ?? $this->prototype)
    );
  }

  public function copyFile($srcFile, $destFile) {
    $this->log("Copying file {$srcFile} to {$destFile}.");
    if (!file_exists(__DIR__."/Templates/".$srcFile)) {
      throw new \Exception("File ".__DIR__."/Templates/{$srcFile} does not exist.");
    } else {
      copy(
        __DIR__."/Templates/".$srcFile,
        $this->outputFolder."/".$destFile
      );
    }

  }

  public function buildPrototype() {

    // delete folders if they exist
    $this->removeFolder("src");
    $this->removeFolder("log");
    $this->removeFolder("tmp");
    $this->removeFolder("upload");

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
          $this->createFolder("src/Widgets/{$widgetName}/Templates");
          foreach ($widgetConfig['actions'] as $actionName => $actionConfig) {
            $templateContainsPhpScript = ($actionConfig['templateContainsPhpScript'] ?? FALSE);

            if ($templateContainsPhpScript) {
              $twigTemplate = "src/Widgets/Actions/{$actionConfig['template']}.twig";
            } else {
              $twigTemplate = "src/Widgets/Action.twig";

              $this->copyFile(
                "src/Widgets/Templates/{$actionConfig['template']}.twig",
                "src/Widgets/{$widgetName}/Templates/{$actionConfig['template']}.twig"
              );
            }

            $tmpActionConfig = $actionConfig;
            unset($tmpActionConfig["template"]);
            unset($tmpActionConfig["templateContainsPhpScript"]);

            $this->renderFile(
              "src/Widgets/{$widgetName}/Actions/{$actionName}.php",
              $twigTemplate,
              array_merge(
                $this->prototype,
                [
                  "thisWidget" => [
                    "name" => $widgetName,
                    "config" => $widgetConfig
                  ],
                  "thisAction" => [
                    "name" => $actionName,
                    "config" => $tmpActionConfig
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