<?php

namespace ADIOS\Core;

class TwigLoader implements \Twig\Loader\LoaderInterface {

  public \ADIOS\Core\Loader $app;

  public function __construct($app) {
    $this->app = $app;
  }

  /**
    * Returns the source context for a given template logical name.
    *
    * @param string $name The template logical name
    * @return \Twig\Source
    * @throws \Twig\Error\LoaderError When $name is not found
    */
  public function getSourceContext($name): \Twig\Source {
    $appNamespace = $this->app->config['appNamespace'] ?? 'App';
    $templateName = str_replace("\\", "/", $name);

    if (strpos($templateName, "ADIOS/Templates/Widgets/") === 0) {
      $templateName = str_replace("ADIOS/Templates/Widgets/", "", $templateName);
      $widget = substr($templateName, 0, strpos($templateName, "/"));
      $action = substr($templateName, strpos($templateName, "/") + 1);

      $templateFile = $this->app->widgetsDir."/{$widget}/Templates/{$action}.twig";
    } else if (strpos($templateName, "{$appNamespace}/") === 0) {
      $templateName = substr($templateName, strlen($appNamespace . '/'));
      $templateFile = 
        $this->app->config['dir']
        . '/src/' . $templateName . '.twig'
      ;
    } else if (strpos($templateName, "ADIOS/Views/") === 0) {
      $templateName = str_replace('ADIOS/', '', $templateName);

      $templateFile = __DIR__ . '/../' . $templateName . '.twig';
    } else if (strpos($templateName, "ADIOS/Templates/") === 0) {
      $templateName = str_replace("ADIOS/Templates/", "", $templateName);

      // najprv skusim hladat core template...
      $templateFile = __DIR__."/../Templates/{$templateName}.twig";

      // ...potom Widget akciu
      if (!is_file($templateFile)) {
        $tPath = explode("/", $templateName);
        $tName = array_pop($tPath);
        $tPath = join("/", $tPath);

        $templateFile = $this->app->widgetsDir."/{$tPath}/Templates/{$tName}.twig";
      }

      // ...a nakoniec Plugin akciu
      if (!is_file($templateFile)) {
        preg_match('/(\w+)\/([\w\/]+)/', $templateName, $m);
        foreach ($this->app->pluginFolders as $pluginFolder) {
          $folder = $pluginFolder."/{$name}/Models";

          $templateFile = "{$folder}/{$m[1]}/Templates/{$m[2]}.twig";
          if (is_file($templateFile)) {
            break;
          }
        }
      }

    } else {
      return new \Twig\Source($name, $name);
    }

    if (!is_file($templateFile)) {
      throw new \Twig\Error\LoaderError("Template {$name} ({$templateName}) does not exist. Tried to load '{$templateFile}'.");
    } else {
      return new \Twig\Source(file_get_contents($templateFile), $name);
    }
  }

  /**
    * Gets the cache key to use for the cache for a given template name.
    *
    * @param string $name The name of the template to load
    *
    * @return string The cache key
    *
    * @throws \Twig\Error\LoaderError When $name is not found
    */
  public function getCacheKey($name): string {
    return $name;
  }

  /**
    * Returns true if the template is still fresh.
    *
    * @param string    $name The template name
    * @param timestamp $time The last modification time of the cached template
    *
    * @return bool    true if the template is fresh, false otherwise
    *
    * @throws \Twig\Error\LoaderError When $name is not found
    */
  public function isFresh(string $name, int $time): bool {
    return true;
  }

  /**
    * Check if we have the source code of a template, given its name.
    *
    * @param string $name The name of the template to check if we can load
    *
    * @return bool    If the template source code is handled by this loader or not
    */
  public function exists(string $name) {
    return (strpos($name, "ADIOS/Templates/") === 0);
  }
}