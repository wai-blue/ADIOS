<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core;

// Autoloader function

spl_autoload_register(function ($class) {
  global $___ADIOSObject;

  $class = str_replace("\\", "/", $class);
  $class = trim($class, "/");

  // if (strpos($class, "ADIOS/") === FALSE) return;

  $loaded = @include_once(dirname(__FILE__) . "/" . str_replace("ADIOS/", "", $class) . ".php");

  if (!$loaded) {

    if (strpos($class, "ADIOS/Controllers") === 0) {

      $class = str_replace("ADIOS/Controllers/", "", $class);

      // najprv skusim hladat core akciu
      $tmp = dirname(__FILE__) . "/Controllers/{$class}.php";
      if (!@include_once($tmp)) {
        // ak sa nepodari, hladam widgetovsku akciu

        $widgetPath = explode("/", $class);
        $widgetName = array_pop($widgetPath);
        $widgetPath = join("/", $widgetPath);

        if (!@include_once($___ADIOSObject->config['srcDir'] . "/Widgets/{$widgetPath}/Controllers/{$widgetName}.php")) {
          // ak ani widgetovska, skusim plugin
          $class = str_replace("Plugins/", "", $class);
          $pathLeft = "";
          $pathRight = "";
          foreach (explode("/", $class) as $pathPart) {
            $pathLeft .= ($pathLeft == "" ? "" : "/") . $pathPart;
            $pathRight = str_replace("{$pathLeft}/", "", $class);

            $included = FALSE;

            foreach ($___ADIOSObject->pluginFolders as $pluginFolder) {
              $file = "{$pluginFolder}/{$pathLeft}/Controllers/{$pathRight}.php";
              if (is_file($file)) {
                include($file);
                $included = TRUE;
                break;
              }
            }

            if ($included) {
              break;
            }
          }
        }
      }
    } else if (preg_match('/ADIOS\/Widgets\/([\w\/]+)/', $class, $m)) {

      if (!isset($___ADIOSObject)) {
        throw new \Exception("ADIOS is not loaded.");
      }

      if (!@include_once($___ADIOSObject->config['srcDir'] . "/Widgets/{$m[1]}/Main.php")) {
        include_once($___ADIOSObject->config['srcDir'] . "/Widgets/{$m[1]}.php");
      }
    } else if (preg_match('/ADIOS\/Plugins\/([\w\/]+)/', $class, $m)) {
      foreach ($___ADIOSObject->pluginFolders as $pluginFolder) {
        if (include_once("{$pluginFolder}/{$m[1]}/Main.php")) {
          break;
        } else if (include_once("{$pluginFolder}/{$m[1]}.php")) {
          break;
        }
      }
    } else if (preg_match('/ADIOS\/Tests\/([\w\/]+)/', $class, $m)) {
      $class = str_replace("ADIOS/Tests/", "", $class);

      $testFile = __DIR__ . "/../../tests/{$class}.php";

      if (is_file($testFile)) {
        include_once($testFile);
      } else {
        include_once($___ADIOSObject->config['srcDir'] . "/../tests/{$class}.php");
      }

    } else if (preg_match('/ADIOS\/Web\/([\w\/]+)/', $class, $m)) {
      $class = str_replace("ADIOS/Web/", "", $class);

      include_once($___ADIOSObject->config['srcDir'] . "/Web/{$class}.php");

    } else if (preg_match('/ADIOS\/([\w\/]+)/', $class, $m)) {
      include_once(__DIR__ . "/../{$m[1]}.php");

    } else if (preg_match('/App\/([\w\/]+)/', $class, $m)) {
      $fname1 = $___ADIOSObject->config['srcDir'] . "/{$m[1]}/Main.php";
      $fname2 = $___ADIOSObject->config['srcDir'] . "/{$m[1]}.php";
      
      if (is_file($fname1)) {
        include($fname1);
      } else if (is_file($fname2)) {
        include($fname2);
      }
    }
  }
});

// ADIOS Loader class
#[\AllowDynamicProperties]
class Loader
{
  const ADIOS_MODE_FULL = 1;
  const ADIOS_MODE_LITE = 2;

  public string $version = "";
  public string $gtp = "";
  public string $requestedUri = "";
  public string $requestedController = "";
  public string $controller = "";
  public string $uid = "";
  public string $srcDir = "";

  public $controllerObject;

  public bool $logged = FALSE;

  public array $config = [];
  public array $widgets = [];

  public array $widgetsInstalled = [];

  public array $pluginFolders = [];
  public array $pluginObjects = [];
  public array $plugins = [];

  public array $modelObjects = [];
  public array $models = [];

  public bool $userLogged = FALSE;
  public array $userProfile = [];
  public array $userPasswordReset = [];

  public ?\ADIOS\Core\DB $db = NULL;
  public ?\ADIOS\Core\Console $console = NULL;
  public ?\ADIOS\Core\Locale $locale = NULL;
  public ?\ADIOS\Core\Router $router = NULL;
  public ?\ADIOS\Core\Email $email = NULL;
  public ?\ADIOS\Core\UserNotifications $userNotifications = NULL;
  public ?\ADIOS\Core\Permissions $permissions = NULL;
  public ?\ADIOS\Core\Test $test = NULL;
  public ?\ADIOS\Core\Web\Loader $web = NULL;

  public ?\Twig\Environment $twig = NULL;

  public ?\ADIOS\Core\PDO $pdo = NULL;

  public array $assetsUrlMap = [];

  public int $controllerNestingLevel = 0;
  public array $controllerStack = [];

  public string $dictionaryFilename = "Core-Loader";
  public array $dictionary = [];

  public bool $forceUserLogout = FALSE;

  public string $desktopContentController = "";

  public string $widgetsDir = "";

  public function __construct($config = NULL, $mode = NULL, $forceUserLogout = FALSE) {

    global $___ADIOSObject;
    $___ADIOSObject = $this;

    if ($mode === NULL) {
      $mode = self::ADIOS_MODE_FULL;
    }

    $this->test = new ($this->getCoreClass('Test'))($this);

    if (is_array($config)) {
      $this->config = $config;
    }

    $this->widgetsDir = $config['widgetsDir'] ?? "";

    $this->version = file_get_contents(__DIR__."/../version.txt");

    $this->gtp = $this->config['global_table_prefix'] ?? "";
    $this->requestedController = $_REQUEST['controller'] ?? "";
    $this->forceUserLogout = $forceUserLogout;

    if (empty($this->config['dir'])) $this->config['dir'] = "";
    if (empty($this->config['url'])) $this->config['url'] = "";
    if (empty($this->config['rewriteBase'])) $this->config['rewriteBase'] = "";

    $this->srcDir = realpath(__DIR__."/..");

    if (empty($this->config['sessionSalt'])) {
      $this->config['sessionSalt'] = rand(100000, 999999);
    }

    $this->config['requestUri'] = $_SERVER['REQUEST_URI'] ?? "";

    // load available languages
    if (empty($this->config['availableLanguages'] ?? [])) {
      $this->config['availableLanguages'] = ["en"];
    }

    // pouziva sa ako vseobecny prefix niektorych session premennych,
    // novy ADIOS ma zatial natvrdo hodnotu, lebo sa sessions riesia cez session name
    if (!defined('_ADIOS_ID')) {
      define(
        '_ADIOS_ID',
        $this->config['sessionSalt']."-".substr(md5($this->config['sessionSalt']), 0, 5)
      );
    }

    // ak requestuje nejaky Asset (css, js, image, font), tak ho vyplujem a skoncim
    if ($this->config['rewriteBase'] == "/") {
      $this->requestedUri = ltrim($this->config['requestUri'], "/");
    } else {
      $this->requestedUri = str_replace($this->config['rewriteBase'], "", $this->config['requestUri']);
    }

    $this->assetsUrlMap["adios/assets/css/"] = __DIR__."/../Assets/Css/";
    $this->assetsUrlMap["adios/assets/js/"] = __DIR__."/../Assets/Js/";
    $this->assetsUrlMap["adios/assets/images/"] = __DIR__."/../Assets/Images/";
    $this->assetsUrlMap["adios/assets/webfonts/"] = __DIR__."/../Assets/Webfonts/";
    $this->assetsUrlMap["adios/assets/widgets/"] = function ($adios, $url) {
      $url = str_replace("adios/assets/widgets/", "", $url);
      preg_match('/(.*?)\/(.+)/', $url, $m);

      $widget = $m[1];
      $asset = $m[2];

      return $adios->widgetsDir."/{$widget}/Assets/{$asset}";
    };
    $this->assetsUrlMap["adios/assets/plugins/"] = function ($adios, $url) {
      $url = str_replace("adios/assets/plugins/", "", $url);
      preg_match('/(.+?)\/~\/(.+)/', $url, $m);

      $plugin = $m[1];
      $asset = $m[2];

      foreach ($adios->pluginFolders as $pluginFolder) {
        $file = "{$pluginFolder}/{$plugin}/Assets/{$asset}";
        if (is_file($file)) {
          return $file;
        }
      }
    };

    //////////////////////////////////////////////////
    // inicializacia

    try {

      // inicializacia debug konzoly
      $this->console = new ($this->getCoreClass('Console'))($this);
      $this->console->clearLog("timestamps", "info");

      // global $gtp; - pouziva sa v basic_functions.php

      $gtp = $this->gtp;

      // nacitanie zakladnych ADIOS lib suborov
      require_once dirname(__FILE__)."/Lib/basic_functions.php";

      if ($mode == self::ADIOS_MODE_FULL) {

        // inicializacia Twigu
        include(dirname(__FILE__)."/Lib/Twig.php");

        $eloquentCapsule = new \Illuminate\Database\Capsule\Manager;

        $eloquentCapsule->addConnection([
          "driver"    => "mysql",
          "host"      => $this->config['db_host'],
          "port"      => $this->config['db_port'],
          "database"  => $this->config['db_name'],
          "username"  => $this->config['db_user'],
          "password"  => $this->config['db_password'],
          "charset"   => 'utf8mb4',
          "collation" => 'utf8mb4_unicode_ci',
        ]);

        // Make this Capsule instance available globally.
        $eloquentCapsule->setAsGlobal();

        // Setup the Eloquent ORM.
        $eloquentCapsule->bootEloquent();

        // Image a file su specialne akcie v tom zmysle, ze nie je
        // potrebne mat nainicializovany cely ADIOS, aby zbehli
        // (ide najma o nepotrebne nacitavanie DB configu)
        // Spustaju sa tu, aby sa setrili zdroje.

        if (
          !empty($this->requestedController)
          && in_array($this->requestedController, ['Image', 'File'])
        ) {
          $this->finalizeConfig();
          include "{$this->requestedController}.php";
          die();
        }
      }

      // inicializacia core modelov
      $this->registerModel($this->getCoreClass("Models\\Config"));
      $this->registerModel($this->getCoreClass("Models\\Translate"));
      $this->registerModel($this->getCoreClass("Models\\User"));
      $this->registerModel($this->getCoreClass("Models\\UserRole"));
      $this->registerModel($this->getCoreClass("Models\\UserHasRole"));
      $this->registerModel($this->getCoreClass("Models\\Token"));

      // inicializacia pluginov - aj pre FULL aj pre LITE mod

      $this->onBeforePluginsLoaded();

      foreach ($this->pluginFolders as $pluginFolder) {
        $this->loadAllPlugins($pluginFolder);
      }

      $this->onAfterPluginsLoaded();

      $this->renderAssets();


      if ($mode == self::ADIOS_MODE_FULL) {

        // start session

        if ($this->config['setSessionTime'] ?? TRUE) {
          ini_set('session.gc_maxlifetime', $this->config['session_maxlifetime'] ?? 60 * 60);
          ini_set('session.gc_probability', $this->config['session_probability'] ?? 1);
          ini_set('session.gc_divisor', $this->config['session_divisor'] ?? 1000);
        }

        ini_set('session.use_cookies', $this->config['sessionUseCookies'] ?? TRUE);

        session_id();
        session_name(_ADIOS_ID);
        session_start();

        define('_SESSION_ID', session_id());
      }

      // inicializacia routera
      $this->router = new ($this->getCoreClass('Router'))($this);

      // inicializacia locale objektu
      $this->locale = new ($this->getCoreClass('Locale'))($this);

      // inicializacia objektu notifikacii
      $this->userNotifications = new ($this->getCoreClass('UserNotifications'))($this);

      // inicializacia mailera
      $this->email = new ($this->getCoreClass('Email'))($this);

      // inicializacia DB - aj pre FULL aj pre LITE mod

      $dbProvider = $this->getConfig('db/provider', '');
      $dbProviderClass = $this->getCoreClass('DB' . (empty($dbProvider) ? '' : '\\Providers\\') . $dbProvider);
      $this->db = new $dbProviderClass($this);

      $this->pdo = new \ADIOS\Core\PDO($this);
      $this->pdo->connect();

      $this->onBeforeConfigLoaded();

      $this->loadConfigFromDB();

      if ($mode == self::ADIOS_MODE_FULL) {

        // set language
        if (!empty($_SESSION[_ADIOS_ID]['language'])) {
          $this->config['language'] = $_SESSION[_ADIOS_ID]['language'];
        }

        if (is_array($this->config['availableLanguages'])) {
          if (!in_array($this->config['language'], $this->config['availableLanguages'])) {
            $this->config['language'] = reset($this->config['availableLanguages']);
          }
        }

        if (empty($this->config['language'])) {
          $this->config['language'] = "en";
        }
      }


      // finalizacia konfiguracie - aj pre FULL aj pre LITE mode
      $this->finalizeConfig();

      $this->onAfterConfigLoaded();

      // object pre kontrolu permissions
      $this->permissions = new ($this->getCoreClass('Permissions'))($this);

      // inicializacia web renderera (byvala CASCADA)
      if (isset($this->config['web']) && is_array($this->config['web'])) {
        $this->web = new ($this->getCoreClass('Web\\Loader'))($this, $this->config['web']);
      }

      // timezone
      date_default_timezone_set($this->config['timezone']);

      if ($mode == self::ADIOS_MODE_FULL) {
        $userModel = new ($this->getCoreClass('Models\\User'))($this);

        if (isset($_POST['passwordReset'])) {
          $email = isset($_POST["email"]) ? $_POST["email"] : "";

          if ($email != "") {
            $userData = $userModel->getByEmail($email);

            if (!empty($userData)) {
              $passwordResetToken =
                $userModel->generatePasswordResetToken(
                  $userData["id"], $email
                )
              ;

              try {
                $this->email = new \ADIOS\Core\Lib\Email(
                  $config["smtp"]["host"],
                  $config["smtp"]["port"]
                );

                $this->email
                  ->setLogin($config["smtp"]["login"], $config["smtp"]["password"])
                  ->setFrom($config["smtp"]["from"])
                ;

                if ($config["smtp"]["protocol"] == 'ssl') {
                  $this->email->setProtocol(\ADIOS\Core\Lib\Email::SSL);
                }

                if ($config["smtp"]["protocol"] == 'tls') {
                  $this->email->setProtocol(\ADIOS\Core\Lib\Email::TLS);
                }

                $this->email->addTo($email);
                $this->email->setSubject(
                  $config["brand"]["title"].
                  " - ".$this->translate("password reset", [], $this)
                );
                $this->email->setHtmlMessage("
                  <h4>
                    {$config["brand"]["title"]}
                    - ". $this->translate("password reset", [], $this)."
                  </h4>
                  <p>"
                    .$this->translate("To recover a forgotten password, click on the link below.", [], $this).
                  "</p>
                  <a href='{$config['url']}/PasswordReset?token={$passwordResetToken}'>
                    {$config['url']}/PasswordReset?token={$passwordResetToken}
                  </a>
                ");
                $this->email->send();
              } catch (\Exception $e) {
                var_dump($e->getMessage());
                exit();
              }

              $this->userPasswordReset["success"] = TRUE;
            } else {
              $this->userPasswordReset["error"] = TRUE;
              $this->userPasswordReset["errorMessage"] =
                $this->translate("The entered e-mail address does not exist.", [], $this)
              ;
            }
          } else {
            $this->userPasswordReset["error"] = TRUE;
            $this->userPasswordReset["errorMessage"] =
              $this->translate("Email cannot be empty. Fill the email field.", [], $this)
            ;
          }
        }

        if (isset($_POST['passwordResetNewPassword'])) {
          $newPassword = isset($_POST["new_password"]) ? $_POST["new_password"] : "";
          $newPassword2 = isset($_POST["new_password_2"]) ? $_POST["new_password_2"] : "";

          // set error to true
          $this->userPasswordReset["error"] = TRUE;

          if ($newPassword == "") {
            $this->userPasswordReset["errorMessage"] =
              $this->translate("New password cannot be empty.", [], $this)
            ;
          } else if ($newPassword2 == "") {
            $this->userPasswordReset["errorMessage"] =
              $this->translate("Repeated new password cannot be empty.", [], $this)
            ;
          } else if ($newPassword != $newPassword2) {
            $this->userPasswordReset["errorMessage"] =
              $this->translate("Entered passwords do not match.", [], $this)
            ;
          } else if (strlen($newPassword) < 8) {
            $this->userPasswordReset["errorMessage"] =
              $this->translate("Minimum password length is 8 characters.", [], $this)
            ;
          } else {
            $this->userPasswordReset["error"] = FALSE;

            $userData = $userModel->validateToken($_GET["token"], true);

            if ($userData) {
              $userModel->updatePassword($userData["id"], $newPassword);

              $userModel->authUser(
                $userData['login'] ?? '',
                $newPassword ?? ''
              );

              header('Location: ' . $this->config['url']);
              exit();
            }
          }
        }

        // user authentication
        if ($this->forceUserLogout) unset($_SESSION[_ADIOS_ID]['userProfile']);

        if ((int) $_SESSION[_ADIOS_ID]['userProfile']['id'] > 0) {
          $user = $userModel->find((int) $_SESSION[_ADIOS_ID]['userProfile']['id']);

          if ($user['is_active'] != 1) {
            $userModel->logoutUser();
          } else {
            $userModel->loadUserFromSession();
            $userModel->updateAccessInformation((int) $this->userProfile['id']);
          }
        } else if (!empty($_POST['login']) && !empty($_POST['password'])) {
          $userModel->authUser(
            $_POST['login'],
            $_POST['password'],
            ((int) $_POST['keep_logged_in']) == 1
          );

          $userModel->updateLoginAndAccessInformation((int) $this->userProfile['id']);
        }

        // v tomto callbacku mozu widgety zamietnut autorizaciu, ak treba
        $this->onUserAuthorised();





        // inicializacia widgetov

        $this->onBeforeWidgetsLoaded();

        $this->addAllWidgets($this->config['widgets']);

        $this->onAfterWidgetsLoaded();

        // vytvorim definiciu tables podla nacitanych modelov

        foreach ($this->models as $modelName) {
          $this->getModel($modelName);
        }

        // inicializacia twigu

        $twigLoader = new ($this->getCoreClass('TwigLoader'))($this);
        $this->twig = new \Twig\Environment($twigLoader, array(
          'cache' => FALSE,
          'debug' => TRUE,
        ));
        $this->twig->addExtension(new \Twig\Extension\StringLoaderExtension());
        $this->twig->addExtension(new \Twig\Extension\DebugExtension());

        $this->twig->addFunction(new \Twig\TwigFunction(
          'ADIOS_Core_Model_getDefaultTableParams',
          function (string $model) {
            $tmpModel = $this->getModel($model);
            return ($tmpModel instanceof \ADIOS\Core\Model ?
              $tmpModel->defaultTableParams
              : ""
            );
          }
        ));
        $this->twig->addFunction(new \Twig\TwigFunction(
          'ADIOS_Core_Model_getDefaultFormParams',
          function (string $model) {
            $tmpModel = $this->getModel($model);
            return ($tmpModel instanceof \ADIOS\Core\Model ?
              $tmpModel->defaultFormParams
              : ""
            );
          }
        ));
        $this->twig->addFunction(new \Twig\TwigFunction(
          'str2url',
          function ($string) {
            return \ADIOS\Core\HelperFunctions::str2url($string);
          }
        ));
        $this->twig->addFunction(new \Twig\TwigFunction(
          'hasPermission',
          function (string $permission, array $idUserRoles = []) {
            return $this->permissions->has($permission, $idUserRoles);
          }
        ));
        $this->twig->addFunction(new \Twig\TwigFunction(
          'hasRole',
          function (int|string $role) {
            return $this->permissions->hasRole($role);
          }
        ));
        $this->twig->addFunction(new \Twig\TwigFunction(
          'translate',
          function ($string, $objectClassName = "") {
            if (!class_exists($objectClassName)) {
              $object = $this->controllerObject;
            } else {
              $object = new $objectClassName($this);
            }

            return $this->translate($string, [], $object);
          }
        ));
        $this->twig->addFunction(new \Twig\TwigFunction(
          'adiosView',
          function ($uid, $view, $params) {
            if (!is_array($params)) {
              $params = [];
            }
            return $this->view->create(
              $view . (empty($uid) ? '' : '#' . $uid),
              $params
            )->render();
          }
        ));
        $this->twig->addFunction(new \Twig\TwigFunction(
          'adiosRender',
          function ($controller, $params = []) {
            return $this->render($controller, $params);
          }
        ));

        // inicializacia view
        $this->view = new ($this->getCoreClass('ViewWithController'))($this);
      }

      $this->dispatchEventToPlugins("onADIOSAfterInit", ["adios" => $this]);
    } catch (\Exception $e) {
      exit("ADIOS INIT failed: [".get_class($e)."] ".$e->getMessage());
    }

    return $this;
  }

  public function isAjax() {
    return isset($_REQUEST['__IS_AJAX__']) && $_REQUEST['__IS_AJAX__'] == "1";
  }

  public function isNestedController() {
    return ($this->controllerNestingLevel > 2);
  }

  public function isWindow() {
    return isset($_REQUEST['__IS_WINDOW__']) && $_REQUEST['__IS_WINDOW__'] == "1";
  }

  public function getCoreClass($class): string {
    return $this->config['coreClasses'][$class] ?? ('\\ADIOS\\Core\\' . $class);
  }

  //////////////////////////////////////////////////////////////////////////////
  // WIDGETS

  public function addWidget($widgetName) {
    if (!isset($this->widgets[$widgetName])) {
      try {
        $widgetClassName = "\\App\\Widgets\\".str_replace("/", "\\", $widgetName);
        if (!class_exists($widgetClassName)) {
          throw new \Exception("Widget {$widgetName} not found.");
        }
        $this->widgets[$widgetName] = new $widgetClassName($this);

        $this->router->addRouting($this->widgets[$widgetName]->routing());
      } catch (\Exception $e) {
        exit("Failed to load widget {$widgetName}: ".$e->getMessage());
      }
    }
  }

  public function addAllWidgets(array $widgets = [], $path = "") {
    foreach ($widgets as $wName => $w_config) {
      $fullWidgetName = ($path == "" ? "" : "{$path}/").$wName;
      if (isset($w_config['enabled']) && $w_config['enabled'] === TRUE) {
        $this->addWidget($fullWidgetName);
      } else {
        // ak nie je enabled, moze to este byt dalej vetvene
        if (is_array($w_config)) {
          $this->addAllWidgets($w_config, $fullWidgetName);
        }
      }
    }
  }

  //////////////////////////////////////////////////////////////////////////////
  // MODELS

  public function registerModel($modelName): void
  {
    if (!in_array($modelName, $this->models)) {
      $this->models[] = $modelName;
    }
  }

  public function getModelNames(): array
  {
    return $this->models;
  }

  public function getModelClassName($modelName): string
  {
    return str_replace("/", "\\", $modelName);
  }

  /**
   * Returns the object of the model referenced by $modelName.
   * The returned object is cached into modelObjects property.
   *
   * @param  string $modelName Reference of the model. E.g. 'ADIOS/Core/Models/User'.
   * @throws \ADIOS\Core\Exception If $modelName is not available.
   * @return object Instantiated object of the model.
   */
  public function getModel(string $modelName): \ADIOS\Core\Model {
    if (!isset($this->modelObjects[$modelName])) {
      try {
        $modelClassName = $this->getModelClassName($modelName);
        $this->modelObjects[$modelName] = new $modelClassName($this);

        $this->router->addRouting($this->modelObjects[$modelName]->routing());

      } catch (\Exception $e) {
        throw new \ADIOS\Core\Exceptions\GeneralException("Can't find model '{$modelName}'. ".$e->getMessage());
      }
    }

    return $this->modelObjects[$modelName];
  }

  //////////////////////////////////////////////////////////////////////////////
  // PLUGINS

  public function registerPluginFolder($folder) {
    if (is_dir($folder) && !in_array($folder, $this->pluginFolders)) {
      $this->pluginFolders[] = $folder;
    }
  }

  public function getPluginClassName($pluginName) {
    return "\\ADIOS\\Plugins\\".str_replace("/", "\\", $pluginName);
  }

  public function getPlugin($pluginName) {
    return $this->pluginObjects[$pluginName] ?? NULL;
  }

  public function getPlugins() {
    return $this->pluginObjects;
  }

  public function loadAllPlugins($pluginFolder, $subFolder = "") {
    $folder = $pluginFolder.(empty($subFolder) ? "" : "/{$subFolder}");

    foreach (scandir($folder) as $file) {
      if (strpos($file, ".") !== FALSE) continue;

      $fullPath = (empty($subFolder) ? "" : "{$subFolder}/").$file;

      if (
        is_dir("{$folder}/{$file}")
        && !is_file("{$folder}/{$file}/Main.php")
      ) {
        $this->loadAllPlugins($pluginFolder, $fullPath);
      } else if (is_file("{$folder}/{$file}/Main.php")) {
        try {
          $tmpPluginClassName = $this->getPluginClassName($fullPath);

          if (class_exists($tmpPluginClassName)) {
            $this->plugins[] = $fullPath;
            $this->pluginObjects[$fullPath] = new $tmpPluginClassName($this);
          }
        } catch (\Exception $e) {
          exit("Failed to load plugin {$fullPath}: ".$e->getMessage());
        }
      }
    }
  }

  //////////////////////////////////////////////////////////////////////////////
  // TRANSLATIONS

  public function loadDictionary($object, $toLanguage = "") {
    $dictionary = [];
    $dictionaryFolder = $object->dictionaryFolder ?? "";

    if (empty($toLanguage)) {
      $toLanguage = $this->config['language'] ?? "";
    }

    if (empty($dictionaryFolder)) {
      $dictionaryFolder = "{$this->config['srcDir']}/Lang";
    }

    if (strlen($toLanguage) == 2) {
      if (empty($object->dictionaryFilename)) {
        $dictionaryFilename = strtr(get_class($object), "./\\", "---");
      } else {
        $dictionaryFilename = $object->dictionaryFilename;
      }

      $dictionaryFile = "{$dictionaryFolder}/{$toLanguage}/{$dictionaryFilename}.php";

      if (file_exists($dictionaryFile)) {
        include($dictionaryFile);
      } else {
        // echo("{$dictionaryFile} does not exist ({$object->name})\n");
      }
    }

    return $dictionary;
  }

  public function translate(string $string, array $vars, $object = NULL, $toLanguage = ""): string {
    if ($object === NULL) $object = $this;
    if (empty($toLanguage)) {
      $toLanguage = $this->config['language'] ?? "en";
    }

    if ($toLanguage == "en") {
      return $string;
    }

    if (empty($this->dictionary[$toLanguage])) {
      $this->dictionary[$toLanguage] = [];

      $dictionaryFiles = \ADIOS\Core\HelperFunctions::scanDirRecursively("{$this->config['srcDir']}/Lang");

      foreach ($dictionaryFiles as $file) {
        include("{$this->config['srcDir']}/Lang/{$file}");

        $this->dictionary[$toLanguage] =  \ADIOS\Core\HelperFunctions::arrayMergeRecursively(
          $this->dictionary[$toLanguage],
          $dictionary
        );
      }
    }

    $dictionary = $this->dictionary[$toLanguage] ?? [];
    $objectClassName = get_class($object);
    foreach (explode("\\", $objectClassName) as $namespaceItem) {
      if (is_array($dictionary[$namespaceItem])) {
        $dictionary = $dictionary[$namespaceItem];
      } else {
        break;
      }
    }

    if (!isset($dictionary[$string])) {
      $translated = $string;
      if ($this->getConfig('debugTranslations', FALSE)) {
        $translated .= ' ' . get_class($object);
      }
    } else {
      $translated = $dictionary[$string];
    }

    foreach ($vars as $varName => $varValue) {
      $translated = str_replace('{{ ' . $varName . ' }}', $varValue, $translated);
    }

    return $translated;
  }

  //////////////////////////////////////////////////////////////////////////////
  // MISCELANEOUS

  public function renderAssets() {
    $cachingTime = 3600;
    $headerExpires = "Expires: ".gmdate("D, d M Y H:i:s", time() + $cachingTime)." GMT";
    $headerCacheControl = "Cache-Control: max-age={$cachingTime}";

    if ($this->requestedUri == "adios/cache.css") {
      $cssCache = $this->renderCSSCache();

      header("Content-type: text/css");
      header("ETag: ".md5($cssCache));
      header($headerExpires);
      header("Pragma: cache");
      header($headerCacheControl);

      echo $cssCache;

      exit();
    } else if ($this->requestedUri == "adios/cache.js") {
      $jsCache = $this->renderJSCache();
      $cachingTime = 3600;

      header("Content-type: text/js");
      header("ETag: ".md5($jsCache));
      header($headerExpires);
      header("Pragma: cache");
      header($headerCacheControl);

      echo $jsCache;

      exit();
    } else if ($this->requestedUri == "adios/react.js") {
      $jsCache = $this->renderReactJsBundle();
      $cachingTime = 3600;

      header("Content-type: text/js");
      header("ETag: ".md5($jsCache));
      header($headerExpires);
      header("Pragma: cache");
      header($headerCacheControl);

      echo $jsCache;

      exit();
    } else {
      foreach ($this->assetsUrlMap as $urlPart => $mapping) {
        if (preg_match('/^'.str_replace("/", "\\/", $urlPart).'/', $this->requestedUri, $m)) {

          if ($mapping instanceof \Closure) {
            $sourceFile = $mapping($this, $this->requestedUri);
          } else {
            $sourceFile = $mapping.str_replace($urlPart, "", $this->requestedUri);
          }

          $ext = strtolower(pathinfo($this->requestedUri, PATHINFO_EXTENSION));

          switch ($ext) {
            case "css":
            case "js":
              header("Content-type: text/{$ext}");
              header($headerExpires);
              header("Pragma: cache");
              header($headerCacheControl);
              echo file_get_contents($sourceFile);
              break;
            case "eot":
            case "ttf":
            case "woff":
            case "woff2":
              header("Content-type: font/{$ext}");
              header($headerExpires);
              header("Pragma: cache");
              header($headerCacheControl);
              echo file_get_contents($sourceFile);
              break;
            case "bmp":
            case "gif":
            case "jpg":
            case "jpeg":
            case "png":
            case "tiff":
            case "webp":
            case "svg":
              if ($ext == "svg") {
                $contentType = "svg+xml";
              } else {
                $contentType = $ext;
              }

              header("Content-type: image/{$contentType}");
              header($headerExpires);
              header("Pragma: cache");
              header($headerCacheControl);
              echo file_get_contents($sourceFile);
              break;
          }

          exit();
        }
      }
    }
  }

  public function install() {
    $this->console->clear();

    $installationStart = microtime(TRUE);

    $this->console->info("Dropping existing tables.");

    foreach ($this->models as $modelName) {
      $model = $this->getModel($modelName);
      $model->dropTableIfExists();
    }

    $this->console->info("Database is empty, installing models.");

    $this->db->startTransaction();

    foreach ($this->models as $modelName) {
      try {
        $model = $this->getModel($modelName);

        $start = microtime(TRUE);

        $model->install();
        $this->console->info("Model {$modelName} installed.", ["duration" => round((microtime(true) - $start) * 1000, 2)." msec"]);
      } catch (\ADIOS\Core\Exceptions\ModelInstallationException $e) {
        $this->console->warning("Model {$modelName} installation skipped.", ["exception" => $e->getMessage()]);
      } catch (\Exception $e) {
        $this->console->error("Model {$modelName} installation failed.", ["exception" => $e->getMessage()]);
      } catch (\Illuminate\Database\QueryException $e) {
        //
      } catch (\ADIOS\Core\Exceptions\DBException $e) {
        // Moze sa stat, ze vytvorenie tabulky zlyha napr. kvoli
        // "Cannot add or update a child row: a foreign key constraint fails".
        // V takom pripade budem instalaciu opakovat v dalsom kole
      }
    }

    foreach ($this->models as $modelName) {
      try {
        $model = $this->getModel($modelName);

        $start = microtime(TRUE);

        $model->createSqlForeignKeys();
        $this->console->info("Indexes for model {$modelName} installed.", ["duration" => round((microtime(true) - $start) * 1000, 2)." msec"]);
      } catch (\Exception $e) {
        $this->console->error("Indexes installation for model {$modelName} failed.", ["exception" => $e->getMessage()]);
      } catch (\Illuminate\Database\QueryException $e) {
        //
      } catch (\ADIOS\Core\Exceptions\DBException $e) {
        //
      }
    }

    foreach ($this->widgets as $widget) {
      try {
        if ($widget->install()) {
          $this->widgetsInstalled[$widget->name] = TRUE;
          $this->console->info("Widget {$widget->name} installed.", ["duration" => round((microtime(true) - $start) * 1000, 2)." msec"]);
        } else {
          $this->console->warning("Model {$modelName} installation skipped.");
        }
      } catch (\Exception $e) {
        $this->console->error("Model {$modelName} installation failed.");
      } catch (\ADIOS\Core\Exceptions\DBException $e) {
        // Moze sa stat, ze vytvorenie tabulky zlyha napr. kvoli
        // "Cannot add or update a child row: a foreign key constraint fails".
        // V takom pripade budem instalaciu opakovat v dalsom kole
      }

      $this->dispatchEventToPlugins("onWidgetAfterInstall", [
        "widget" => $widget,
      ]);
    }

    $this->db->commit();

    $this->console->info("Core installation done in ".round((microtime(true) - $installationStart), 2)." seconds.");
  }

  // funkcia render() zabezpecuje:
  //   - routing podla a) (ne)prihlaseny user, b) $this->requestedController, c) $_REQUEST['__IS_AJAX__']
  //   - kontrolu requestu podla $_REQUEST['c']
  //   - vygenerovanie UID
  //   - renderovanie naroutovanej akcie

  /**
   * Renders the requested content. It can be the (1) whole desktop with complete <html>
   * content; (2) the HTML of a controller requested dynamically using AJAX; or (3) a JSON
   * string requested dynamically using AJAX and further processed in Javascript.
   *
   * @param  mixed $params Parameters (a.k.a. arguments) of the requested controller.
   * @throws \ADIOS\Core\Exception When no controller is specified or requested controller is unknown.
   * @throws \ADIOS\Core\Exception When running in CLI and requested controller is blocked for the CLI.
   * @throws \ADIOS\Core\Exception When running in SAPI and requested controller is blocked for the SAPI.
   * @return string Rendered content.
   */
  public function render(string $controller = '', array $params = []) {

    if (preg_match('/(\w+)\/Cron\/(\w+)/', $this->requestedUri, $m)) {
      $cronClassName = str_replace("/", "\\", "/App/Widgets/{$m[0]}");

      if (class_exists($cronClassName)) {
        (new $cronClassName($this))->run();
      } else {
        echo "Unknown cron '{$this->requestedUri}'.";
      }

      exit();
    }

    try {

      if (empty($controller)) {
        if (php_sapi_name() === 'cli') {
          $params = @json_decode($_SERVER['argv'][2] ?? "", TRUE);
          if (!is_array($params)) { // toto nastane v pripade, ked $_SERVER['argv'] nie je JSON string
            $params = $_SERVER['argv'];
          }
          $this->controller = $_SERVER['argv'][1] ?? "";
        } else {
          $this->controller = $_REQUEST['controller'] ?? '';
          // $params = $_REQUEST;
          $params = \ADIOS\Core\HelperFunctions::arrayMergeRecursively(
            array_merge($_GET, $_POST),
            json_decode(file_get_contents("php://input"), true) ?? []
          );
          // echo"x---"; var_dump(json_decode(file_get_contents("php://input"), true) ?? []);exit;
          unset($params['controller']);
        }
      } else {
        $this->controller = $controller;
      }

      list($this->controller, $this->params) = $this->router->applyRouting($this->controller, $params);

      $controllerClassName = $this->getControllerClassName($this->controller);

      if (!class_exists($controllerClassName)) {
        throw new \ADIOS\Core\Exceptions\GeneralException("Unknown controller '{$this->controller}'.");
      }

      if (php_sapi_name() === 'cli') {
        if (!$controllerClassName::$cliSAPIEnabled) {
          throw new \ADIOS\Core\Exceptions\GeneralException("Controller is not available for CLI interface.");
        }
      } else {
        if (!$controllerClassName::$webSAPIEnabled) {
          throw new \ADIOS\Core\Exceptions\GeneralException("Controller is not available for WEB interface.");
        }
      }

      // mam moznost upravit config (napr. na skrytie desktopu alebo upravu permissions)
      $this->config = $controllerClassName::overrideConfig($this->config, $this->params);

      if ($this->params['__IS_AJAX__']) {
        // tak nic
      } else if (
        !$this->getConfig("hideDesktop", FALSE)
        && !$controllerClassName::$hideDefaultDesktop
        && $this->controllerNestingLevel == 0
      ) {
        // treba nacitat cely desktop, ak to nie je zakazane v config alebo v akcii
        $this->params['contentController'] = $this->controller;
        $this->params['config'] = $this->config;
        $this->params['_COOKIE'] = $_COOKIE;

        $this->controller = $this->config['defaultDesktopController'] ?? 'Desktop';
      }

      if (
        !$this->userLogged
        && $controllerClassName::$requiresUserAuthentication
      ) {
        $this->controller = "Login";
      }

      if (empty($this->controller)) {
        $this->controller = $this->config['defaultDesktopController'] ?? 'Desktop';
      }

      // Kontrola permissions

      $this->router->checkPermissions($this->controller);

      // All OK, rendering content...

      // vygenerovanie UID tohto behu
      if (empty($this->uid)) {
        $uid = $this->getUid($this->params['id']);
      } else {
        $uid = $this->uid.'__'.$this->getUid($this->params['id']);
      }

      $this->setUid($uid);

      if (in_array($this->controller, array_keys($this->config['widgets']))) {
        $this->controller = "{$this->controller}/Main";
      }





      $this->controllerNestingLevel++;
      $this->controllerStack[] = $this->controller;

      $controllerClassName = $this->getControllerClassName($this->controller);

      $return = '';

      $this->dispatchEventToPlugins("onADIOSBeforeRender", ["adios" => $this]);

      try {
        if ($this->controllerExists($this->controller)) {
          $this->controllerObject = new $controllerClassName($this, $this->params);

          if (
            $controllerClassName::$requiresUserAuthentication
            && !$this->permissions->has($this->controllerObject->permissionName)
          ) {
            throw new \ADIOS\Core\Exceptions\NotEnoughPermissionsException($this->controllerObject->permissionName);
          }

          $this->onBeforeRender();

          foreach ($this->widgets as $widget) {
            $widget->onBeforeRender();
          }

          $json = $this->controllerObject->renderJson();

          if (is_array($json)) {
            $return = json_encode($json);
          } else {
            [$view, $viewParams] = $this->controllerObject->prepareViewAndParams();

            if (is_string($view)) {
              if (substr($view, 0, 3) == 'App') {
                $canUseTwig = is_file($this->config['dir'] . '/' . str_replace('App', 'src', $view) . '.twig');
              } else if (substr($view, 0, 5) == 'ADIOS') {
                $canUseTwig = is_file(__DIR__ . '/..' . str_replace('ADIOS', '', $view) . '.twig');
              } else {
                $canUseTwig = FALSE;
              }

              if ($canUseTwig) {
                $html = $this->twig->render(
                  $view,
                  [
                    'uid' => $this->uid,
                    'user' => $this->userProfile,
                    'config' => $this->config,
                    'viewParams' => $viewParams,
                    'windowParams' => $viewParams['windowParams'] ?? NULL,
                  ]
                );
              } else {
                $html = $this->view->create(
                  $view,
                  $viewParams
                )->render();
              };

              return $html;
            } else {
              $renderReturn = $this->controllerObject->render($this->params);

              if ($renderReturn === NULL) {
                // akcia nic nereturnovala, iba robila echo
                $return = "";
              } else if (is_string($renderReturn)) {
                $return = $renderReturn;
              } else {
                $return = $this->renderReturn($renderReturn);
              }
            }
          }

          $this->onAfterRender();

          foreach ($this->widgets as $widget) {
            $widget->onAfterRender();
          }
        }
      } catch (\ADIOS\Core\Exceptions\NotEnoughPermissionsException $e) {
        $return = $this->renderFatal("Not enough permissions: ".$e->getMessage(), FALSE);
        header('HTTP/1.1 401 Unauthorized', true, 401);
      } catch (\Exception $e) {
        $error = error_get_last();

        if ($error['type'] == E_ERROR) {
          $return = $this->renderFatal(
            '<div style="margin-bottom:1em;">'
              . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']
            . '</div>'
            . '<pre style="font-size:0.75em;font-family:Courier New">'
              . $e->getTraceAsString()
            . '</pre>',
            TRUE
          );
        } else {
          $return = $this->renderFatal($this->renderExceptionHtml($e));
        }

        header('HTTP/1.1 400 Bad Request', true, 400);
      }

      return $return;

    } catch (\ADIOS\Core\Exceptions\NotEnoughPermissionsException $e) {
      header('HTTP/1.1 401 Unauthorized', true, 401);
      return $this->renderFatal($e->getMessage(), FALSE);
    } catch (\ADIOS\Core\Exceptions\GeneralException $e) {
      $lines = [];
      $lines[] = "ADIOS RUN failed: [".get_class($e)."] ".$e->getMessage();
      if ($this->config['debug']) {
        $lines[] = "Requested URI = {$this->requestedUri}";
        $lines[] = "Rewrite base = {$this->config['rewriteBase']}";
        $lines[] = "SERVER.REQUEST_URI = {$this->config['requestUri']}";
      }

      header('HTTP/1.1 400 Bad Request', true, 400);
      return join(" ", $lines);
    } catch (\ArgumentCountError $e) {
      echo $e->getMessage();
      // var_dump(debug_backtrace());
      header('HTTP/1.1 400 Bad Request', true, 400);
    }
  }

  public function getControllerClassName(string $controller) : string {

    $controllerPathParts = [];
    foreach (explode("/", $controller) as $controllerPathPart) {
      // convert-dash-string-toCamelCase
      $controllerPathParts[] = str_replace(' ', '', ucwords(str_replace('-', ' ', $controllerPathPart)));
    }
    $controller = join("/", $controllerPathParts);

    $controllerClassName = '';

    // Dusan 31.5.2023: Tento sposob zapisu akcii je zjednoteny so sposobom zapisu modelov.
    foreach (array_keys($this->widgets) as $widgetName) {
      if (strpos(strtolower($controller), strtolower($widgetName)) === 0) {
        $controllerClassName =
          '\\App\\Widgets\\'
          . $widgetName
          . '\\Controllers\\'
          . substr($controller, strlen($widgetName) + 1)
        ;
      }
    }
    $controllerClassName = str_replace('/', '\\', $controllerClassName);

    if (!class_exists($controllerClassName)) {
      // Dusan 31.5.2023: Tento sposob zapisu akcii je deprecated.
      $controllerClassName = 'ADIOS\\Controllers\\' . str_replace('/', '\\', $controller);

      // $this->console->warning('[ADIOS] Deprecated class name for controller ' . $controller . '.');
    }

    return $controllerClassName;
  }

  public function controllerExists(string $controller) : bool {
    return class_exists($this->getControllerClassName($controller));
  }

  /**
   * Checks user permissions before rendering requested controller.
   * Original implementation does nothing. Must be overriden
   * the application's main class.
   *
   * Does not return anything, only throws exceptions.
   *
   * @abstract
   * @param string $controller Name of the controller to be rendered.
   * @param array $params Parameters (a.k.a. arguments) of the controller.
   * @throws \ADIOS\Core\NotEnoughPermissionsException When the signed user does not have enough permissions.
   * @return void
   */
  public function checkPermissionsForController($controller, $params = NULL) {
    // to be overriden
  }

  public function renderReturn($return) {
    // if ($this->isAjax() && !$this->isWindow()) {
      return json_encode([
        "result" => "success",
        "message" => $return,
      ]);
    // } else {
    //   return $return;
    // }
  }

  public function renderWarning($message, $isHtml = TRUE) {
    if ($this->isAjax() && !$this->isWindow()) {
      return json_encode([
        "status" => "warning",
        "message" => $message,
      ]);
    } else {
      return "
        <div class='alert alert-warning' role='alert'>
          ".($isHtml ? $message : hsc($message))."
        </div>
      ";
    }
  }

  public function renderFatal($message, $isHtml = TRUE) {
    if ($this->isAjax() && !$this->isWindow()) {
      return json_encode([
        "status" => "error",
        "message" => $message,
      ]);
    } else {
      return "
        <div class='alert alert-danger' role='alert' style='z-index:99999999'>
          ".($isHtml ? $message : hsc($message))."
        </div>
      ";
    }
  }

  public function renderHtmlFatal($message) {
    return $this->renderFatal($message, TRUE);
  }


  public function renderExceptionHtml($exception) {

    $traceLog = "";
    foreach ($exception->getTrace() as $item) {
      $traceLog .= "{$item['file']}:{$item['line']}\n";
    }

    $errorMessage = $exception->getMessage();
    $errorHash = md5(date("YmdHis").$errorMessage);

    $errorDebugInfoHtml = "
      <div class='adios exception debug'>
        Error hash: {$errorHash} (see error log file for more information)<br/>
        ".get_class($exception)."<br/>
        Stack trace:<br/>
        <div class='trace-log'>{$traceLog}</div>
      </div>
    ";

    $showMoreInformationButton = "
      <a href='javascript:void(0);' onclick='$(this).next(\"div\").show(); $(this).hide();'>
        ".$this->translate("Show more information", [], $this)."
      </a>
    ";

    // $this->console->error("{$errorHash}\t{$errorMessage}\t{$this->db->last_query}\t{$this->db->db_error}");

    switch (get_class($exception)) {
      case 'ADIOS\Core\Exceptions\DBException':
        $html = "
          <div class='adios exception emoji'>🥴</div>
          <div class='adios exception message'>
            Oops! Something went wrong with the database.
            See logs for more information or contact the support.<br/>
          </div>
          <div class='adios exception message'>
            {$errorMessage}
          </div>
          {$showMoreInformationButton}
          <div style='display:none' class='adios exception more-information'>
            {$errorDebugInfoHtml}
          </div>
        ";
      break;
      case 'Illuminate\Database\QueryException':
      case 'ADIOS\Core\Exceptions\DBDuplicateEntryException':

        if (get_class($exception) == 'Illuminate\Database\QueryException') {
          $dbQuery = $exception->getSql();
          $dbError = $exception->errorInfo[2];
          $errorNo = $exception->errorInfo[1];
        } else {
          list($dbError, $dbQuery, $initiatingModelName, $errorNo) = json_decode($exception->getMessage(), TRUE);
        }

        $invalidColumns = [];

        if (!empty($initiatingModelName)) {
          $initiatingModel = $this->getModel($initiatingModelName);
          $columns = $initiatingModel->columns();
          $indexes = $initiatingModel->indexes();

          preg_match("/Duplicate entry '(.*?)' for key '(.*?)'/", $dbError, $m);
          $invalidIndex = $m[2];
          $invalidColumns = [];
          foreach ($indexes[$invalidIndex]['columns'] as $columnName) {
            $invalidColumns[] = $columns[$columnName]["title"];
          }
        } else {
          preg_match("/Duplicate entry '(.*?)' for key '(.*?)'/", $dbError, $m);
          $invalidColumns = [$m[2]];
        }

        switch ($errorNo) {
          case 1216:
          case 1451:
            $errorMessage = "You are trying to delete a record that is linked with another record(s).";
            break;
          case 1062:
          case 1217:
          case 1452:
            $errorMessage = "You are trying to save a record that is already existing.";
            break;
        }

        $html = "
          <div class='adios exception emoji'>🥴</div>
          <div class='adios exception message'>
            ".$this->translate($errorMessage, [], $this)."<br/>
            <br/>
            <b>".join(", ", $invalidColumns)."</b>
          </div>
          {$showMoreInformationButton}
          <div style='display:none' class='adios exception more-information'>
            {$dbError}
            {$errorDebugInfoHtml}
          </div>
        ";
      break;
      default:
        $html = "
          <div class='adios exception emoji'>🥴</div>
          <div class='adios exception message'>
            Oops! Something went wrong.
            See logs for more information or contact the support.<br/>
          </div>
          <div class='adios exception message'>
            ".$exception->getMessage()."
          </div>
          {$showMoreInformationButton}
          <div style='display:none' class='adios exception more-information'>
            {$errorDebugInfoHtml}
          </div>
        ";
      break;
    }

    return $html;//$this->renderHtmlWarning($html);
  }

  public function renderHtmlWarning($warning) {
    return $this->renderWarning($warning, TRUE);
  }

  /**
   * Propagates an event to all plugins of the application. Each plugin can
   * implement hook for the event. The hook must return either modified event
   * data of FALSE. Returning FALSE in the hook terminates the event propagation.
   *
   * @param  string $eventName Name of the event to propagate.
   * @param  array $eventData Data of the event. Each event has its own specific structure of the data.
   * @throws \ADIOS\Core\Exception When plugin's hook returns invalid value.
   * @return array<string, mixed> Event data modified by plugins which implement the hook.
   */
  public function dispatchEventToPlugins(string $eventName, array $eventData = []): array
  {
    foreach ($this->pluginObjects as $plugin) {
      if (method_exists($plugin, $eventName)) {
        $eventData = $plugin->$eventName($eventData);
        if (!is_array($eventData) && $eventData !== FALSE) {
          throw new \ADIOS\Core\Exceptions\GeneralException("Plugin {$plugin->name}, event {$eventName}: No value returned. Either forward \$event or return FALSE.");
        }

        if ($eventData === FALSE) {
          break;
        }
      }
    }
    return $eventData;
  }

  public function hasPermissionForController($controller, $params) {
    return TRUE;
  }

  ////////////////////////////////////////////////
  // metody pre pracu s konfiguraciou

  public function getConfig($path, $default = NULL) {
    $retval = $this->config;
    foreach (explode('/', $path) as $key => $value) {
      if (isset($retval[$value])) {
        $retval = $retval[$value];
      } else {
        $retval = null;
      }
    }
    return ($retval === NULL ? $default : $retval);
  }

  public function setConfig($path, $value) {
    $path_array = explode('/', $path);

    $cfg = &$this->config;
    foreach ($path_array as $path_level => $path_slice) {
      if ($path_level == count($path_array) - 1) {
        $cfg[$path_slice] = $value;
      } else {
        if (empty($cfg[$path_slice])) {
          $cfg[$path_slice] = NULL;
        }
        $cfg = &$cfg[$path_slice];
      }
    }
  }

  // TODO: toto treba prekontrolovat, velmi pravdepodobne to nefunguje
  // public function mergeConfig($config_to_merge) {
  //   if (is_array($config_to_merge)) {
  //     foreach ($config_to_merge as $key => $value) {
  //       if (is_array($value)) {
  //         $this->config[$key] = $this->mergeConfig($config_original[$key], $value);
  //       } else {
  //         $this->config[$key] = $value;
  //       }
  //     }
  //   }

  //   return $this->config;
  // }

  public function saveConfig(array $config, string $path = '') {
    try {
      if (is_array($config)) {
        foreach ($config as $key => $value) {
          $tmpPath = $path.$key;

          if (is_array($value)) {
            $this->saveConfig($value, $tmpPath.'/');
          } else if ($value === NULL) {
            $this->db->query("
              delete from `".(empty($this->gtp) ? '' : $this->gtp . '_')."_config`
              where `path` like '".$this->db->escape($tmpPath)."%'
            ");
          } else {
            $this->db->query("
              insert into `".(empty($this->gtp) ? '' : $this->gtp . '_')."_config` set
                `path` = '".$this->db->escape($tmpPath)."',
                `value` = '".$this->db->escape($value)."'
              on duplicate key update
                `path` = '".$this->db->escape($tmpPath)."',
                `value` = '".$this->db->escape($value)."'
            ");
          }
        }
      }
    } catch (\Exception $e) {
      // do nothing
    }
  }

  public function saveConfigByPath(string $path, $value) {
    try {
      if (!empty($path)) {
        $this->db->query("
          insert into `".(empty($this->gtp) ? '' : $this->gtp . '_')."_config` set
            `path` = '".$this->db->escape($path)."',
            `value` = '".$this->db->escape($value)."'
          on duplicate key update
            `path` = '".$this->db->escape($path)."',
            `value` = '".$this->db->escape($value)."'
        ");
      }
    } catch (\Exception $e) {
      // do nothing
    }
  }

  public function deleteConfig($path) {
    try {
      if (!empty($path)) {
        $this->db->query("
          delete from `".(empty($this->gtp) ? '' : $this->gtp . '_')."_config`
          where `path` like '".$this->db->escape($path)."%'
        ");
      }
    } catch (\Exception $e) {
      // do nothing
    }
  }

  public function loadConfigFromDB() {
    try {
      $queryOk = $this->db->query("
        select
          *
        from `".(empty($this->gtp) ? '' : $this->gtp . '_')."_config`
        order by id asc
      ");

      if ($queryOk) {
        while ($row = $this->db->fetchArray()) {
          $tmp = &$this->config;
          foreach (explode("/", $row['path']) as $tmp_path) {
            if (!is_array($tmp[$tmp_path])) {
              $tmp[$tmp_path] = [];
            }
            $tmp = &$tmp[$tmp_path];
          }
          $tmp = $row['value'];
        }
      }
    } catch (\Exception $e) {
      // do nothing
    }
  }

  public function finalizeConfig() {
    // various default values
    $this->config['widgets'] = $this->config['widgets'] ?? [];
    $this->config['protocol'] = (strtoupper($_SERVER['HTTPS'] ?? "") == "ON" ? "https" : "http");
    $this->config['timezone'] = $this->config['timezone'] ?? 'Europe/Bratislava';

    $this->config['uploadDir'] = $this->config['uploadDir'] ?? "{$this->config['dir']}/upload";
    $this->config['uploadUrl'] = $this->config['uploadUrl'] ?? "{$this->config['url']}/upload";

    $this->config['uploadDir'] = str_replace("\\", "/", $this->config['uploadDir']);
  }

  public function onUserAuthorised() {
    // to be overriden
  }

  public function onBeforeConfigLoaded() {
    // to be overriden
  }

  public function onAfterConfigLoaded() {
    // to be overriden
  }

  public function onBeforeWidgetsLoaded() {
    // to be overriden
  }

  public function onAfterWidgetsLoaded() {
    // to be overriden
  }

  public function onBeforePluginsLoaded() {
    // to be overriden
  }

  public function onAfterPluginsLoaded() {
    // to be overriden
  }

  public function onBeforeRender() {
    // to be overriden
  }

  public function onAfterRender() {
    // to be overriden
  }

  ////////////////////////////////////////////////



  public function getUid($uid = '') {
    if (empty($uid)) {
      $tmp = $this->controller.'-'.time().rand(100000, 999999);
    } else {
      $tmp = $uid;
    }

    $tmp = str_replace('/', '-', $tmp);

    $uid = "";
    for ($i = 0; $i < strlen($tmp); $i++) {
      if ($tmp[$i] == "-") {
        $uid .= strtoupper($tmp[++$i]);
      } else {
        $uid .= $tmp[$i];
      }
    }

    $this->setUid($uid);

    return $uid;
  }

  /**
   * Checks the argument whether it is a valid ADIOS UID string.
   *
   * @param  string $uid The string to validate.
   * @throws \ADIOS\Core\Exceptions\InvalidUidException If the provided string is not a valid ADIOS UID string.
   * @return void
   */
  public function checkUid($uid) {
    if (preg_match('/[^A-Za-z0-9\-_]/', $uid)) {
      throw new \ADIOS\Core\Exceptions\InvalidUidException();
    }
  }

  public function setUid($uid) {
    $this->checkUid($uid);
    $this->uid = $uid;
  }

  public function renderCSSCache() {
    $css = "";

    $cssFiles = [
      dirname(__FILE__)."/../Assets/Css/fontawesome-5.13.0.css",
      dirname(__FILE__)."/../Assets/Css/bootstrap.min.css",
      //dirname(__FILE__)."/../Assets/Css/bootstrapmd.min.css",
      dirname(__FILE__)."/../Assets/Css/sb-admin-2.css",
      dirname(__FILE__)."/../Assets/Css/responsive.css",
      dirname(__FILE__)."/../Assets/Css/adios-react-ui.css",
      dirname(__FILE__)."/../Assets/Css/colors.css",
      dirname(__FILE__)."/../Assets/Css/desktop.css",
      dirname(__FILE__)."/../Assets/Css/jquery-ui.structure.css",
      dirname(__FILE__)."/../Assets/Css/jquery-ui-fontawesome.css",
      dirname(__FILE__)."/../Assets/Css/jquery.window.css",
      dirname(__FILE__)."/../Assets/Css/adios_classes.css",
      dirname(__FILE__)."/../Assets/Css/quill-1.3.6.core.css",
      dirname(__FILE__)."/../Assets/Css/quill-1.3.6.snow.css",
      dirname(__FILE__)."/../Assets/Css/jquery.tag-editor.css",
      dirname(__FILE__)."/../Assets/Css/jquery.tag-editor.css",
      dirname(__FILE__)."/../Assets/Css/jquery-ui.min.css",
      dirname(__FILE__)."/../Assets/Css/multi-select.dist.css",
      dirname(__FILE__)."/../Assets/Css/datatables.css",
      dirname(__FILE__)."/../Components/Css/Modal.css",
    ];

    foreach (scandir(dirname(__FILE__).'/../Assets/Css/Ui') as $file) {
      if ('.css' == substr($file, -4)) {
        $cssFiles[] = dirname(__FILE__)."/../Assets/Css/Ui/{$file}";
      }
    }

    foreach (scandir($this->widgetsDir) as $widget) {
      if (!in_array($widget, [".", ".."]) && is_file($this->widgetsDir."/{$widget}/Main.css")) {
        $cssFiles[] = $this->widgetsDir."/{$widget}/Main.css";
      }

      if (is_dir($this->widgetsDir."/{$widget}/Assets/Css")) {
        foreach (scandir($this->widgetsDir."/{$widget}/Assets/Css") as $widgetCssFile) {
          $cssFiles[] = $this->widgetsDir."/{$widget}/Assets/Css/{$widgetCssFile}";
        }
      }
    }

    foreach ($cssFiles as $file) {
      $css .= @file_get_contents($file)."\n";
    }

    return $css;
  }

  private function scanReactFolder(string $path): string {
    $reactJs = '';

    foreach (scandir($path . '/Assets/Js/React') as $file) {
      if ('.js' == substr($file, -3)) {
        $reactJs = @file_get_contents($path . "/Assets/Js/React/{$file}") . ";";
        break;
      }
    }

    return $reactJs;
  }

  public function renderReactJsBundle(): string {
    $reactFolders = [
      dirname(__FILE__) . '/..',
      $this->config['srcDir']
    ];

    $jsFilesContent = "";

    foreach ($reactFolders as $reactFolder) {
      $jsFilesContent .= $this->scanReactFolder($reactFolder);
    }

    return $jsFilesContent;
  }

  public function renderJSCache() {
    $js = "";

    $jsFiles = [
      dirname(__FILE__)."/../Assets/Js/jquery-3.5.1.js",
      dirname(__FILE__)."/../Assets/Js/jquery.scrollTo.min.js",
      dirname(__FILE__)."/../Assets/Js/jquery.window.js",
      dirname(__FILE__)."/../Assets/Js/jquery.ui.widget.js",
      dirname(__FILE__)."/../Assets/Js/jquery.ui.mouse.js",
      dirname(__FILE__)."/../Assets/Js/jquery-ui-touch-punch.js",
      dirname(__FILE__)."/../Assets/Js/md5.js",
      dirname(__FILE__)."/../Assets/Js/base64.js",
      dirname(__FILE__)."/../Assets/Js/cookie.js",
      dirname(__FILE__)."/../Assets/Js/keyboard_shortcuts.js",
      dirname(__FILE__)."/../Assets/Js/json.js",
      dirname(__FILE__)."/../Assets/Js/moment.min.js",
      dirname(__FILE__)."/../Assets/Js/chart.min.js",
      dirname(__FILE__)."/../Assets/Js/desktop.js",
      dirname(__FILE__)."/../Assets/Js/ajax_functions.js",
      dirname(__FILE__)."/../Assets/Js/adios.js",
      dirname(__FILE__)."/../Assets/Js/quill-1.3.6.min.js",
      dirname(__FILE__)."/../Assets/Js/bootstrap.bundle.js",
      dirname(__FILE__)."/../Assets/Js/jquery.easing.js",
      dirname(__FILE__)."/../Assets/Js/sb-admin-2.js",
      dirname(__FILE__)."/../Assets/Js/jsoneditor.js",
      dirname(__FILE__)."/../Assets/Js/jquery.tag-editor.js",
      dirname(__FILE__)."/../Assets/Js/jquery.caret.min.js",
      dirname(__FILE__)."/../Assets/Js/jquery-ui.min.js",
      dirname(__FILE__)."/../Assets/Js/jquery.multi-select.js",
      dirname(__FILE__)."/../Assets/Js/jquery.quicksearch.js",
      dirname(__FILE__)."/../Assets/Js/datatables.js",
      dirname(__FILE__)."/../Assets/Js/jeditable.js",
      dirname(__FILE__)."/../Assets/Js/draggable.js"
    ];

    foreach (scandir(dirname(__FILE__).'/../Assets/Js/Ui') as $file) {
      if ('.js' == substr($file, -3)) {
        $jsFiles[] = dirname(__FILE__)."/../Assets/Js/Ui/{$file}";
      }
    }

    foreach (scandir($this->widgetsDir) as $widget) {
      if (!in_array($widget, [".", ".."]) && is_file($this->widgetsDir."/{$widget}/main.js")) {
        $jsFiles[] = $this->widgetsDir."/{$widget}/main.js";
      }

      if (is_dir($this->widgetsDir."/{$widget}/Assets/Js")) {
        foreach (scandir($this->widgetsDir."/{$widget}/Assets/Js") as $widgetJsFile) {
          $jsFiles[] = $this->widgetsDir."/{$widget}/Assets/Js/{$widgetJsFile}";
        }
      }
    }

    foreach ($jsFiles as $file) {
      $js .= @file_get_contents($file).";\n";
    }

    $js .= "
      var adios_language_translations = {};
    ";

    foreach ($this->config['availableLanguages'] as $language) {
      $js .= "
        adios_language_translations['{$language}'] = {
          'Confirmation': '".ads($this->translate("Confirmation", [], $this, $language))."',
          'OK, I understand': '".ads($this->translate("OK, I understand", [], $this, $language))."',
          'Cancel': '".ads($this->translate("Cancel", [], $this, $language))."',
          'Warning': '".ads($this->translate("Warning", [], $this, $language))."',
        };
      ";
    }

    return $js;
  }
}
