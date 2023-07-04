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

  if (strpos($class, "ADIOS/") === FALSE) return;

  $loaded = @include(dirname(__FILE__)."/".str_replace("ADIOS/", "", $class).".php");

  if (!$loaded) {

    if (strpos($class, "ADIOS/Actions") === 0) {

      $class = str_replace("ADIOS/Actions/", "", $class);

      // najprv skusim hladat core akciu
      $tmp = dirname(__FILE__)."/Actions/{$class}.php";
      if (!@include($tmp)) {
        // ak sa nepodari, hladam widgetovsku akciu

        $widgetPath = explode("/", $class);
        $widgetName = array_pop($widgetPath);
        $widgetPath = join("/", $widgetPath);

        if (!@include($___ADIOSObject->config['dir']."/Widgets/{$widgetPath}/Actions/{$widgetName}.php")) {
          // ak ani widgetovska, skusim plugin
          $class = str_replace("Plugins/", "", $class);
          $pathLeft = "";
          $pathRight = "";
          foreach (explode("/", $class) as $pathPart) {
            $pathLeft .= ($pathLeft == "" ? "" : "/").$pathPart;
            $pathRight = str_replace("{$pathLeft}/", "", $class);

            $included = FALSE;

            foreach ($___ADIOSObject->pluginFolders as $pluginFolder) {
              $file = "{$pluginFolder}/{$pathLeft}/Actions/{$pathRight}.php";
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

      if (!@include($___ADIOSObject->config['dir']."/Widgets/{$m[1]}/Main.php")) {
        include($___ADIOSObject->config['dir']."/Widgets/{$m[1]}.php");
      }
    } else if (preg_match('/ADIOS\/Plugins\/([\w\/]+)/', $class, $m)) {
      foreach ($___ADIOSObject->pluginFolders as $pluginFolder) {
        if (include("{$pluginFolder}/{$m[1]}/Main.php")) {
          break;
        } else if (include("{$pluginFolder}/{$m[1]}.php")) {
          break;
        }
      }
    } else if (preg_match('/ADIOS\/Tests\/([\w\/]+)/', $class, $m)) {
      $class = str_replace("ADIOS/Tests/", "", $class);

      $testFile = __DIR__."/../../tests/{$class}.php";

      if (is_file($testFile)) {
        require($testFile);
      } else {
        require($___ADIOSObject->config['dir']."/../tests/{$class}.php");
      }

    } else if (preg_match('/ADIOS\/Web\/([\w\/]+)/', $class, $m)) {
      $class = str_replace("ADIOS/Web/", "", $class);

      require($___ADIOSObject->config['dir']."/Web/{$class}.php");

    } else if (preg_match('/ADIOS\/([\w\/]+)/', $class, $m)) {
      include(__DIR__."/../{$m[1]}.php");
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
  public string $requestedURI = "";
  public string $requestedAction = "";
  public string $action = "";
  public string $uid = "";
  public string $srcDir = "";

  public $actionObject;

  public bool $logged = FALSE;

  public array $config = [];
  public array $routing = [];
  public array $widgets = [];

  public array $widgetsInstalled = [];

  public array $pluginFolders = [];
  public array $pluginObjects = [];
  public array $plugins = [];

  public array $modelObjects = [];
  public array $models = [];

  public bool $userLogged = FALSE;
  public $userProfile = NULL;
  public array $userPasswordReset = [];

  public $db = NULL;
  public $twig = NULL;
  public $ui = NULL;
  public $console = NULL;
  public $locale = NULL;
  public $email = NULL;
  public $userNotifications = NULL;
  public $permissions = NULL; // objekt triedy Permissions
  public $test = NULL;
  public $web = NULL;

  public array $assetsUrlMap = [];

  public int $actionNestingLevel = 0;
  public array $actionStack = [];

  public string $dictionaryFilename = "Core-Loader";

  public array $classFactories = [];

  public bool $forceUserLogout = FALSE;

  public string $desktopContentAction = "";
  public array $desktopContentActionParams = [];

  public string $widgetsDir = "";

  public function __construct($config = NULL, $mode = NULL, $forceUserLogout = FALSE) {

    global $___ADIOSObject;
    $___ADIOSObject = $this;

    if ($mode === NULL) {
      $mode = self::ADIOS_MODE_FULL;
    }

    $this->test = new \ADIOS\Core\Test($this);

    if (is_array($config)) {
      $this->config = $config;
    }

    $this->widgetsDir = $config['widgets_dir'] ?? "";

    $this->version = file_get_contents(__DIR__."/../version.txt");

    $this->gtp = $this->config['global_table_prefix'] ?? "";
    $this->requestedAction = $_REQUEST['action'] ?? "";
    $this->forceUserLogout = $forceUserLogout;

    if (empty($this->config['dir'])) $this->config['dir'] = "";
    if (empty($this->config['url'])) $this->config['url'] = "";
    if (empty($this->config['rewrite_base'])) $this->config['rewrite_base'] = "";

    $this->srcDir = realpath(__DIR__."/..");

    if (empty($this->config['session_salt'])) {
      $this->config['session_salt'] = rand(100000, 999999);
    }

    $this->config['request_uri'] = $_SERVER['REQUEST_URI'] ?? "";

    // load available languages
    if (empty($this->config['available_languages'] ?? [])) {
      $this->config['available_languages'] = ["en"];
    }

    // pouziva sa ako vseobecny prefix niektorych session premennych,
    // novy ADIOS ma zatial natvrdo hodnotu, lebo sa sessions riesia cez session name
    if (!defined('_ADIOS_ID')) {
      define(
        '_ADIOS_ID',
        $this->config['session_salt']."-".substr(md5($this->config['session_salt']), 0, 5)
      );
    }

    // ak requestuje nejaky Asset (css, js, image, font), tak ho vyplujem a skoncim
    if ($this->config['rewrite_base'] == "/") {
      $this->requestedURI = ltrim($this->config['request_uri'], "/");
    } else {
      $this->requestedURI = str_replace($this->config['rewrite_base'], "", $this->config['request_uri']);
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
      $consoleFactoryClass = $this->classFactories['console'] ?? \ADIOS\Core\Console::class;
      $this->console = new $consoleFactoryClass($this);
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
          !empty($this->requestedAction)
          && in_array($this->requestedAction, ['Image', 'File'])
        ) {
          $this->finalizeConfig();
          include "{$this->requestedAction}.php";
          die();
        }
      }

      // inicializacia core modelov

      $this->registerModel("Core/Models/Config");
      $this->registerModel("Core/Models/Translate");
      $this->registerModel("Core/Models/User");
      $this->registerModel("Core/Models/UserRole");
      $this->registerModel("Core/Models/Token");

      // inicializacia pluginov - aj pre FULL aj pre LITE mod

      $this->onBeforePluginsLoaded();

      foreach ($this->pluginFolders as $pluginFolder) {
        $this->loadAllPlugins($pluginFolder);
      }

      $this->onAfterPluginsLoaded();

      $this->renderAssets();


      if ($mode == self::ADIOS_MODE_FULL) {

        // start session

        if ($this->config['set_session_time'] ?? TRUE) {
          ini_set('session.gc_maxlifetime', $this->config['session_maxlifetime'] ?? 60 * 60);
          ini_set('session.gc_probability', $this->config['session_probability'] ?? 1);
          ini_set('session.gc_divisor', $this->config['session_divisor'] ?? 1000);
        }

        ini_set('session.use_cookies', $this->config['session_use_cookies'] ?? TRUE);

        session_id();
        session_name(_ADIOS_ID);
        session_start();

        define('_SESSION_ID', session_id());
      }

      // inicializacia locale objektu
      $localeFactoryClass = $this->classFactories['locale'] ?? \ADIOS\Core\Locale::class;
      $this->locale = new $localeFactoryClass($this);

      // inicializacia objektu notifikacii
      $userNotificationsFactoryClass = $this->classFactories['userNotifications'] ?? \ADIOS\Core\UserNotifications::class;
      $this->userNotifications = new $userNotificationsFactoryClass($this);

      // inicializacia mailera
      $emailFactoryClass = $this->classFactories['userNotifications'] ?? \ADIOS\Core\Email::class;
      $this->email = new $emailFactoryClass($this);

      // inicializacia DB - aj pre FULL aj pre LITE mod

      $dbFactoryClass = $this->classFactories['db'] ?? \ADIOS\Core\DB\Providers\MySQLi::class;
      $this->db = new $dbFactoryClass($this, [
        'db_host' => $this->getConfig('db_host', ''),
        'db_port' => $this->getConfig('db_port', ''),
        'db_user' => $this->getConfig('db_user', ''),
        'db_password' => $this->getConfig('db_password', ''),
        'db_name' => $this->getConfig('db_name', ''),
        'db_codepage' => $this->getConfig('db_codepage', 'utf8mb4'),
      ]);

      $this->onBeforeConfigLoaded();

      $this->loadConfigFromDB();

      if ($mode == self::ADIOS_MODE_FULL) {

        // set language
        if (!empty($_SESSION[_ADIOS_ID]['language'])) {
          $this->config['language'] = $_SESSION[_ADIOS_ID]['language'];
        }

        if (is_array($this->config['available_languages'])) {
          if (!in_array($this->config['language'], $this->config['available_languages'])) {
            $this->config['language'] = reset($this->config['available_languages']);
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
      $this->permissions = new \ADIOS\Core\Permissions($this);

      // inicializacia web renderera (byvala CASCADA)
      if (isset($this->config['web']) && is_array($this->config['web'])) {
        $this->web = new \ADIOS\Core\Web\Loader($this, $this->config['web']);
      }

      // timezone
      date_default_timezone_set($this->config['timezone']);

      if ($mode == self::ADIOS_MODE_FULL) {

        if (isset($_POST['passwordReset'])) {
          $email = isset($_POST["email"]) ? $_POST["email"] : "";

          if ($email != "") {
            $userModel = $this->getModel("Core/Models/User");
            $userData = $userModel->getByEmail($email);

            if (!empty($userData)) {
              $passwordResetToken =
                $userModel->generatePasswordResetToken(
                  $userData["id"], $email
                )
              ;

              try {
                $this->email = new \ADIOS\Core\Lib\Email(
                  $config["smtp_host"],
                  $config["smtp_port"]
                );

                $this->email
                  ->setLogin($config["smtp_login"], $config["smtp_password"])
                  ->setFrom($config["smtp_from"])
                ;

                if ($config["smtp_protocol"] == 'ssl') {
                  $this->email->setProtocol(\ADIOS\Core\Lib\Email::SSL);
                }

                if ($config["smtp_protocol"] == 'tls') {
                  $this->email->setProtocol(\ADIOS\Core\Lib\Email::TLS);
                }

                $this->email->addTo($email);
                $this->email->setSubject(
                  $config["application_name"].
                  " - ".$this->translate("password reset", [], $this)
                );
                $this->email->setHtmlMessage("
                  <h4>
                    {$config['application_name']}
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
                var_dump($e->getMessage()); exit();
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

            $userModel = $this->getModel("Core/Models/User");
            $userData = $userModel->validateToken($_GET["token"], true);

            if ($userData) {
              $userModel->updatePassword($userData["id"], $newPassword);

              $this->authUser(
                $userData['login'],
                $newPassword
              );

              header("Location: {$this->config['url']}");
              exit();
            }
          }
        }

        // user authentication
        if ($this->forceUserLogout) unset($_SESSION[_ADIOS_ID]['userProfile']);

        if ((int) $_SESSION[_ADIOS_ID]['userProfile']['id'] > 0) {
          $adiosUserModel = $this->getModel("Core/Models/User");
          // $maxSessionLoginDurationDays = $this->getConfig('auth/max-session-login-duration-days') ?? 1;
          // $maxSessionLoginDurationTime = ((int) $maxSessionLoginDurationDays) * 60 * 60 * 24;


          $user = reset(
            $this->db->select($adiosUserModel)
              ->columns([\ADIOS\Core\DB\Query::allColumnsWithoutLookups])
              ->where([
                ['id', '=', (int) $_SESSION[_ADIOS_ID]['userProfile']['id']]
              ])
              ->fetch()
          );

          if (
            $user['is_active'] != 1
            // || $maxSessionLoginDurationTime + strtotime($user['last_access_time']) < time()
          ) {
            unset($_SESSION[_ADIOS_ID]['userProfile']);
            $this->userProfile = [];
            $this->userLogged = FALSE;
          } else {
            $this->userProfile = $_SESSION[_ADIOS_ID]['userProfile'];
            $this->userLogged = TRUE;
            $clientIp = $this->getClientIpAddress();
            $this->db->query("
              UPDATE `{$adiosUserModel->getFullTableSqlName()}`
              SET
                `last_access_time` = '".date('Y-m-d H:i:s')."',
                `last_access_ip` = '{$clientIp}'
              WHERE `id` = ".(int)$this->userProfile['id'].";
            ");
          }
        } else if ($this->authUser(
          $_POST['login'],
          $_POST['password'],
          ((int) $_POST['keep_logged_in']) == 1
        )) {
          // ked uz som prihlaseny, redirectnem sa, aby nasledny F5 refresh
          // nevyzadoval form resubmission
          header("Location: {$this->config['url']}");
          exit();
        } else {
          $this->userProfile = [];
          $this->userLogged = FALSE;
        }

        // v tomto callbacku mozu widgety zamietnut autorizaciu, ak treba
        $this->onUserAuthorised();

      }

      if ($mode == self::ADIOS_MODE_FULL) {

        // inicializacia widgetov

        $this->onBeforeWidgetsLoaded();

        $this->addAllWidgets($this->config['widgets']);

        $this->onAfterWidgetsLoaded();

        // vytvorim definiciu tables podla nacitanych modelov

        foreach ($this->models as $modelName) {
          $this->getModel($modelName);
        }

        // inicializacia twigu

        $twigLoaderFactoryClass = $this->classFactories['twigLoader'] ?? \ADIOS\Core\Lib\TwigLoader::class;
        $twigLoader = new $twigLoaderFactoryClass($this);
        $this->twig = new \Twig\Environment($twigLoader, array(
          'cache' => FALSE,
          'debug' => TRUE,
        ));
        $this->twig->addExtension(new \Twig\Extension\StringLoaderExtension());
        $this->twig->addExtension(new \Twig\Extension\DebugExtension());
        $this->twig->addFunction(new \Twig\TwigFunction(
          'translate',
          function ($string) {
            return $this->translate($string, [], $this->actionObject);
          }
        ));
        $this->twig->addFunction(new \Twig\TwigFunction('adiosView', function ($uid, $view, $params) {
          if (!is_array($params)) {
            $params = [];
          }
          return $this->view->create(
            $view . (empty($uid) ? '' : '#' . $uid),
            $params
          )->render();
        }));
        $this->twig->addFunction(new \Twig\TwigFunction('adiosAction', function ($action, $params = []) {
          return $this->renderAction($action, $params);
        }));

        // inicializacia UI wrappera
        // $uiFactoryClass = $this->classFactories['ui'] ?? \ADIOS\Core\View::class;
        // $this->ui = new $uiFactoryClass($this);

        // inicializacia UI wrappera
        $viewFactoryClass = $this->classFactories['view'] ?? \ADIOS\Core\View::class;
        $this->view = new $viewFactoryClass($this);
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

  public function isNestedAction() {
    return ($this->actionNestingLevel > 2);
  }

  public function isWindow() {
    return isset($_REQUEST['__IS_WINDOW__']) && $_REQUEST['__IS_WINDOW__'] == "1";
  }

  //////////////////////////////////////////////////////////////////////////////
  // ROUTING

  public function setRouting($routing) {
    if (is_array($routing)) {
      $this->routing = $routing;
    }
  }

  public function addRouting($routing) {
    if (is_array($routing)) {
      $this->routing = array_merge($this->routing, $routing);
    }
  }

  //////////////////////////////////////////////////////////////////////////////
  // WIDGETS

  public function addWidget($widgetName) {
    if (!isset($this->widgets[$widgetName])) {
      try {
        $widgetClassName = "\\ADIOS\\Widgets\\".str_replace("/", "\\", $widgetName);
        if (!class_exists($widgetClassName)) {
          throw new \Exception("Widget {$widgetName} not found.");
        }
        $this->widgets[$widgetName] = new $widgetClassName($this);

        $this->addRouting($this->widgets[$widgetName]->routing());
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

  public function registerModel($modelName) {
    if (!in_array($modelName, $this->models)) {
      $this->models[] = $modelName;
    }
  }

  public function getModelClassName($modelName) {
    return "\\ADIOS\\".str_replace("/", "\\", $modelName);
  }

  /**
   * Returns the object of the model referenced by $modelName.
   * The returned object is cached into modelObjects property.
   *
   * @param  string $modelName Reference of the model. E.g. 'Core/Models/User'.
   * @throws \ADIOS\Core\Exception If $modelName is not available.
   * @return object Instantiated object of the model.
   */
  public function getModel(string $modelName): \ADIOS\Core\Model {
    if (!isset($this->modelObjects[$modelName])) {
      try {
        $modelClassName = $this->getModelClassName($modelName);
        $this->modelObjects[$modelName] = new $modelClassName($this);

        $this->addRouting($this->modelObjects[$modelName]->routing());

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
      $dictionaryFolder = "{$this->config['dir']}/Lang";
    }

    if (strlen($toLanguage) == 2) {
      if (empty($object->dictionaryFilename)) {
        $dictionaryFilename = strtr(get_class($object), "./\\", "---");
        $dictionaryFilename = str_replace("ADIOS-", "", $dictionaryFilename);
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

  public function translate(string $string, array $vars, $object, $toLanguage = ""): string {
    if (empty($toLanguage)) {
      $toLanguage = $this->config['language'] ?? "en";
    }

    if ($toLanguage == "en") {
      return $string;
    }

    $dictionary = [];

    if (empty($object->dictionary[$toLanguage])) {
      $dictionary[$toLanguage] = $this->loadDictionary($object, $toLanguage);
    }

    // // $dictionary[$toLanguage] = $object->dictionary[$toLanguage] ?? [];
    // if (get_class($object) == "ADIOS\\Widgets\\Orders\\Models\\Order") {
    //   var_dump($string);
    //   var_dump(get_class($object));
    //   print_r($dictionary);exit;
    //   }

    if (!isset($dictionary[$toLanguage][$string])) {
      $translated = $string;
    } else {
      $translated = $dictionary[$toLanguage][$string];
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

    if ($this->requestedURI == "adios/cache.css") {
      $cssCache = $this->renderCSSCache();

      header("Content-type: text/css");
      header("ETag: ".md5($cssCache));
      header($headerExpires);
      header("Pragma: cache");
      header($headerCacheControl);

      echo $cssCache;

      exit();
    } else if ($this->requestedURI == "adios/cache.js") {
      $jsCache = $this->renderJSCache();
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
        if (preg_match('/^'.str_replace("/", "\\/", $urlPart).'/', $this->requestedURI, $m)) {

          if ($mapping instanceof \Closure) {
            $sourceFile = $mapping($this, $this->requestedURI);
          } else {
            $sourceFile = $mapping.str_replace($urlPart, "", $this->requestedURI);
          }

          $ext = strtolower(pathinfo($this->requestedURI, PATHINFO_EXTENSION));

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

  public function replaceRouteVariables($routeParams, $variables) {
    if (is_array($routeParams)) {
      foreach ($routeParams as $paramName => $paramValue) {

        if (is_array($paramValue)) {
          $routeParams[$paramName] = $this->replaceRouteVariables($paramValue, $variables);
        } else {
          foreach ($variables as $k2 => $v2) {
            $routeParams[$paramName] = str_replace('$'.$k2, $v2, $routeParams[$paramName]);
          }
        }
      }
    }

    return $routeParams;
  }

  // funkcia render() zabezpecuje:
  //   - routing podla a) (ne)prihlaseny user, b) $this->requestedAction, c) $_REQUEST['__IS_AJAX__']
  //   - kontrolu requestu podla $_REQUEST['c']
  //   - vygenerovanie UID
  //   - renderovanie naroutovanej akcie

  /**
   * Renders the requested content. It can be the (1) whole desktop with complete <html>
   * content; (2) the HTML of an action requested dynamically using AJAX; or (3) a JSON
   * string requested dynamically using AJAX and further processed in Javascript.
   *
   * @param  mixed $params Parameters (a.k.a. arguments) of the requested action.
   * @throws \ADIOS\Core\Exception When no action is specified or requested action is unknown.
   * @throws \ADIOS\Core\Exception When running in CLI and requested action is blocked for the CLI.
   * @throws \ADIOS\Core\Exception When running in SAPI and requested action is blocked for the SAPI.
   * @return string Rendered content.
   */
  public function render($params = []) {
    if (preg_match('/(\w+)\/Cron\/(\w+)/', $this->requestedURI, $m)) {
      $cronClassName = str_replace("/", "\\", "/ADIOS/Widgets/{$m[0]}");

      if (class_exists($cronClassName)) {
        (new $cronClassName($this))->run();
      } else {
        echo "Unknown cron '{$this->requestedURI}'.";
      }

      exit();
    }

    try {

      // cache vytvaram az v tomto momente, t.j. iba pri F5 refresh
      // aby sa pri kazdom AJAX requeste zbytocne nevytvarala
      // $this->rebuildCache();

      if (php_sapi_name() === 'cli') {
        $params = @json_decode($_SERVER['argv'][2] ?? "", TRUE);
        if (!is_array($params)) { // toto nastane v pripade, ked $_SERVER['argv'] nie je JSON string
          $params = $_SERVER['argv'];
        }
        $params['action'] = $_SERVER['argv'][1] ?? "";
      } else {
        $params = $_REQUEST;
      }


      // Kontrola permissions, krok 1
      // Tu sa permissions kontroluju na zaklade REQUEST_URI, cize na zaklade routingu

      $permissionForRequestedURI = "";
      foreach ($this->routing as $routePattern => $route) {
        if (preg_match((string) $routePattern, (string) $params['action'], $m)) {
          $permissionForRequestedURI = $route['permission'];
        }
      }

      if (
        !empty($permissionForRequestedURI)
        && !$this->permissions->has($permissionForRequestedURI)
      ) {
        throw new \ADIOS\Core\Exceptions\NotEnoughPermissionsException("Not enough permissions ({$permissionForRequestedURI}).");
      }

      // TODO: Docasne. Ked bude fungovat, vymazat.
      $params['permissionForRequestedURI'] = $permissionForRequestedURI;

      if (!empty($params['action'])) {
        // Prejdem routovaciu tabulku, ak najdem prislusny zaznam, nastavim action a params.
        // Ak pre $params['action'] neexistuje vhodny routing, nemenim nic - pouzije sa
        // povodne $params['action'], cize requestovana URLka.

        foreach ($this->routing as $routePattern => $route) {
          if (preg_match($routePattern, $params['action'], $m)) {
            // povodnu $params['action'] nahradim novou $route['action']
            $params['action'] = $route['action'];

            $route['params'] = $this->replaceRouteVariables($route['params'], $m);

            foreach ($route['params'] as $k => $v) {
              $params[$k] = $v;
            }
          }
        }

      }

      if (empty($this->action)) {
        if (empty($params['action'])) {
          $this->action = (php_sapi_name() === 'cli' ? "" : $this->config['default_action']);
        } else {
          $this->action = $params['action'];
        }
      }

      $this->dispatchEventToPlugins("onADIOSBeforeActionRender", ["adios" => $this]);

      if (empty($this->action)) {
        throw new \ADIOS\Core\Exceptions\GeneralException("No action specified.");
      }

      $actionClassName = $this->getActionClassName($this->action);

      if (!class_exists($actionClassName)) {
        throw new \ADIOS\Core\Exceptions\GeneralException("Unknown action '{$this->action}'.");
      }

      if (php_sapi_name() === 'cli') {
        if (!$actionClassName::$cliSAPIEnabled) {
          throw new \ADIOS\Core\Exceptions\GeneralException("Action is not available for CLI interface.");
        }
      } else {
        if (!$actionClassName::$webSAPIEnabled) {
          throw new \ADIOS\Core\Exceptions\GeneralException("Action is not available for WEB interface.");
        }
      }

      // mam moznost upravit config (napr. na skrytie desktopu alebo upravu permissions)
      $this->config = $actionClassName::overrideConfig($this->config, $params);

      if ($params['__IS_AJAX__']) {
        // tak nic
      } else if (
        !$this->getConfig("hide_default_desktop", FALSE)
        && !$actionClassName::$hideDefaultDesktop
        && !method_exists($actionClassName, "renderJSON")
      ) {
        // treba nacitat cely desktop, ak to nie je zakazane v config alebo v akcii
        $this->desktopContentAction = $this->action;
        $this->desktopContentActionParams = $params;
        $this->action = "Desktop";
      }

      if (
        !$this->userLogged
        && $actionClassName::$requiresUserAuthentication
      ) {
        $this->action = "Login";
      }

      if (empty($this->action)) {
        $this->action = "Desktop";
      }

      // vygenerovanie UID tohto behu
      if (empty($this->uid)) {
        $uid = $this->getUid($params['id']);
      } else {
        $uid = $this->uid.'__'.$this->getUid($params['id']);
      }

      $this->setUid($uid);

      return $this->renderAction($this->action, $params);
    } catch (\ADIOS\Core\Exceptions\NotEnoughPermissionsException $e) {
      if ($this->isAjax()) {
        echo $this->renderFatal($e->getMessage());
        exit();
      } else {
        header('Location: ' . $this->config['url']);
        exit;
      }
    } catch (\ADIOS\Core\Exceptions\GeneralException $e) {
      $lines = [];
      $lines[] = "ADIOS RUN failed: [".get_class($e)."] ".$e->getMessage();
      if ($this->config['debug']) {
        $lines[] = "Requested URI = {$this->requestedURI}";
        $lines[] = "Rewrite base = {$this->config['rewrite_base']}";
        $lines[] = "SERVER.REQUEST_URI = {$this->config['request_uri']}";
      }

      echo join(" ", $lines);

      exit();
    }
  }

  public function getActionClassName(string $action) : string {

    // If the action contains the dash (-), it must be converted to camelCase first
    $action = str_replace(' ', '', ucwords(str_replace('-', ' ', $action)));

    $actionClassName = '';

    // Dusan 31.5.2023: Tento sposob zapisu akcii je zjednoteny so sposobom zapisu modelov.
    foreach ($this->widgets as $widgetName => $widgetData) {
      if (strpos(strtolower($action), strtolower($widgetName)) === 0) {
        $actionClassName = 
          '\\ADIOS\\Widgets\\'
          . $widgetName
          . '\\Actions\\'
          . substr($action, strlen($widgetName) + 1)
        ;
      }
    }
    $actionClassName = str_replace('/', '\\', $actionClassName);

    if (!class_exists($actionClassName)) {
      // Dusan 31.5.2023: Tento sposob zapisu akcii je deprecated.
      $actionClassName = 'ADIOS\\Actions\\' . str_replace('/', '\\', $action);

      $this->console->warning('Deprecated class name for action ' . $action . '.');
    }

    return $actionClassName;
  }

  public function actionExists(string $action) : bool {
    return class_exists($this->getActionClassName($action));
  }

  // funkcia renderAction() zabezpecuje:
  //   - kontrolu pravomoci, ci moze logged user akciu spustit
  //   - vyrenderovanie akcie alebo, ak neexistuje, vyrenderovanie twig template

  public function renderAction($action, $params) {
    if (!is_array($params)) $params = [];

    $params['_REQUEST'] = $params;
    $params['_COOKIE'] = $_COOKIE;
    $this->action = $action;

    if (in_array($action, array_keys($this->config['widgets']))) {
      $action = "{$action}/Main";
    }

    $this->actionNestingLevel++;
    $this->actionStack[] = $action;

    $actionClassName = $this->getActionClassName($action);

    try {
      // Kontrola permissions, krok 2
      // Tu sa permissions kontroluju na zaklade povoleni pre konkretnu akciu
      // (renderovana akcia nemusi byt to iste, ako REQUESTED_URI, pretoze routing
      // to moze zmenit)
      if ($actionClassName::$requiresUserAuthentication) {
        $this->checkPermissionsForAction($action, $params);
      }

      // permissions udelene
      if ($this->actionExists($action)) {
        $this->actionObject = new $actionClassName($this, $params);

        if (method_exists($actionClassName, "renderJSON")) {
          $actionReturn = $this->actionObject->renderJSON($params);
          $actionHtml = json_encode($actionReturn);
        } else {
          $actionReturn = $this->actionObject->render($params);

          if ($actionReturn === NULL) {
            // akcia nic nereturnovala, iba robila echo
            $actionHtml = "";
          } else if (is_string($actionReturn)) {
            $actionHtml = $actionReturn;
          } else {
            $actionHtml = $this->renderReturn($actionReturn);
          }
        }

      } else {

        // ak sa nepodari najst classu, tak skusim aspon vyrenderovat template
        $tmpTemplateName = $actionClassName;
        $tmpTemplateName = str_replace("\\", "/", $tmpTemplateName);
        $tmpTemplateName = str_replace("/Actions/", "/Templates/", $tmpTemplateName);

        $actionFactoryClass = $this->classFactories['action'] ?? \ADIOS\Core\Action::class;
        $tmp = new $actionFactoryClass($this);
        $tmp->twigTemplate = $tmpTemplateName;
        $actionHtml = $tmp->render($params);
      }
    } catch (\ADIOS\Core\Exceptions\NotEnoughPermissionsException $e) {
      $actionHtml = $this->renderFatal($e->getMessage());
    } catch (
      \Exception
      // | \Throwable
      $e) {
      $error = error_get_last();

      if ($error['type'] == E_ERROR) {
        $actionHtml = $this->renderFatal(
          '<div style="margin-bottom:1em;">'
            . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']
          . '</div>'
          . '<pre style="font-size:0.75em;font-family:Courier New">'
            . $e->getTraceAsString()
          . '</pre>',
          TRUE
        );
      } else {
        $actionHtml = $this->renderFatal(
          '<div style="margin-bottom:1em;">'
            . get_class($e) . ': ' . $e->getMessage()
          . '</div>'
          . '<pre style="font-size:0.75em;font-family:Courier New">'
            . $e->getTraceAsString()
          . '</pre>',
          TRUE
        );
      }
    }

    return $actionHtml;
  }

  /**
   * Checks user permissions before rendering requested action.
   * Original implementation does nothing. Must be overriden
   * the application's main class.
   *
   * Does not return anything, only throws exceptions.
   *
   * @abstract
   * @param string $action Name of the action to be rendered.
   * @param array $params Parameters (a.k.a. arguments) of the action.
   * @throws \ADIOS\Core\NotEnoughPermissionsException When the signed user does not have enough permissions.
   * @return void
   */
  public function checkPermissionsForAction($action, $params = NULL) {
    // to be overriden
  }

  public function renderReturn($return) {
    // if ($this->isAjax() && !$this->isWindow()) {
      return json_encode([
        "result" => "SUCCESS",
        "content" => $return,
      ]);
    // } else {
    //   return $return;
    // }
  }

  public function renderWarning($message, $isHtml = TRUE) {
    if ($this->isAjax() && !$this->isWindow()) {
      return json_encode([
        "result" => "WARNING",
        "content" => $message,
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
        "result" => "FATAL",
        "content" => $message,
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


  public function renderExceptionWarningHtml($exception) {

    $traceLog = "";
    foreach ($exception->getTrace() as $item) {
      $traceLog .= "{$item['file']}:{$item['line']}\n";
    }

    switch (get_class($exception)) {
      case 'ADIOS\Core\Exceptions\DBException':
        $errorMessage = $exception->getMessage();
        $errorHash = md5(date("YmdHis").$errorMessage);
        // $this->console->error("{$errorHash}\t{$errorMessage}\t{$this->db->last_query}\t{$this->db->db_error}");
        $html = "
          <div style='text-align:center;font-size:5em;color:red'>
            🥴
          </div>
          <div style='margin-top:1em;margin-bottom:1em;'>
            Oops! Something went wrong with the database.
            See logs for more information or contact the support.<br/>
          </div>
          <div style='color:red;margin-bottom:1em;white-space:pre;font-family:courier;font-size:0.8em;overflow:auto;'>{$errorMessage}</div>
          <div style='color:gray;font-size:0.8em;'>
            {$errorHash}<br/>
            ".get_class($exception)."<br/>
            <a href='javascript:void(0);' onclick='$(this).closest(\"div\").find(\".trace-log\").show()'>Show/Hide trace log</a><br/>
            <div class='trace-log' style='display:none'>{$traceLog}</div>
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
          <div style='text-align:center;font-size:5em;color:red'>
            <i class='fas fa-copy'></i>
          </div>
          <div style='margin-top:1em;margin-bottom:3em;text-align:center;color:red;'>
            ".$this->translate($errorMessage, [], $this)."<br/>
            <br/>
            <b>".join(", ", $invalidColumns)."</b>
          </div>
          <a href='javascript:void(0);' onclick='$(this).next(\"div\").slideDown();'>
          ".$this->translate("Show more information", [], $this)."
          </a>
          <div style='display:none'>
            <div style='color:red;margin-bottom:1em;font-family:courier;font-size:8pt;max-height:10em;overflow:auto;'>
              {$dbError}<br/>
              {$dbQuery}<br/>
              {$initiatingModelName}
            </div>
            <div style='color:gray;font-size:0.8em;'>
              Error # {$errorNo}<br/>
              ".get_class($exception)."<br/>
              <a href='javascript:void(0);' onclick='$(this).closest(\"div\").find(\".trace-log\").show()'>Show/Hide trace log</a><br/>
              <div class='trace-log' style='display:none'>{$traceLog}</div>
            </div>
          </div>
        ";
        break;
      default:
        $html = "
          <div style='text-align:center;font-size:5em;color:red'>
            🥴
          </div>
          <div style='margin-top:1em;margin-bottom:1em;'>
            Oops! Something went wrong.
            See logs for more information or contact the support.<br/>
          </div>
          <div style='color:red;margin-bottom:1em;white-space:pre;font-family:courier;font-size:0.8em;overflow:auto;'>".$exception->getMessage()."</div>
          <div style='color:gray'>
            ".get_class($exception)."
          </div>
        ";
        break;
    }

    return $this->renderHtmlWarning($html);
  }

  public function renderHtmlWarning($warning) {
    return $this->renderWarning($warning, TRUE);
  }

  /**
   * Propagates an event to all plugins of the application. Each plugin can
   * implement hook for the event. The hook must return either modified event
   * data of FALSE. Returning FALSE in the hook terminates the event propagation.
   *
   * @param  string $event Name of the event to propagate.
   * @param  array $eventData Data of the event. Each event has its own specific structure of the data.
   * @throws \ADIOS\Core\Exception When plugin's hook returns invalid value.
   * @return array<string, mixed> Event data modified by plugins which implement the hook.
   */
  public function dispatchEventToPlugins($event, $eventData = []) {
    foreach ($this->pluginObjects as $plugin) {
      if (method_exists($plugin, $event)) {
        $eventData = $plugin->$event($eventData);
        if (!is_array($eventData) && $eventData !== FALSE) {
          throw new \ADIOS\Core\Exceptions\GeneralException("Plugin {$plugin->name}, event {$event}: No value returned. Either forward \$event or return FALSE.");
        }

        if ($eventData === FALSE) {
          break;
        }
      }
    }
    return $eventData;
  }

  public function hasPermissionForAction($action, $params) {
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
    if (is_array($config)) {
      foreach ($config as $key => $value) {
        $tmpPath = $path.$key;

        if (is_array($value)) {
          $this->saveConfig($value, $tmpPath.'/');
        } else if ($value === NULL) {
          $this->db->query("
            delete from `".(empty($this->gtp) ? '' : $this->gtp . '_')."config`
            where `path` like '".$this->db->escape($tmpPath)."%'
          ");
        } else {
          $this->db->query("
            insert into `".(empty($this->gtp) ? '' : $this->gtp . '_')."config` set
              `path` = '".$this->db->escape($tmpPath)."',
              `value` = '".$this->db->escape($value)."'
            on duplicate key update
              `path` = '".$this->db->escape($tmpPath)."',
              `value` = '".$this->db->escape($value)."'
          ");
        }
      }
    }
  }

  public function saveConfigByPath(string $path, $value) {
    if (!empty($path)) {
      $this->db->query("
        insert into `".(empty($this->gtp) ? '' : $this->gtp . '_')."config` set
          `path` = '".$this->db->escape($path)."',
          `value` = '".$this->db->escape($value)."'
        on duplicate key update
          `path` = '".$this->db->escape($path)."',
          `value` = '".$this->db->escape($value)."'
      ");
    }
  }

  public function deleteConfig($path) {
    if (!empty($path)) {
      $this->db->query("
        delete from `".(empty($this->gtp) ? '' : $this->gtp . '_')."config`
        where `path` like '".$this->db->escape($path)."%'
      ");
    }
  }

  public function loadConfigFromDB() {
    try {
      $queryOk = $this->db->query("
        select
          *
        from `".(empty($this->gtp) ? '' : $this->gtp . '_')."config`
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

    $this->config['files_dir'] = $this->config['files_dir'] ?? "{$this->config['dir']}/upload";
    $this->config['files_url'] = $this->config['files_url'] ?? "{$this->config['url']}/upload";

    $this->config['upload_dir'] = $this->config['files_dir'];
    $this->config['upload_url'] = $this->config['files_url'];

    $this->config['files_dir'] = str_replace("\\", "/", $this->config['files_dir']);
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

  public function onModelsLoaded() {
    // to be overriden
  }

  ////////////////////////////////////////////////



  public function getUid($uid = '') {
    if (empty($uid)) {
      $tmp = $this->action.'-'.time().rand(100000, 999999);
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

  public function getClientIpAddress() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
  }

  public function authCookieSerialize($login, $password) {
    return md5($login.".".$password).",".$login;
  }

  public function authCookieGetLogin() {
    list($tmpHash, $tmpLogin) = explode(",", $_COOKIE[_ADIOS_ID.'-user']);
    return $tmpLogin;
  }

  public function authUser($login, $password, $rememberLogin = FALSE) {
    $this->userProfile = null;
    $login = trim((string) $login);

    if (empty($login) && !empty($_COOKIE[_ADIOS_ID.'-user'])) {
      $login = $this->authCookieGetLogin();
    }

    if (!empty($login)) {
      $adiosUserModel = $this->getModel("Core/Models/User");
      $this->db->query("
        select
          *
        from `{$adiosUserModel->getFullTableSqlName()}`
        where
          (
            `login`= '".$this->db->escape($login)."'
            or `email`= '".$this->db->escape($login)."'
          )
          and `is_active` <> 0
      ");

      while ($data = $this->db->fetchArray()) {
        $passwordMatch = FALSE;

        if (!empty($password) && password_verify($password, $data['password'])) {
          // plain text
          $passwordMatch = TRUE;
        } else if ($_COOKIE[_ADIOS_ID.'-user'] == $this->authCookieSerialize($data['login'], $data['password'])) {
          $passwordMatch = TRUE;
        }

        if ($passwordMatch) {
          $this->userProfile = $data;
          $this->userLogged = TRUE;

          // update last_login_time a last_login_ip
          $clientIp = $this->getClientIpAddress();
          $this->db->query("
            UPDATE ".(empty($this->gtp) ? '' : $this->gtp . '_')."users
            SET
              last_login_time = '".date('Y-m-d H:i:s')."',
              last_login_ip = '{$clientIp}',
              last_access_time = '".date('Y-m-d H:i:s')."',
              last_access_ip = '{$clientIp}'
            WHERE id = ".(int)$this->userProfile['id'].";
          ");

          $_SESSION[_ADIOS_ID]['userProfile'] = $this->userProfile;

          if ($rememberLogin) {
            setcookie(
              _ADIOS_ID.'-user',
              $this->authCookieSerialize($data['login'], $data['password']),
              time() + (3600 * 24 * 30)
            );
          }

          return TRUE;
        }
      }
    }

    return FALSE;
  }

  // 20.1.2023: Deprecated
  // public function generate_rc_perms($perms) { }

  // 20.1.2023: Deprecated
  // public function has_perms($perm) {
  //   return TRUE;
  // }

  // 20.1.2023: Deprecated
  // public function action_perms($action) {
  //   return TRUE;
  // }

  // 20.1.2023: Deprecated
  // public function db_perms($action) {
  //   return TRUE;
  // }

  // 20.1.2023: Deprecated
  // public function feature_perms($action) {
  //   return TRUE;
  // }

  // 20.1.2023: Deprecated
  // public function table_has_cols_perms($table_name, $operation) {
  //   return TRUE;
  // }



  public function renderCSSCache() {
    $css = "";

    $cssFiles = [
      dirname(__FILE__)."/../Assets/Css/fontawesome-5.13.0.css",
      dirname(__FILE__)."/../Assets/Css/bootstrap.min.css",
      dirname(__FILE__)."/../Assets/Css/sb-admin-2.css",
      dirname(__FILE__)."/../Assets/Css/responsive.css",
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
      dirname(__FILE__)."/../Assets/Js/jeditable.js"
    ];

    foreach (scandir(dirname(__FILE__).'/../Assets/Js/Ui') as $file) {
      if ('.js' == substr($file, -3)) {
        $jsFiles[] = dirname(__FILE__)."/../Assets/Js/Ui/{$file}";
      }
    }

    foreach (scandir($this->widgetsDir) as $widget) {
      if (!in_array($widget, [".", ".."]) && is_file($this->widgetsDir."/{$widget}/Main.js")) {
        $jsFiles[] = $this->widgetsDir."/{$widget}/Main.js";
      }

      if (is_dir($this->widgetsDir."/{$widget}/Assets/Js")) {
        foreach (scandir($this->widgetsDir."/{$widget}/Assets/Js") as $widgetJsFile) {
          $jsFiles[] = $this->widgetsDir."/{$widget}/Assets/Js/{$widgetJsFile}";
        }
      }
    }

    foreach ($jsFiles as $file) {
      $js .= @file_get_contents($file)."\n";
    }

    $js .= "
      var adios_language_translations = {};
    ";

    foreach ($this->config['available_languages'] as $language) {
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
