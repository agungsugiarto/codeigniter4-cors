<?php

namespace Fluent\Cors\Filters;

use CodeIgniter\Config\Services;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Fluent\Cors\ServiceCors;

class CorsFilter implements FilterInterface
{
    /**
     * @var CorsService
     */
    protected $cors;

    /**
     * @var defaultOptions
     */
    protected $defaultOptions;


    /**
     * Constructor.
     */
    public function __construct(array $options = [])
    {
        $this->defaultOptions = self::defaultOptions();
        $this->cors = new ServiceCors(array_merge($this->defaultOptions, $options));
    }

    /**
     * Before.
     *
     * @param \CodeIgniter\HTTP\RequestInterface $request
     * @param null $arguments
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if ($this->cors->isPreflightRequest($request)) {
            $response = $this->cors->handlePreflightRequest($request);
            $this->cors->varyHeader($response, 'Access-Control-Request-Method');

            return $response;
        }

        if ($request->getMethod(true) === 'OPTIONS') {
            $this->cors->varyHeader(Services::response(), 'Access-Control-Request-Method');
        }
    }

    /**
     * After.
     *
     * @param \CodeIgniter\HTTP\RequestInterface $request
     * @param \CodeIgniter\HTTP\ResponseInterface $response
     * @param null $arguments
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (! $response->hasHeader('Access-Control-Allow-Origin')) {
            $this->cors->addActualRequestHeaders($response, $request);
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
        return [
            'allowedHeaders'         => config('Cors')->allowedHeaders,
            'allowedMethods'         => config('Cors')->allowedMethods,
            'allowedOrigins'         => config('Cors')->allowedOrigins,
            'allowedOriginsPatterns' => config('Cors')->allowedOriginsPatterns,
            'exposedHeaders'         => config('Cors')->exposedHeaders,
            'maxAge'                 => config('Cors')->maxAge,
            'supportsCredentials'    => config('Cors')->supportsCredentials,
        ];
    }
}
