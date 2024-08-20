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
  $class = trim(str_replace("\\", "/", $class), "/");

  if (preg_match('/ADIOS\/([\w\/]+)/', $class, $m)) {
    require_once(__DIR__ . "/../{$m[1]}.php");
  }
});

register_shutdown_function(function() {
  $error = error_get_last();
  if ($error !== NULL && $error['type'] == E_ERROR) {
    header('HTTP/1.1 400 Bad Request', true, 400);
  }
});

// ADIOS Loader class
#[\AllowDynamicProperties]
class Loader
{
  const ADIOS_MODE_FULL = 1;
  const ADIOS_MODE_LITE = 2;

  public string $gtp = "";
  public string $requestedUri = "";
  // public string $requestedController = "";
  public string $controller = "";
  public string $permission = "";
  public string $uid = "";
  public string $srcDir = "";
  public array $route = [];

  public ?\ADIOS\Core\Controller $controllerObject;

  public bool $logged = FALSE;

  public array $config = [];
  public array $widgets = [];

  public array $widgetsInstalled = [];

  public array $pluginFolders = [];
  public array $pluginObjects = [];
  public array $plugins = [];

  public array $modelObjects = [];
  public array $registeredModels = [];

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
  public ?\Illuminate\Database\Capsule\Manager $eloquent = NULL;

  public ?\Twig\Environment $twig = NULL;

  public ?\ADIOS\Core\PDO $pdo = NULL;

  public array $assetsUrlMap = [];

  public string $dictionaryFilename = "Core-Loader";
  public array $dictionary = [];

  public string $desktopContentController = "";

  public string $widgetsDir = "";

  public function __construct($config = NULL, $mode = NULL) {

    global $___ADIOSObject;
    $___ADIOSObject = $this;

    if ($mode === NULL) {
      $mode = self::ADIOS_MODE_FULL;
    }

    if (is_array($config)) {
      $this->config = $config;
    }

    \ADIOS\Core\Helper::addSpeedLogTag("#1");

    // $this->test = new ($this->getCoreClass('Core\\Test'))($this);

    $this->widgetsDir = $config['widgetsDir'] ?? "";

    $this->gtp = $this->config['global_table_prefix'] ?? "";
    // $this->requestedController = $_REQUEST['controller'] ?? "";
    $this->params = $this->extractParamsFromRequest();

    if (empty($this->config['dir'])) $this->config['dir'] = "";
    if (empty($this->config['url'])) $this->config['url'] = "";
    if (empty($this->config['rewriteBase'])) $this->config['rewriteBase'] = "";
    if (empty($this->config['accountDir'])) $this->config['accountDir'] = $this->config['dir'];
    if (empty($this->config['accountUrl'])) $this->config['accountUrl'] = $this->config['url'];

    $this->srcDir = realpath(__DIR__."/..");

    if (empty($this->config['sessionSalt'])) {
      $this->config['sessionSalt'] = rand(100000, 999999);
    }

    $this->config['requestUri'] = $_SERVER['REQUEST_URI'] ?? "";

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
    $this->assetsUrlMap["adios/assets/widgets/"] = function ($app, $url) {
      $url = str_replace("adios/assets/widgets/", "", $url);
      preg_match('/(.*?)\/(.+)/', $url, $m);

      $widget = $m[1];
      $asset = $m[2];

      return $app->widgetsDir."/{$widget}/Assets/{$asset}";
    };
    $this->assetsUrlMap["adios/assets/plugins/"] = function ($app, $url) {
      $url = str_replace("adios/assets/plugins/", "", $url);
      preg_match('/(.+?)\/~\/(.+)/', $url, $m);

      $plugin = $m[1];
      $asset = $m[2];

      foreach ($app->pluginFolders as $pluginFolder) {
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
      $this->console = new ($this->getCoreClass('Core\\Console'))($this);
      $this->console->clearLog("timestamps", "info");

      // global $gtp; - pouziva sa v basic_functions.php

      $gtp = $this->gtp;

      // nacitanie zakladnych ADIOS lib suborov
      require_once dirname(__FILE__)."/Lib/basic_functions.php";

      \ADIOS\Core\Helper::addSpeedLogTag("#2");

      if ($mode == self::ADIOS_MODE_FULL) {

        $this->initDatabaseConnections();

      }

      \ADIOS\Core\Helper::addSpeedLogTag("#2.1");

      // inicializacia core modelov
      $this->registerModel($this->getCoreClass('Models\\Config'));
      // $this->registerModel($this->getCoreClass('Models\\Translate'));
      $this->registerModel($this->getCoreClass('Models\\User'));
      $this->registerModel($this->getCoreClass('Models\\UserRole'));
      $this->registerModel($this->getCoreClass('Models\\UserHasRole'));
      $this->registerModel($this->getCoreClass('Models\\Token'));

      // inicializacia pluginov - aj pre FULL aj pre LITE mod

      $this->onBeforePluginsLoaded();

      foreach ($this->pluginFolders as $pluginFolder) {
        $this->loadAllPlugins($pluginFolder);
      }

      $this->onAfterPluginsLoaded();

      $this->renderAssets();


      if ($mode == self::ADIOS_MODE_FULL) {
        // start session

        session_id();
        session_name(_ADIOS_ID);
        session_start();

        define('_SESSION_ID', session_id());
      }

      \ADIOS\Core\Helper::addSpeedLogTag("#2.2");

      // inicializacia routera
      $this->router = new ($this->getCoreClass('Core\\Router'))($this);

      // inicializacia locale objektu
      $this->locale = new ($this->getCoreClass('Core\\Locale'))($this);

      // inicializacia objektu notifikacii
      $this->userNotifications = new ($this->getCoreClass('Core\\UserNotifications'))($this);

      // inicializacia DB - aj pre FULL aj pre LITE mod

      $this->onBeforeConfigLoaded();

      // if ($mode == self::ADIOS_MODE_FULL) {
      //   $this->loadConfigFromDB();
      // }

      \ADIOS\Core\Helper::addSpeedLogTag("#3");

      // finalizacia konfiguracie - aj pre FULL aj pre LITE mode
      $this->finalizeConfig();
      \ADIOS\Core\Helper::addSpeedLogTag("#3.1");

      $this->onAfterConfigLoaded();
      \ADIOS\Core\Helper::addSpeedLogTag("#3.2");

      // object pre kontrolu permissions
      $this->permissions = new ($this->getCoreClass('Core\\Permissions'))($this);

      // inicializacia web renderera (byvala CASCADA)
      if (isset($this->config['web']) && is_array($this->config['web'])) {
        $this->web = new ($this->getCoreClass('Core\\Web\\Loader'))($this, $this->config['web']);
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
                  <a href='{$config['accountUrl']}/PasswordReset?token={$passwordResetToken}'>
                    {$config['accountUrl']}/PasswordReset?token={$passwordResetToken}
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

              header('Location: ' . $this->config['accountUrl']);
              exit();
            }
          }
        }

        // user authentication

        if ((int) ($_SESSION[_ADIOS_ID]['userProfile']['id'] ?? 0) > 0) {
          $user = $userModel->loadUser((int) $_SESSION[_ADIOS_ID]['userProfile']['id']);

          if (!$userModel->isUserActive($user)) {
            $userModel->signOut();
          } else {
            $user = $userModel->loadUserFromSession();

            if (is_array($user)) {
              $userModel->updateAccessInformation((int) $user['id']);
              $this->userProfile = $user;
              $this->userLogged = TRUE;
            } else {
              $this->userProfile = NULL;
              $this->userLogged = FALSE;
            }
          }
        } else if (!empty($_POST['login']) && !empty($_POST['password'])) {
          $userModel->authUser(
            $_POST['login'],
            $_POST['password'],
            ((int) $_POST['keep_logged_in']) == 1
          );

          $userModel->updateLoginAndAccessInformation((int) ($this->userProfile['id'] ?? 0));
        }

        // v tomto callbacku mozu widgety zamietnut autorizaciu, ak treba
        $this->onUserAuthorised();


        \ADIOS\Core\Helper::addSpeedLogTag("#4");


        // inicializacia widgetov

        $this->onBeforeWidgetsLoaded();

        $this->addAllWidgets($this->config['widgets']);

        $this->onAfterWidgetsLoaded();

        \ADIOS\Core\Helper::addSpeedLogTag("#5");

        // vytvorim definiciu tables podla nacitanych modelov

        foreach ($this->registeredModels as $modelName) {
          $this->getModel($modelName);
        }

        // pridam routing pre ADIOS default controllers
        $appControllers = \ADIOS\Core\Helper::scanDirRecursively(__DIR__ . '/../Controllers');
        $tmpRouting = [];
        foreach ($appControllers as $tmpController) {
          $tmpController = str_replace(".php", "", $tmpController);
          $tmpRouting["/^".str_replace("/", "\\/", $tmpController)."$/"] = [
            "controller" => 'ADIOS\\Controllers\\' . $tmpController,
          ];
        }
        $this->router->addRouting($tmpRouting);

        $this->router->addRouting([
          '/^api\/form\/describe\/?$/' => [
            'controller' => 'ADIOS/Controllers/Api/Form/Describe',
          ],
          '/^api\/record\/get\/?$/' => [
            'controller' => 'ADIOS/Controllers/Api/Record/Get',
          ],
          '/^api\/record\/lookup\/?$/' => [
            'controller' => 'ADIOS/Controllers/Api/Record/Lookup',
          ],
          '/^api\/record\/save\/?$/' => [
            'controller' => 'ADIOS/Controllers/Api/Record/Save',
          ],
        ]);

        // inicializacia twigu
        $twigLoader = new ($this->getCoreClass('Core\\TwigLoader'))($this);
        $this->twig = new \Twig\Environment($twigLoader, array(
          'cache' => FALSE,
          'debug' => TRUE,
        ));
        $this->twig->addGlobal('config', $this->config);
        $this->twig->addExtension(new \Twig\Extension\StringLoaderExtension());
        $this->twig->addExtension(new \Twig\Extension\DebugExtension());

        $this->twig->addFunction(new \Twig\TwigFunction(
          'adiosModel',
          function (string $model) {
            return $this->getModel($model);
          }
        ));

        $this->twig->addFunction(new \Twig\TwigFunction(
          '_dump',
          function ($var) {
            ob_start();
            _var_dump($var);
            return ob_get_clean();
          }
        ));

        $this->twig->addFunction(new \Twig\TwigFunction(
          'adiosHtmlAttributes',
          function (?array $attributes) {
            if (!is_array($attributes)) {
              return '';
            } else {
              $attrsStr = join(
                ' ',
                array_map(
                  function($key) use ($attributes) {
                    if (is_bool($attributes[$key])){
                      return $attributes[$key] ? $key : '';
                    } else if (is_array($attributes[$key])) {
                      return \ADIOS\Core\Helper::camelToKebab($key)."='".json_encode($attributes[$key])."'";
                    } else {
                      return \ADIOS\Core\Helper::camelToKebab($key)."='{$attributes[$key]}'";
                    }
                  },
                  array_keys($attributes)
                )
              );

              return $attrsStr;
            }
          }
        ));

        $this->twig->addFunction(new \Twig\TwigFunction(
          'str2url',
          function ($string) {
            return \ADIOS\Core\Helper::str2url($string ?? '');
          }
        ));
        $this->twig->addFunction(new \Twig\TwigFunction(
          'hasPermission',
          function (string $permission, array $idUserRoles = []) {
            return $this->permissions->granted($permission, $idUserRoles);
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
        $this->view = new ($this->getCoreClass('Core\\ViewWithController'))($this);
      }

      $this->dispatchEventToPlugins("onADIOSAfterInit", ["app" => $this]);
    } catch (\Exception $e) {
      exit("ADIOS INIT failed: [".get_class($e)."] ".$e->getMessage());
    }

    \ADIOS\Core\Helper::addSpeedLogTag("#6");
    // \ADIOS\Core\Helper::printSpeedLogTags();

    return $this;
  }

  public function isAjax() {
    return isset($_REQUEST['__IS_AJAX__']) && $_REQUEST['__IS_AJAX__'] == "1";
  }

  public function isWindow() {
    return isset($_REQUEST['__IS_WINDOW__']) && $_REQUEST['__IS_WINDOW__'] == "1";
  }

  public function getCoreClass($class): string {
    return $this->config['coreClasses'][$class] ?? ('\\ADIOS\\' . $class);
  }

  public function getDefaultConnectionConfig(): ?array {
    if (isset($this->config['db']['defaultConnection']) && is_array($this->config['db']['defaultConnection'])) {
      return $this->config['db']['defaultConnection'];
    } else {
      return [
        "driver"    => "mysql",
        "host"      => $this->config['db_host'] ?? '',
        "port"      => $this->config['db_port'] ?? 3306,
        "database"  => $this->config['db_name'] ?? '',
        "username"  => $this->config['db_user'] ?? '',
        "password"  => $this->config['db_password'] ?? '',
        "charset"   => 'utf8mb4',
        "collation" => 'utf8mb4_unicode_ci',
      ];
    }
  }

  public function initDatabaseConnections() {
    $this->eloquent = new \Illuminate\Database\Capsule\Manager;

    $dbConnectionConfig = $this->getDefaultConnectionConfig();

    if ($dbConnectionConfig !== null) {
      $this->eloquent->setAsGlobal();
      $this->eloquent->bootEloquent();
      $this->eloquent->addConnection($dbConnectionConfig, 'default');
    }

    $dbProvider = $this->getConfig('db/provider', '');
    $dbProviderClass = $this->getCoreClass('Core\\DB' . (empty($dbProvider) ? '' : '\\Providers\\') . $dbProvider);
    $this->db = new $dbProviderClass($this);

    $this->pdo = new \ADIOS\Core\PDO($this);
    $this->pdo->connect();
  }

  //////////////////////////////////////////////////////////////////////////////
  // WIDGETS

  public function addWidget($widgetName) {
    if (!isset($this->widgets[$widgetName])) {
      try {
        $widgetClassName = "\\" . $this->config['appNamespace'] . "\\Widgets\\".str_replace("/", "\\", $widgetName);
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
    if (!in_array($modelName, $this->registeredModels)) {
      $this->registeredModels[] = $modelName;
    }
  }

  public function getModelNames(): array
  {
    return $this->registeredModels;
  }

  public function getModelClassName($modelName): string
  {
    return str_replace("/", "\\", $modelName);
  }

  /**
   * Returns the object of the model referenced by $modelName.
   * The returned object is cached into modelObjects property.
   *
   * @param  string $modelName Reference of the model. E.g. 'ADIOS/Models/User'.
   * @throws \ADIOS\Core\Exception If $modelName is not available.
   * @return object Instantiated object of the model.
   */
  public function getModel(string $modelName): \ADIOS\Core\Model {
    if (!isset($this->modelObjects[$modelName])) {
      try {
        $modelClassName = $this->getModelClassName($modelName);
        $this->modelObjects[$modelName] = new $modelClassName($this);

        // $this->router->addRouting($this->modelObjects[$modelName]->routing());

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

  public function getDictionaryFilename(string $language = ''): string
  {
    $dictionaryFile = '';

    if (empty($language)) $language = $this->config['language'] ?? 'en';
    if (empty($language)) $language = 'en';

    if (strlen($language) == 2) {
      $dictionaryFile = "{$this->config['srcDir']}/Lang/{$language}.json";
    }

    return $dictionaryFile;
  }

  public function loadDictionary(string $language = ""): array
  {
    $dictionary = [];
    $dictionaryFile = $this->getDictionaryFilename($language);

    if (!empty($dictionaryFile) && file_exists($dictionaryFile)) {
      $dictionary = @json_decode(file_get_contents($dictionaryFile), true);
    }

    return $dictionary;
  }

  public function translate(string $string, array $vars = [], $contextObject = NULL, $toLanguage = ""): string
  {
    if ($contextObject === NULL) $contextObject = $this;
    if (empty($toLanguage)) {
      $toLanguage = $this->config['language'] ?? "en";
    }

    if ($toLanguage == "en") {
      $translated = $string;
    } else {
      if (empty($this->dictionary[$toLanguage])) {
        $this->dictionary[$toLanguage] = $this->loadDictionary($toLanguage);
      }

      $dictionary = $this->dictionary[$toLanguage] ?? [];
      $context = str_replace('\\', ':', get_class($contextObject));

      if (empty($dictionary[$context][$string])) {
        $translated = $string;
        $dictionaryFile = $this->getDictionaryFilename($toLanguage);
        $this->dictionary[$toLanguage][$context][$string] = '';

        if (is_file($dictionaryFile)) {
          file_put_contents(
            $dictionaryFile,
            json_encode(
              $this->dictionary[$toLanguage],
              JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
          );
        }
      } else {
        $translated = $dictionary[$context][$string];
      }
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
    // } else if ($this->requestedUri == "adios/react.js") {
    //   $jsCache = $this->renderReactJsBundle();
    //   $cachingTime = 3600;

    //   header("Content-type: text/js");
    //   header("ETag: ".md5($jsCache));
    //   header($headerExpires);
    //   header("Pragma: cache");
    //   header($headerCacheControl);

    //   echo $jsCache;

    //   exit();
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

    foreach ($this->registeredModels as $modelName) {
      $model = $this->getModel($modelName);
      $model->dropTableIfExists();
    }

    $this->console->info("Database is empty, installing models.");

    $this->db->startTransaction();

    foreach ($this->registeredModels as $modelName) {
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

    // foreach ($this->registeredModels as $modelName) {
    //   try {
    //     $model = $this->getModel($modelName);

    //     $start = microtime(TRUE);

    //     $model->createSqlForeignKeys();
    //     $this->console->info("Indexes for model {$modelName} installed.", ["duration" => round((microtime(true) - $start) * 1000, 2)." msec"]);
    //   } catch (\Exception $e) {
    //     $this->console->error("Indexes installation for model {$modelName} failed.", ["exception" => $e->getMessage()]);
    //   } catch (\Illuminate\Database\QueryException $e) {
    //     //
    //   } catch (\ADIOS\Core\Exceptions\DBException $e) {
    //     //
    //   }
    // }

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

  public function extractParamsFromRequest(): array {
    $route = '';
    $params = [];

    if (php_sapi_name() === 'cli') {
      $params = @json_decode($_SERVER['argv'][2] ?? "", TRUE);
      if (!is_array($params)) { // toto nastane v pripade, ked $_SERVER['argv'] nie je JSON string
        $params = $_SERVER['argv'];
      }
      $route = $_SERVER['argv'][1] ?? "";
    } else {
      $params = \ADIOS\Core\Helper::arrayMergeRecursively(
        array_merge($_GET, $_POST),
        json_decode(file_get_contents("php://input"), true) ?? []
      );
      unset($params['route']);
    }

    return $params;
  }

  public function extractRouteFromRequest(): string {
    $route = '';

    if (php_sapi_name() === 'cli') {
      $route = $_SERVER['argv'][1] ?? "";
    } else {
      $route = $_REQUEST['route'] ?? '';
    }

    return $route;
  }

  /**
   * Renders the requested content. It can be the (1) whole desktop with complete <html>
   * content; (2) the HTML of a controller requested dynamically using AJAX; or (3) a JSON
   * string requested dynamically using AJAX and further processed in Javascript.
   *
   * @param  mixed $params Parameters (a.k.a. arguments) of the requested controller.
   * @throws \ADIOS\Core\Exception When running in CLI and requested controller is blocked for the CLI.
   * @throws \ADIOS\Core\Exception When running in SAPI and requested controller is blocked for the SAPI.
   * @return string Rendered content.
   */
  public function render(string $routeUrl = '', array $params = []) {

    try {

      \ADIOS\Core\Helper::clearSpeedLogTags();
      \ADIOS\Core\Helper::addSpeedLogTag("render1");

      // Find-out which route is used for rendering

      if (empty($routeUrl)) $routeUrl = $this->extractRouteFromRequest();
      if (count($params) == 0) $params = $this->extractParamsFromRequest();

      $this->routeUrl = $routeUrl;
      $this->params = $params;

      // Apply routing and find-out which controller, permision and rendering params will be used
      // list($this->controller, $this->view, $this->permission, $this->params) = $this->router->applyRouting($this->route, $this->params);
      list($this->route, $this->params) = $this->router->applyRouting($this->routeUrl, $this->params);
      $this->console->info("applyRouting for {$this->routeUrl}: " . print_r($this->route, true));

      $this->controller = $this->route['controller'] ?? '';
      $this->view = $this->route['view'] ?? '';
      $this->permission = $this->route['permission'] ?? '';

      $this->onAfterRouting();

      if (isset($this->params['sign-out'])) {
        unset($_SESSION[_ADIOS_ID]);

        setcookie(_ADIOS_ID.'-user', '', 0);
        setcookie(_ADIOS_ID.'-language', '', 0);

        header("Location: {$this->config['accountUrl']}?");
        exit();
      }

      // Check if controller exists or if it can be used
      if (empty($this->controller)) {
        $controllerClassName = \ADIOS\Core\Controller::class;
      } else if (!$this->controllerExists($this->controller)) {
        throw new \ADIOS\Core\Exceptions\ControllerNotFound($this->controller);
      } else {
        $controllerClassName = $this->getControllerClassName($this->controller);
      }

      \ADIOS\Core\Helper::addSpeedLogTag("render2");

      // Create the object for the controller
      $this->controllerObject = new $controllerClassName($this, $this->params);

      if (empty($this->permission) && !empty($this->controllerObject->permission)) {
        $this->permission = $this->controllerObject->permission;
      }

      // Perform some basic checks
      if (php_sapi_name() === 'cli') {
        if (!$controllerClassName::$cliSAPIEnabled) {
          throw new \ADIOS\Core\Exceptions\GeneralException("Controller is not enabled in CLI interface.");
        }
      } else {
        if (!$controllerClassName::$webSAPIEnabled) {
          throw new \ADIOS\Core\Exceptions\GeneralException("Controller is not enabled in WEB interface.");
        }
      }

      \ADIOS\Core\Helper::addSpeedLogTag("render3");

      if (
        !$this->userLogged
        && $this->controllerObject->requiresUserAuthentication
      ) {
        $this->controllerObject = new \ADIOS\Controllers\Login($this);
        $this->view = ($this->config['appNamespace'] ?? 'App') . '/Views/Login';
        $this->permission = "";
      }

      // Kontrola permissions

      if ($this->controllerObject->requiresUserAuthentication) {
        $this->router->checkPermission($this->permission);
      }

      // All OK, rendering content...

      \ADIOS\Core\Helper::addSpeedLogTag("render4");

      // vygenerovanie UID tohto behu
      if (empty($this->uid)) {
        $uid = $this->getUid($this->params['id'] ?? '');
      } else {
        $uid = $this->uid.'__'.$this->getUid($this->params['id']);
      }

      $this->setUid($uid);

      $return = '';

      $this->dispatchEventToPlugins("onADIOSBeforeRender", ["app" => $this]);

      unset($this->params['__IS_AJAX__']);

      $this->onBeforeRender();

      // Either return JSON string ...
      $json = $this->controllerObject->renderJson();

      \ADIOS\Core\Helper::addSpeedLogTag("render5");

      if (is_array($json)) {
        $return = json_encode($json);

      // ... Or a view must be applied.
      } else {

        $view = $this->view;
        $this->controllerObject->prepareViewParams();

        $contentParams = [
          'uid' => $this->uid,
          'user' => $this->userProfile,
          'config' => $this->config,
          'routeUrl' => $this->routeUrl,
          'route' => $this->route,
          'session' => $_SESSION[_ADIOS_ID] ?? [],
          'viewParams' => $this->controllerObject->viewParams,
          'windowParams' => $this->controllerObject->viewParams['windowParams'] ?? NULL,
        ];

        $contentHtml = $this->twig->render(
          $view,
          $contentParams
        );

        \ADIOS\Core\Helper::addSpeedLogTag("render6");

        // In some cases the result of the view will be used as-is ...
        if (($this->params['__IS_AJAX__'] ?? FALSE)|| $this->controllerObject->hideDefaultDesktop) {
          $html = $contentHtml;
        
        // ... But in most cases it will be "encapsulated" in the desktop.
        } else {
          $desktopControllerObject = $this->getDesktopController();
          $desktopControllerObject->prepareViewParams();

          $desktopParams = $contentParams;
          $desktopParams['viewParams'] = array_merge($desktopControllerObject->viewParams, $contentParams['viewParams']);
          $desktopParams['contentHtml'] = $contentHtml;

          $html = $this->twig->render(($this->config['appNamespace'] ?? 'App') . '/Views/Desktop', $desktopParams);

          \ADIOS\Core\Helper::addSpeedLogTag("render7");

        }

        \ADIOS\Core\Helper::addSpeedLogTag("render8");

        return $html;
      }

      $this->onAfterRender();

      \ADIOS\Core\Helper::addSpeedLogTag("render9");

      // \ADIOS\Core\Helper::printSpeedLogTags();

      return $return;

    } catch (\ADIOS\Core\Exceptions\ControllerNotFound $e) {
      header('HTTP/1.1 400 Bad Request', true, 400);
      return $this->renderFatal('Controller not found: ' . $e->getMessage(), FALSE);
      // $this->router->redirectTo("");
    // } catch (\ADIOS\Core\Exceptions\NotEnoughPermissionsException $e) {
    //   header('HTTP/1.1 401 Unauthorized', true, 401);
    //   return $this->renderFatal($e->getMessage(), FALSE);
    } catch (\ADIOS\Core\Exceptions\NotEnoughPermissionsException $e) {
      $message = $e->getMessage();
      if ($this->userLogged) {
        $message .= " Hint: Sign out and sign in again. {$this->config['accountUrl']}?sign-out";
      }
      return $this->renderFatal($message, FALSE);
      // header('HTTP/1.1 401 Unauthorized', true, 401);
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
      header('HTTP/1.1 400 Bad Request', true, 400);
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

      return $return;

      header('HTTP/1.1 400 Bad Request', true, 400);
    }
  }

  public function getDesktopController(): \ADIOS\Core\Controller {
    return new ($this->getCoreClass('Controllers\\Desktop'))($this);
  }

  public function getControllerClassName(string $controller) : string {
    return '\\' . str_replace('/', '\\', $controller);

    // $controllerPathParts = [];
    // foreach (explode("/", $controller) as $controllerPathPart) {
    //   // convert-dash-string-toCamelCase
    //   $controllerPathParts[] = str_replace(' ', '', ucwords(str_replace('-', ' ', $controllerPathPart)));
    // }
    // $controller = join("/", $controllerPathParts);

    // $controllerClassName = '';

    // // Dusan 31.5.2023: Tento sposob zapisu akcii je zjednoteny so sposobom zapisu modelov.
    // foreach (array_keys($this->widgets) as $widgetName) {
    //   if (strpos(strtolower($controller), strtolower($widgetName)) === 0) {
    //     $controllerClassName =
    //       '\\' . $this->config['appNamespace'] . '\\Widgets\\'
    //       . $widgetName
    //       . '\\Controllers\\'
    //       . substr($controller, strlen($widgetName) + 1)
    //     ;
    //   }
    // }
    // $controllerClassName = str_replace('/', '\\', $controllerClassName);

    // if (!class_exists($controllerClassName)) {
    //   // Dusan 31.5.2023: Tento sposob zapisu akcii je deprecated.
    //   $controllerClassName = 'ADIOS\\Controllers\\' . str_replace('/', '\\', $controller);

    //   // $this->console->warning('[ADIOS] Deprecated class name for controller ' . $controller . '.');
    // }

    // return $controllerClassName;
  }

  public function controllerExists(string $controller) : bool {
    return class_exists($this->getControllerClassName($controller));
  }

  public function renderSuccess($return) {
    return json_encode([
      "result" => "success",
      "message" => $return,
    ]);
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
        Error hash: {$errorHash} (see error log file for more information)<br/>
        ".get_class($exception)."<br/>
        Stack trace:<br/>
        <div class='trace-log'>{$traceLog}</div>
      </div>
    ";

    $this->console->error("{$errorHash}\t{$errorMessage}");

    switch (get_class($exception)) {
      case 'ADIOS\Core\Exceptions\DBException':
        $html = "
          <div class='adios exception emoji'>🥴</div>
          <div class='adios exception message'>
            Oops! Something went wrong with the database.
          </div>
          <div class='adios exception message'>
            {$errorMessage}
          </div>
          {$errorDebugInfoHtml}
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
          {$dbError}
          {$errorDebugInfoHtml}
        ";
      break;
      default:
        $html = "
          <div class='adios exception emoji'>🥴</div>
          <div class='adios exception message'>
            Oops! Something went wrong.
          </div>
          <div class='adios exception message'>
            ".$exception->getMessage()."
          </div>
          {$errorDebugInfoHtml}
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
    } catch (\Exception $e) {
      // do nothing
    }
  }

  public function saveConfigByPath(string $path, $value) {
    try {
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
    } catch (\Exception $e) {
      // do nothing
    }
  }

  public function deleteConfig($path) {
    try {
      if (!empty($path)) {
        $this->db->query("
          delete from `".(empty($this->gtp) ? '' : $this->gtp . '_')."config`
          where `path` like '".$this->db->escape($path)."%'
        ");
      }
    } catch (\Exception $e) {
      // do nothing
    }
  }

  // public function loadConfigFromDB() {
  //   try {
  //     $queryOk = $this->db->query("
  //       select
  //         *
  //       from `".(empty($this->gtp) ? '' : $this->gtp . '_')._config`
  //       order by id asc
  //     ");

  //     if ($queryOk) {
  //       while ($row = $this->db->fetchArray()) {
  //         $tmp = &$this->config;
  //         foreach (explode("/", $row['path']) as $tmp_path) {
  //           if (!isset($tmp[$tmp_path])) {
  //             $tmp[$tmp_path] = [];
  //           }
  //           $tmp = &$tmp[$tmp_path];
  //         }
  //         $tmp = $row['value'];
  //       }
  //     }
  //   } catch (\Exception $e) {
  //     // do nothing
  //   }
  // }

  public function finalizeConfig() {
    // various default values
    $this->config['widgets'] = $this->config['widgets'] ?? [];
    $this->config['protocol'] = (strtoupper($_SERVER['HTTPS'] ?? "") == "ON" ? "https" : "http");
    $this->config['timezone'] = $this->config['timezone'] ?? 'Europe/Bratislava';

    $this->config['uploadDir'] = $this->config['uploadDir'] ?? "{$this->config['accountDir']}/upload";
    $this->config['uploadUrl'] = $this->config['uploadUrl'] ?? "{$this->config['accountUrl']}/upload";

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

  public function onAfterRouting() {
    // to be overriden
  }

  public function onBeforeRender() {
    foreach ($this->widgets as $widget) {
      $widget->onBeforeRender();
    }
  }

  public function onAfterRender() {
    foreach ($this->widgets as $widget) {
      $widget->onAfterRender();
    }
  }

  ////////////////////////////////////////////////



  public function getUid($uid = '') {
    if (empty($uid)) {
      $tmp = $this->controller.'-'.time().rand(100000, 999999);
    } else {
      $tmp = $uid;
    }

    $tmp = str_replace('\\', '/', $tmp);
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
      dirname(__FILE__)."/../Assets/Css/sb-admin-2.css",
      dirname(__FILE__)."/../Assets/Css/components.css",
      dirname(__FILE__)."/../Assets/Css/colors.css",
    ];

    // foreach (scandir(dirname(__FILE__).'/../Assets/Css/Ui') as $file) {
    //   if ('.css' == substr($file, -4)) {
    //     $cssFiles[] = dirname(__FILE__)."/../Assets/Css/Ui/{$file}";
    //   }
    // }

    // foreach (scandir($this->widgetsDir) as $widget) {
    //   if (in_array($widget, [".", ".."])) continue;
    //   if (is_file($this->widgetsDir."/{$widget}/Main.css")) {
    //     $cssFiles[] = $this->widgetsDir."/{$widget}/Main.css";
    //   }

    //   if (is_dir($this->widgetsDir."/{$widget}/Assets/Css")) {
    //     foreach (scandir($this->widgetsDir."/{$widget}/Assets/Css") as $widgetCssFile) {
    //       $cssFiles[] = $this->widgetsDir."/{$widget}/Assets/Css/{$widgetCssFile}";
    //     }
    //   }
    // }

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

  // public function renderReactJsBundle(): string {
  //   $reactFolders = [
  //     dirname(__FILE__) . '/..',
  //     $this->config['srcDir']
  //   ];

  //   $jsFilesContent = "";

  //   foreach ($reactFolders as $reactFolder) {
  //     $jsFilesContent .= $this->scanReactFolder($reactFolder);
  //   }

  //   return $jsFilesContent;
  // }

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

    // foreach (scandir(dirname(__FILE__).'/../Assets/Js/Ui') as $file) {
    //   if ('.js' == substr($file, -3)) {
    //     $jsFiles[] = dirname(__FILE__)."/../Assets/Js/Ui/{$file}";
    //   }
    // }

    // if (is_dir($this->widgetsDir)) {
    //   foreach (scandir($this->widgetsDir) as $widget) {
    //     if (!in_array($widget, [".", ".."]) && is_file($this->widgetsDir."/{$widget}/main.js")) {
    //       $jsFiles[] = $this->widgetsDir."/{$widget}/main.js";
    //     }

    //     if (is_dir($this->widgetsDir."/{$widget}/Assets/Js")) {
    //       foreach (scandir($this->widgetsDir."/{$widget}/Assets/Js") as $widgetJsFile) {
    //         $jsFiles[] = $this->widgetsDir."/{$widget}/Assets/Js/{$widgetJsFile}";
    //       }
    //     }
    //   }
    // }

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
