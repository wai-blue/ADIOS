<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core;

class PDO {
  public ?\ADIOS\Core\Loader $app = null;
  public ?\PDO $connection = null;
  
  public function __construct($app) {
    $this->app = $app;
  }

  public function connect() {
    $dbHost = $this->app->config['db_host'] ?? '';
    $dbPort = $this->app->config['db_port'] ?? '';
    $dbUser = $this->app->config['db_user'] ?? '';
    $dbPassword = $this->app->config['db_password'] ?? '';
    $dbName = $this->app->config['db_name'] ?? '';
    $dbCodepage = $this->app->config['db_codepage'] ?? 'utf8mb4';

    if (!empty($dbHost)) {
      $this->connection = new \PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCodepage}",
        $dbUser,
        $dbPassword
      );
    }

  }

  public function debugQuery($query, $data = []) {
    $stmt = $this->connection->prepare($query);
    $stmt->execute($data);
    ob_start();
    $stmt->debugDumpParams();
    _var_dump(ob_get_clean());
  }

  public function execute($query, $data = []) {
    $stmt = $this->connection->prepare($query);
    $stmt->execute($data);
  }

  public function fetchAll($query, $data = []) {
    $stmt = $this->connection->prepare($query);
    $stmt->execute($data);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  public function fetchFirst($query, $data = []) {
    $tmp = $this->fetchAll($query, $data);
    return reset($tmp);
  }

}
