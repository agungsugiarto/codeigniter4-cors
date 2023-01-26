<?php

namespace Fluent\Cors\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Fluent\Cors\Config\Services;

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
        $this->cors = Services::cors();
    }

    /**
     * {@inheritdoc}
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // For Preflight, return the Preflight response
        if ($this->cors->isPreflightRequest($request)) {
            $response = $this->cors->handlePreflightRequest($request);
            $response = $this->cors->varyHeader($response, 'Access-Control-Request-Method');

            return $response;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if ($request->getMethod(true) === 'OPTIONS') {
            $response = $this->cors->varyHeader($response, 'Access-Control-Request-Method');
        }

        if (! $response->hasHeader('Access-Control-Allow-Origin')) {
            // Add the CORS headers to the Response
            $response = $this->cors->addActualRequestHeaders($response, $request);
        }

        return $response;
    }
}
