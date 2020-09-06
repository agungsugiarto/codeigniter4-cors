<?php

namespace Fluent\Cors\Filters;

use CodeIgniter\Config\Config;
use CodeIgniter\Config\Services;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Fluent\Cors\ServiceCors;

class CorsFilter implements FilterInterface
{
    /**
     * @var \Fluent\Cors\ServiceCors $cors
     */
    protected $cors;

    /**
     * Constructor.
     *
     * @param array $options
     * @return void
     */
    public function __construct()
    {
        $this->cors = new ServiceCors(static::defaultOptions());
    }

    /**
     * @inheritdoc
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if ($this->cors->isPreflightRequest($request)) {
            $response = $this->cors->handlePreflightRequest($request);
            $this->cors->varyHeader($response, 'Access-Control-Request-Method');

            return $response;
        }
    }

    /**
     * @inheritdoc
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if ($this->cors->isCorsRequest($request)) {
            $this->cors->handleRequest($request, $response);
        }
        
        return $response;
    }

    /**
     * Default config options.
     *
     * @return array
     */
    protected static function defaultOptions()
    {
        $config = Config::get('Cors');

        return [
            'allowedHeaders'      => $config->allowedHeaders,
            'allowedMethods'      => $config->allowedMethods,
            'allowedOrigins'      => $config->allowedOrigins,
            'exposedHeaders'      => $config->exposedHeaders,
            'maxAge'              => $config->maxAge,
            'supportsCredentials' => $config->supportsCredentials,
        ];
    }
}
