<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core;

class ViewWithController {

  const DISPLAY_MODE_WINDOW = 'window';
  const DISPLAY_MODE_DESKTOP = 'desktop';
  const DISPLAY_MODE_INLINE = 'inline';
  
  public ?\ADIOS\Core\Loader $app = null;

  public string $uid = "";

  public bool $useSession = FALSE;
  public array $params = [];
  public string $fullName = "";
  public string $shortName = "";
  public array $classes = [];
  public array $html = [];
  public array $attrs = [];

  public $displayMode = '';

  public $window = NULL;

  public ?\ADIOS\Core\ViewWithController $parentView = NULL;
  public array $childViews = [];

  public string $twigTemplate = "";
  
  /**
   * languageDictionary
   *
   * @internal
   * @var array
   */
  // public $languageDictionary = [];
  
  /**
   * __construct
   *
   * @internal
   * @param  mixed $app
   * @param  mixed $params
   * @return void
   */
  public function __construct(
    ?\ADIOS\Core\Loader $app = NULL,
    array $params = [],
    ?\ADIOS\Core\ViewWithController $parentView = NULL
  ) {
    if (!isset($app->viewsCounter)) {
      $app->viewsCounter = 0;
    }
    ++$app->viewsCounter;

    $this->app = $app;
    $this->parentView = $parentView;

    if ($params['lpfs'] ?? FALSE) {
      $params = $this->loadParamsFromSession($params['uid']);
    }

    $this->fullName = str_replace("\\", "/", str_replace("ADIOS\\Core\\View\\", "", static::class));

    $tmp = explode("/", $this->fullName);
    $this->shortName = end($tmp);

    if (empty($params['uid'])) {
      $params['uid'] =
        'view_'
        . str_replace("\\", "", str_replace("ADIOS\\Core\\", "", get_class($this)))
        . '_' . substr(md5('_' . rand(1000, 9999)), 0, 5)
        . '_' . substr(md5('_' . rand(1000, 9999)), 0, 5)
        . '_' . substr(md5('_' . rand(1000, 9999)), 0, 5)
      ;
    }

    if ($this->useSession) {
      $tmpParams = $params;
      unset($tmpParams["_REQUEST"]);
      unset($tmpParams["_COOKIE"]);
      unset($tmpParams["uid"]);
      $this->saveParamsToSession($params['uid'], $tmpParams);
    }

    $tmp = explode("\\", get_class($this));
    $componentName = end($tmp);

    $this->params = $params;
    $this->uid = $params['uid'];
    $this->displayMode = $this->params['displayMode'] ?? 'desktop';
    $this->childViews = [];
    $this->classes = ['adios', 'ui', $componentName];
    $this->twigTemplate = $this->twigTemplate ?? "Components/{$this->fullName}";

    if (
      empty($this->displayMode)
      && $this->parentView === NULL
    ) {
      $this->displayMode = 'desktop';
    }

    if (isset($params['cssClass'])) {
      $this->addCssClass($params['cssClass']);
    }

    if ($this->displayMode == 'window') {
      if (empty($this->params['windowParams']['uid'])) {
        $this->params['windowParams']['uid'] = $this->uid . '_window';
      }
      $this->window = $this->app->view->create(
        '\\ADIOS\\Core\\ViewsWithController\\Window' . ($this->params['windowParams']['uid'] == '' ? '' : '#' . $this->params['windowParams']['uid'])
      );
      $this->window->addViewAsObject($this);
      $this->parentView = $this->window;
    }

  }


  public function __call(string $name, array $arguments)
  {
    $chr = substr($name, 0, 1);
    $firstLetterIsCapital = strtolower($chr) != $chr;

    $className = "\\ADIOS\\Core\\ViewsWithController\\{$name}";

    if (
      $firstLetterIsCapital
      && class_exists($className)
    ) {
      return new $className($this->app, $arguments[0], $arguments[1]);
    } else {
      throw new \ADIOS\Core\Exceptions\UnknownView();
    }
  }

  public function setUid(string $uid): \ADIOS\Core\ViewWithController
  {
    $this->uid = $uid;
    return $this;
  }


  public function saveParamsToSession(string $uid = "", $params = NULL)
  {
    $_SESSION[_ADIOS_ID]['views'][$uid ?? $this->uid] = is_array($params) ? $params : $this->params;
  }
  
  public function loadParamsFromSession(string $uid = "")
  {
    $params = $_SESSION[_ADIOS_ID]['views'][$uid ?? $this->uid];
    $params["uid"] = $uid ?? $this->uid;
    return $params;
  }

  public function create(
    string $view = '',
    array $params = [],
    \ADIOS\Core\ViewWithController $parentView = NULL)
  {
    list($viewClassName, $uid) = explode('#', $view);

    if (!empty($uid)) {
      $params['uid'] = $uid;
    }

    $viewClassName = str_replace('/', '\\', $viewClassName);
    if (empty($viewClassName)) {
      $viewClassName = "\\ADIOS\\Core\\ViewWithController";
    }

    // if (!class_exists($viewClassName)) {
    //   if (empty($viewClassName)) {
    //     $viewClassName = "\\ADIOS\\Core\\ViewWithController";
    //   } else {
    //     $viewClassName = "\\ADIOS\\Core\\ViewsWithController\\{$viewClassName}";
    //   }
    // }

    // var_dump($viewClassName);

    if (strpos($viewClassName, '\\') !== 0) {
      $viewClassName = '\\' . $viewClassName;
    }

    return new $viewClassName(
      $this->app,
      $params,
      $parentView
    );
  }

  public function addView(string $view = '', array $params = []): \ADIOS\Core\ViewWithController
  {
    $view = $this->create($view, $params, $this);
    $this->childViews[] = $view;
    return $view;
  }

  public function addViewAsObject(\ADIOS\Core\ViewWithController $viewObject): \ADIOS\Core\ViewWithController
  {
    $viewObject->parentView = $this;
    $this->childViews[] = $viewObject;
    return $viewObject;
  }

  public function removeAllViews(): \ADIOS\Core\ViewWithController {
    foreach (array_keys($this->childViews) as $key) {
      unset($this->childViews[$key]);
    }

    $this->childViews = [];

    return $this;
  }

  /**
   * translate
   *
   * @internal
   * @param  mixed $string
   * @param  mixed $context
   * @param  mixed $toLanguage
   * @return void
   */
  public function translate(string $string, array $vars = []): string
  {
    return $this->app->translate($string, $vars, $this);
  }
  
  /**
   * @internal
   */
  public function add($subviews, $panel = 'default')
  {
    if (is_array($subviews)) {
      foreach ($subviews as $subview) {
        $this->add($subview, $panel);
      }
    } elseif (is_string($subviews) || '' == $subviews) {
      $this->childViews[$panel][] = $subviews;
    } else {
      $subviews->param('parent_uid', $this->uid);
      if ('' != $subviews->params['key']) {
        $this->childViews[$panel][$subviews->params['key']] = $subviews;
      } else {
        $this->childViews[$panel][] = $subviews;
      }
    }

    return $this;
  }
  
  /**
   * cadd
   *
   * @internal
   * @param  mixed $component_name
   * @param  mixed $params
   * @return void
   */
  // public function cadd($component_name, $params = null) {
  //   $this->add($this->app->view->create($component_name, $params));

  //   return $this;
  // }
  
  /**
   * param
   *
   * @internal
   * @param  mixed $param_name
   * @param  mixed $param_value
   * @return void
   */
  // public function param($param_name, $param_value = null) {
  //   if (null === $param_value) {
  //     return $this->params[$param_name];
  //   } else {
  //     $this->params[$param_name] = $param_value;
  //   }

  //   return $this;
  // }

  /**
   * Funkcia slúži na rekurzívny merge viacúrovňových polí.
   *
   * @version 1
   *
   * @internal
   * @param array $params Pole pôvodných parametrov
   * @param array $update Pole parametrov, ktoré aktualizujú a dopĺňajú $params
   *
   * @return array Zmergované výsledné pole
   */
  public function params_merge($params, $update)
  {
    if (!is_array($params)) {
      $params = [];
    }

    if (_count($update)) {
      foreach ($update as $key => $val) {
        if (_count($val)) {
          $params[$key] = $this->params_merge(
            $params[$key] ?? NULL,
            $val
          );
        } else {
          $params[$key] = $val;
        }
      }
    }

    return $params;
  }
  
  /**
   * html
   *
   * @internal
   * @param  mixed $new_html
   * @param  mixed $panel
   * @return void
   */
  public function html($new_html = null, $panel = 'default')
  {
    if (null === $new_html) {
      return $this->html[$panel];
    } else {
      $this->html[$panel] = $new_html;
    }

    return $this;
  }
  
  /**
   * on
   *
   * @internal
   * @param  mixed $event_name
   * @param  mixed $event_js
   * @return void
   */
  public function on($event_name, $event_js)
  {
    $this->params['on'][$event_name] .= $event_js;

    return $this;
  }
  
  /**
   * addCssClass
   *
   * @internal
   * @param  mixed $cssClass
   * @return void
   */
  public function addCssClass(string $cssClass): \ADIOS\Core\ViewWithController
  {
    if (!empty($cssClass)) $this->classes[] = $cssClass;
    return $this;
  }

  /**
   * removeCssClass
   *
   * @internal
   * @param  mixed $cssClass
   * @return void
   */
  public function removeCssClass(string $cssClass): \ADIOS\Core\ViewWithController
  {
    $tmpClasses = [];
    foreach ($this->classes as $class) {
      if ($class != $cssClass) {
        $tmpClasses[] = $class;
      }
    }
    $this->classes = $tmpClasses;

    return $this;
  }

  public function getCssClassesString(): string
  {
    return join(" ", $this->classes);
  }
  
  /**
   * Used to return values for TWIG renderer. Applies only in TWIG template of the action.
   *
   * @internal
   * @return array Array of parameters used in TWIG
   */
  public function getTwigParams(): array
  {
    return $this->params;
  }
  
  /**
   * render
   *
   * @internal
   * @param  mixed $panel
   * @return void
   */
  public function render(string $panel = ''): string
  {

    if (
      !empty($this->twigTemplate)
       && is_file(__DIR__."/../Templates/{$this->twigTemplate}.twig")
    ) {

      $twigParams = [
        "uid" => $this->uid,
        "gtp" => $this->app->gtp,
        "config" => $this->app->config,
        "user" => $this->app->auth->user,
        "dictionary" => $this->app->dictionary,
        "view" => $this->params,
        "params" => $this->getTwigParams(),
      ];

      $html = $this->app->twig->render(
        'ADIOS\\Templates\\' . $this->twigTemplate,
        $twigParams
      );
    } else {
      $html = '';
      foreach ($this->childViews as $view) {
        if ($view instanceof \ADIOS\Core\ViewWithController) {
          $html .= $view->render();
        } else {
          var_dump($view);
        }
      }
    }

    // if ($this->parentView === NULL) {
    // } else {
    //   $html .= "[has_parent]";
    // }

    return $this->applyDisplayMode((string) $html);
  }

  public function applyDisplayMode(string $content, string $displayMode = '') : string
  {
    if (empty($displayMode)) $displayMode = $this->displayMode;

    switch ($displayMode) {
      case self::DISPLAY_MODE_WINDOW:
        if (!$this->window instanceof \ADIOS\Core\ViewsWithController\Window) {
          $this->window = new \ADIOS\Core\ViewsWithController\Window($this->app, []);
          $this->window->setTitle('Window Title');
          $this->window->setCloseButton(
            $this->create('\\ADIOS\\Core\\ViewsWithController\\Button', [
              'type' => 'close',
              'onclick' => "
                window_close('{$this->window->uid}');
              ",
            ])
          );
        }

        $this->window->setContent($content);
        if (is_array($this->params['windowParams'])) {
          if (!empty($this->params['windowParams']['title'])) {
            $this->window->setTitle($this->params['windowParams']['title']);
          }
          $this->window->addCssClass($this->params['windowParams']['cssClass'] ?? '');

          if ($this->params['windowParams']['fullscreen']) {
            $this->window->addCssClass('fullscreen');
          }

          if ($this->params['windowParams']['modal']) {
            $this->window->addCssClass('modal');
          }
        }

        $html = $this->window->render();
      break;
      case self::DISPLAY_MODE_DESKTOP:
        $html = $content;
      break;
      case self::DISPLAY_MODE_INLINE:
      default:
        $html = $content;
      break;
    }

    return $html;
  }
  
  /**
   * main_params
   *
   * @internal
   * @return void
   */
  public function main_params() {
    // pre inputy, ktore su disabled sa nastavi tento parameter, aby sa nedostali do udajov selectovanych cez ADIOS.views.Form.get_values
    if ('m_ui_input' == get_class($this)) {
      if ($this->params['disabled']) {
        $adios_disabled_attribute = "adios-do-not-serialize='1'";
      }
    }

    return "
      id='{$this->params['uid']}'
      class='".join(' ', $this->classes)."'
      style='{$this->params['cssStyle']}'
      {$adios_disabled_attribute}
    ";
  }
  
  /**
   * attr
   *
   * @internal
   * @param  mixed $attr
   * @param  mixed $val
   * @return void
   */
  public function attr($attr, $val) {
    $this->attrs[$attr] = $val;
  }

  public function findParentView($viewName) {
    $result = NULL;

    if ($this->parentView !== NULL) {
      if (get_class($this->parentView) == trim($viewName, '\\')) {
        $result = $this->parentView;
      } else {
        $result = $this->parentView->findParentView($viewName);
      }
    }

    return $result;
  }

}
