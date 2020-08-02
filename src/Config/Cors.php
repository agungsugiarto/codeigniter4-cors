<?php

namespace Fluent\Cors\Config;

use CodeIgniter\Config\BaseConfig;

class Cors extends BaseConfig
{
   public $allowedHeaders = ['*'];

   public $allowedMethods = ['*'];

   public $allowedOrigins = ['*'];

   public $allowedOriginsPatterns = [];

   public $exposedHeaders = [];

   public $maxAge = 0;

   public $supportsCredentials = false;
}
