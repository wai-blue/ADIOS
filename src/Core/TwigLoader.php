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

    if (strpos($templateName, "{$appNamespace}/") === 0) {
      $templateName = substr($templateName, strlen($appNamespace . '/'));
      $templateRootDir = $this->app->config['twigRootDir'] ?? $this->app->config['dir'] . '/src';
      $templateFile = $templateRootDir . '/' . $templateName . '.twig'
      ;
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