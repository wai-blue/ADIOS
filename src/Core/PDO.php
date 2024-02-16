<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core;

class PDO {
  public ?\ADIOS\Core\Loader $adios = null;
  public ?\PDO $connection = null;
  
  public function __construct(&$adios) {
    $this->adios = $adios;
  }

  public function connect() {
    $dbHost = $this->adios->config['db_host'] ?? '';
    $dbPort = $this->adios->config['db_port'] ?? '';
    $dbUser = $this->adios->config['db_user'] ?? '';
    $dbPassword = $this->adios->config['db_password'] ?? '';
    $dbName = $this->adios->config['db_name'] ?? '';
    $dbCodepage = $this->adios->config['db_codepage'] ?? 'utf8mb4';

    if (!empty($dbHost) && !empty($dbPort) && !empty($dbName) && !empty($dbCodepage)) {
      $this->connection = new \PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCodepage}",
        $dbUser,
        $dbPassword
      );
    }

  }

  public function fetchAll($query, $data) {
    $stmt = $this->connection->prepare($query);
    $stmt->execute($data);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

}