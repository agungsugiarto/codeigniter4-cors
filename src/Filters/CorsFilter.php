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
        $config = Factories::config('Cors');

        $this->cors = new ServiceCors([
            'allowedHeaders'         => $config->allowedHeaders,
            'allowedMethods'         => $config->allowedMethods,
            'allowedOrigins'         => $config->allowedOrigins,
            'allowedOriginsPatterns' => $config->allowedOriginsPatterns,
            'exposedHeaders'         => $config->exposedHeaders,
            'maxAge'                 => $config->maxAge,
            'supportsCredentials'    => $config->supportsCredentials,
        ]);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (! $response->hasHeader('Access-Control-Allow-Origin')) {
            return $this->cors->addActualRequestHeaders($response, $request);
        }
    }
}
