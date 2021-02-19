<?php

namespace Fluent\Cors\Filters;

use CodeIgniter\Config\Factories;
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
     * {@inheritdoc}
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if ($this->cors->isPreflightRequest($request)) {
            $response = $this->cors->handlePreflightRequest($request);

            return $this->cors->varyHeader($response, 'Access-Control-Request-Method');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if ($request->getMethod() === 'OPTIONS') {
            $this->cors->varyHeader($response, 'Access-Control-Request-Method');
        }

        return $this->cors->addActualRequestHeaders($response, $request);
    }

    /**
     * Default config options.
     *
     * @return array
     */
    protected static function defaultOptions()
    {
        $config = Factories::config('Cors');

        return [
            'allowedHeaders'         => $config->allowedHeaders,
            'allowedMethods'         => $config->allowedMethods,
            'allowedOrigins'         => $config->allowedOrigins,
            'allowedOriginsPatterns' => $config->allowedOriginsPatterns,
            'exposedHeaders'         => $config->exposedHeaders,
            'maxAge'                 => $config->maxAge,
            'supportsCredentials'    => $config->supportsCredentials,
        ];
    }
}
