<?php

namespace ADIOS\Core\Web;

// // ADIOS Web autoload function
// spl_autoload_register(function($className) {
//   global $___ADIOSObject;

//   $className = str_replace("\\", "/", $className);

//   $rootNamespace = substr($className, 0, strpos($className, "/"));
//   $restNamespace = substr($className, strpos($className, "/") + 1);

//   switch ($rootNamespace) {
//     case "Cascada":
//       include(dirname(__FILE__)."/src/{$restNamespace}.php");
//     break;
//     case "WEB":
//       if (!is_object($___ADIOSObject)) return;
//       if (empty($___ADIOSObject->rootDir) || !@include("{$___ADIOSObject->rootDir}/{$restNamespace}.php")) {
//         include("{$___ADIOSObject->themeDir}/{$restNamespace}.php");
//       }
//     break;
//   }

// });

// Loader class
class Loader {
  public $adios = NULL;

  public array $controllers = [];
  public $router = NULL;

  public array $config = [];
  public string $rootDir = "";
  public string $rewriteBase = "";
  public string $pageUrl = "";
  public string $rootUrl = "";
  public string $relativeUrl = "";
  public string $language = "";
  public string $themeDir = "";
  public string $template = "";
  public string $theme = "";
  public $twig = NULL;
  public array $twigParams = [];
  public bool $twigCacheDir = FALSE;
  public $JSONResult = NULL;
  public array $urlVariables = [];
  public array $assetsUrlMap = [];
  public string $assetCacheDir = "";
  public string $outputHtml = "";

  private $canContinueWithRendering = TRUE;

  function __construct($adios, $config) {

    $this->adios = $adios;
    $this->config = $config;
    $this->rootDir = $config['rootDir'] ?? '';
    $this->rewriteBase = $config['rewriteBase'] ?? '';
    $this->twigCacheDir = $config['twigCacheDir'] ?? FALSE;
    $this->relativeUrl = $config['relativeUrl'] ?? '';
    $this->themeDir = $config['themeDir'] ?? '';
    $this->assetCacheDir = $config['assetCacheDir'] ?? '';

    if (
      substr($this->rewriteBase, 0, 1) != "/"
      || substr($this->rewriteBase, -1) != "/"
    ) {
      throw new \Exception("RewriteBase for web must start and end with a slash (/).");
    }

    // extract pageUrl
    if ($this->rewriteBase == "/") {
      $this->pageUrl = $_SERVER['REQUEST_URI'];
    } elseif (strlen($this->rewriteBase) > strlen($_SERVER['REQUEST_URI'] ?? "")) {
      $this->pageUrl = "";
    } else {
      $this->pageUrl = str_replace(rtrim($this->rewriteBase, "/"), "", $_SERVER['REQUEST_URI']);
    }

    if (strpos($_SERVER['REQUEST_URI'] ?? "", "?") !== FALSE) {
      $this->pageUrl =
        substr($this->pageUrl, 0,
          strpos($this->pageUrl, "?")
        )
      ;
    }

    $this->pageUrl = trim($this->pageUrl, "/");

    // calculate rootUrl
    // $this->rootUrl = trim("./".str_repeat("../", substr_count($this->pageUrl, "/")), "/");
    $this->rootUrl = rtrim($this->rewriteBase, "/");

    if ($this->themeDir != "") {
      $this->assetsUrlMap["theme/assets/"] = "{$this->themeDir}/Assets/";
      $this->initTwig();
    }

    // connect, if connection info provided
    if (!empty($config['connection'])) {
      $capsule = new \Illuminate\Database\Capsule\Manager;
      $capsule->addConnection($config["connection"]);
      $capsule->setAsGlobal();
      $capsule->bootEloquent();
    }
  }

  public function initTwig() {
    if ($this->themeDir == "") {
      $this->themeDir = "{$this->rootDir}/theme";
    }

    // initialize twig
    $twigParams = [];
    if (is_string($this->twigCacheDir)) {
      $twigParams['cache'] = $this->twigCacheDir;
    } else {
      $twigParams['cache'] = FALSE;
    }

    $twigParams['debug'] = $this->config['twigDebugEnabled'] ?? FALSE;

    $twigLoader = new \Twig\Loader\FilesystemLoader($this->themeDir . '/Pages');
    $this->twig = new \Twig\Environment($twigLoader, $twigParams);
    $this->twig->addExtension(new \Twig\Extension\DebugExtension());
    $this->twig->addExtension(new \Twig\Extension\StringLoaderExtension());

    $this->twig->addFunction(new \Twig\TwigFunction(
      'translate',
      function ($string) {
        return $this->adios->translate($string, []);
      }
    ));
    $this->twig->addFunction(new \Twig\TwigFunction('adiosView', function ($uid, $view, $params) {
      if (!is_array($params)) {
        $params = [];
      }
      return $this->adios->view->create(
        $view . (empty($uid) ? '' : '#' . $uid),
        $params
      )->render();
    }));
    $this->twig->addFunction(new \Twig\TwigFunction('adiosRender', function ($action, $params = []) {
      return $this->adios->render($action, $params);
    }));

    // set default twig params
    $this->setTwigParams([
      "rootUrl" => $this->rootUrl,
      "pageUrl" => $this->pageUrl,
      "rewriteBase" => $this->rewriteBase,
      "urlVariables" => $this->urlVariables ?? [],
      "template" => $this->template ?? "",
      "adiosWebInitJS" => "
        <script>
          adiosWeb = {
            'rootUrl': '{$this->rootUrl}',
          }
        </script>
      ",
      "_GET" => $_GET,
      "_POST" => $_POST,
    ]);

    return $this;

  }

  function setRouter($router) {
    $this->router = $router;
    $this->router->adios = $this->adios;

    // perform redirects, if any
    $this->router->performRedirects();

    return $this;
  }

  // function rebuildHTAccess($htaccessFilename = "") {
  //   $this->router->rebuildHTAccess($htaccessFilename);
  //   return $this;
  // }

  function addController($controller) {
    $this->controllers[] = $controller;
    return $this;
  }

  function addControllers($controllers) {
    if (is_array($controllers)) {
      foreach ($controllers as $controller) {
        $this->controllers[] = $controller;
      }
    }

    return $this;
  }

  function addControllersByName($controllerNames) {
    if (is_array($controllerNames)) {
      foreach ($controllerNames as $controllerName) {
        $this->controllers[] = new $controllerName($this);
      }
    }

    return $this;
  }

  function setTwigParam($key, $value) {
    $this->twigParams[$key] = $value;

    return $this;
  }

  function setTwigParams($params) {
    if (is_array($params)) {
      foreach ($params as $key => $value) {
        $this->setTwigParam($key, $value);
      }
    }

    return $this;
  }

  function setJSONResult($result) {
    $this->JSONResult = $result;
  }

  function redirectTo($pageUrl, $redirectType = NULL) {
    if ($redirectType == 301) {
      header("HTTP/1.1 301 Moved Permanently");
    }

    header('Location: ' . $this->rewriteBase . $pageUrl);

    exit();
  }

  public function cancelRendering() {
    $this->canContinueWithRendering = FALSE;
  }

  public function render() {
    // get template name
    $this->template = $this->router->getCurrentPageTemplate();

    // check if CSS, JS or Image should be rendered
    foreach ($this->assetsUrlMap as $urlPart => $mapping) {
      if (preg_match('/^'.str_replace("/", "\\/", $urlPart).'/', $this->template, $urlMapVariables)) {

        if ($mapping instanceof \Closure) {
          $sourceFile = $mapping($this, $this->template, $urlMapVariables);
        } else {
          $sourceFile = $mapping.str_replace($urlPart, "", $this->template);
        }

        $ext = strtolower(pathinfo($this->template, PATHINFO_EXTENSION));

        $cachingTime = 3600;
        $headerExpires = "Expires: ".gmdate("D, d M Y H:i:s", time() + $cachingTime) . " GMT";
        $headerCacheControl = "Cache-Control: max-age={$cachingTime}";

        $assetContent = @file_get_contents($sourceFile);

        if (!is_dir($this->assetCacheDir)) {
          @mkdir($this->assetCacheDir, 0775);
        }

        if (!empty($this->assetCacheDir) && is_dir($this->assetCacheDir)) {
          $cacheFile = "{$this->assetCacheDir}/".md5($this->template).".{$ext}";
          @file_put_contents($cacheFile, $assetContent);
        }

        switch ($ext) {
          case "css":
          case "js":
            header("Content-type: text/{$ext}");
            header($headerExpires);
            header("Pragma: cache");
            header($headerCacheControl);
            echo $assetContent;
          break;
          case "eot":
          case "ttf":
          case "woff":
          case "woff2":
            header("Content-type: font/{$ext}");
            header($headerExpires);
            header("Pragma: cache");
            header($headerCacheControl);
            echo $assetContent;
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
            echo $assetContent;
          break;
        }

        exit();

      }
    }

    // validate template name
    if (
      strpos($this->template, "..\\") !== FALSE
      || strpos($this->template, "../") !== FALSE
    ) {
      throw new \Exception("ADIOS Web: Invalid template name {$this->template}.");
    }

    // ak nie su ziadne kontrolery, pokusim sa ich pridat sam
    if (empty($this->controllers)) {
      $this->addControllers($this->router->getCurrentPageControllers());
    }

    // vyparsujem url variables a doplnim ich o _GET hodnoty
    $this->urlVariables = $this->router->getCurrentPageUrlVariables();
    foreach ($_GET as $key => $value) {
      $this->urlVariables[$key] = $value;
    }

    // overridnem twigParams tym, co je nastavene v siteMap
    $this->twigParams = array_merge(
      $this->twigParams,
      $this->router->getCurrentPageTemplateVariables()
    );

    // assume that the getViewParams does not block further rendering
    $this->canContinueWithRendering = TRUE;

    // pre render
    foreach ($this->controllers as $controller) {
      $params = $controller->getViewParams();

      $render = $controller->render($params);
      if (is_string($render)) {
        $this->outputHtml = $render;
        $this->canContinueWithRendering = FALSE;
      }

      if (!$this->canContinueWithRendering) {
        break;
      }
    }

    // render
    if ($this->canContinueWithRendering) {
      if ($this->JSONResult === NULL) {
        $templateFile = $this->template . '.twig';
        if (is_file("{$this->themeDir}/Pages/{$templateFile}")) {
          $this->outputHtml = $this->twig->render(
            $templateFile,
            $this->twigParams
          );
        } else {
          // vyskusam este 404 not found template
          $templateFile = $this->router->getNotFoundTemplate() . '.twig';
          if (is_file("{$this->themeDir}/Pages/{$templateFile}")) {
            $this->outputHtml = $this->twig->render(
              $templateFile,
              $this->twigParams
            );
          } else {
            throw new \Exception("ADIOS Web: Template {$this->template} not found.");
          }
        }
      } else {
        $this->outputHtml = @json_encode($this->JSONResult);
      }

      // post render
      foreach ($this->controllers as $controller) {
        $controller->postRender();
      }
    }

    // return
    return $this->outputHtml;
  }

}

