<?php

namespace ADIOS\Core;

class ApiController extends \ADIOS\Core\Controller {

  public function response(): array
  {
    return [];
  }

  public function renderJson(): ?array {
    try {
      return $this->response();
    } catch (\Throwable $e) {
      http_response_code(400);

      return [
        'status' => 'error',
        'message' => $e->getMessage() 
      ];
    }
  }
}

