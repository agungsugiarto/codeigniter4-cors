<?php

namespace Fluent\Cors\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Medz\Cors\Cors;

class MedzFilter implements FilterInterface
{
    /**
     * @var CorsService
     */
    protected $cors;

    /**
     * @var defaultOptions
     */
    protected $defaultOptions;

    public function __construct()
    {
        $this->defaultOptions = self::defaultOptions();
        $this->cors = new Cors($this->defaultOptions);
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $this->cors->setRequest('array', null);
        $this->cors->setResponse('array', null);
        $this->cors->handle();

        foreach ($this->cors->getResponse() as $key => $value) {
            $response->setHeader($key, $value);
        }
    }

    protected static function defaultOptions()
    {
        return [
            'allow-credentials' => config('Cors')->supportsCredentials, // set "Access-Control-Allow-Credentials" 👉 string "false" or "true".
            'allow-headers'     => config('Cors')->allowedHeaders, // ex: Content-Type, Accept, X-Requested-With
            'expose-headers'    => config('Cors')->exposedHeaders,
            'origins'           => config('Cors')->allowedOrigins, // ex: http://localhost
            'methods'           => config('Cors')->allowedMethods, // ex: GET, POST, PUT, PATCH, DELETE
            'max-age'           => config('Cors')->maxAge,
        ];
    }
}