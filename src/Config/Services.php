<?php

namespace Fluent\Cors\Config;

use CodeIgniter\Config\BaseService;
use Fluent\Cors\ServiceCors;

class Services extends BaseService
{
    public static function cors(?Cors $config = null, bool $getShared = true)
    {
        $config ??= config('cors');

        if ($getShared) {
            return static::getSharedInstance('cors', $config);
        }

        return new ServiceCors($config);
    }
}