<?php

namespace ADIOS\Core;

/**
 * Core implementation of ADIOS controller
 * 
 * 'Controller' is fundamendal class for generating HTML content of each ADIOS call. Controllers can
 * be rendered using Twig template or using custom render() method.
 * 
 */
class Controller {
  /**
   * Reference to ADIOS object
   */
  protected ?\ADIOS\Core\Loader $adios = null;
    
  /**
   * Shorthand for "global table prefix"
   */
  protected string $gtp = "";

  /**
   * Array of parameters (arguments) passed to the controller
   */
  public array $params;

  /**
   * TRUE/FALSE array with permissions for the user role
   */
  public static array $permissionsByUserRole = [];
  
  /**
   * If set to FALSE, the rendered content of controller is available to public
   */
  public bool $requiresUserAuthentication = TRUE;

  /**
   * If set to TRUE, the default ADIOS desktop will not be added to the rendered content
   */
  public bool $hideDefaultDesktop = FALSE;

  /**
   * If set to FALSE, the controller will not be rendered in CLI
   */
  public static bool $cliSAPIEnabled = TRUE;

  /**
   * If set to FALSE, the controller will not be rendered in WEB
   */
  public static bool $webSAPIEnabled = TRUE;

  public array $dictionary = [];
  public array $viewParams = [];

  public string $name = "";
  public string $shortName = "";
  public string $permission = "";
  public string $uid = "";
  public string $controller = "";
  public string $myRootFolder = "";
  public string $twigTemplate = "";
  public string $view = "";

  function __construct(\ADIOS\Core\Loader $adios, array $params = [])
  {
    $this->name = str_replace("\\", "/", str_replace("ADIOS\\", "", get_class($this)));
    $this->adios = $adios;
    $this->params = $params;
    $this->uid = $this->adios->uid;
    $this->gtp = $this->adios->gtp;
    $this->controller = $this->adios->controller;

    $this->shortName = $this->name;
    $this->shortName = str_replace('App/Widgets/', '', $this->shortName);
    $this->shortName = str_replace('Controllers/', '', $this->shortName);

    $this->permission = $this->shortName;

    $this->myRootFolder = str_replace("\\", "/", dirname((new \ReflectionClass(get_class($this)))->getFileName()));

    if (!is_array($this->params)) {
      $this->params = [];
    }

    if (!empty($this->adios->config['templates'][static::class])) {
      $this->twigTemplate = $this->adios->config['templates'][static::class];
    }

    $this->init();

  }

  /**
    * Validates inputs ($this->params) used for the TWIG template.
    *
    * return bool True if inputs are valid, otherwise false.
    */
  public function validateInputs(): bool {
    return TRUE;
  }

  /**
   * Executed at the end of the constructor.
   * Could be used e.g. to validate input parameters.
   *
   * @throws Exception Should throw an exception on error.
   */
  public function init() {
    //
  }

  /**
   * Returns the object of the controller for rendering the desktop.
   *
   * @return Object of the controller for rendering the desktop.
   */
  public function getDesktopController(array $desktopParams = []): \ADIOS\Core\Controller {
    return new ($this->adios->getCoreClass('Controllers\\Desktop'))($this->adios, $desktopParams);
  }

  /**
   * If the controller shall only return JSON, this method must be overriden.
   *
   * @return array Array to be returned as a JSON.
   */
  public function renderJson() {
    return NULL;
  }

  /**
   * If the controller shall return the HTML of the view, this method must be overriden.
   *
   * @return array View to be used to render the HTML.
   */
  public function getViewParams(): array
  {
    return $this->params ?? [];
  }
  
  /**
   * Shorthand for ADIOS core translate() function. Uses own language dictionary.
   *
   * @param  string $string String to be translated
   * @param  string $context Context where the string is used
   * @param  string $toLanguage Output language
   * @return string Translated string.
   */
  public function translate(string $string, array $vars = []): string
  {
    return $this->adios->translate($string, $vars, $this);
  }
  
  /**
   * Renders the content of requested controller using Twig template.
   * In most cases is this method overriden.
   *
   * @return string Rendered HTML content of the controller.
   * @return array Key-value pair of output values. Will be converted to JSON.
   * 
   * @throws \Twig\Error\RuntimeError
   * @throws \Twig\Error\LoaderError
   */
  public function render()
  {
    $twigParams = $this->params;

    $twigParams["uid"] = $this->adios->uid;
    $twigParams["gtp"] = $this->adios->gtp;
    $twigParams["config"] = $this->adios->config;
    $twigParams["requestedUri"] = $this->adios->requestedUri;
    $twigParams["user"] = $this->adios->userProfile;
    $twigParams["locale"] = $this->adios->locale->getAll();
    $twigParams["dictionary"] = $this->dictionary;
    $twigParams['userNotifications'] = $this->adios->userNotifications->getAsHtml();

    try {
      $tmpTemplate = empty($this->twigTemplate)
        ? str_replace("\\Controllers\\", "\\Templates\\", static::class)
        : $this->twigTemplate
      ;

      return $this->adios->twig->render(
        $tmpTemplate,
        $twigParams
      );
    } catch (\Twig\Error\RuntimeError $e) {
      throw ($e->getPrevious());
    } catch (\Twig\Error\LoaderError $e) {
      return $e->getMessage();
    }
  }

}

