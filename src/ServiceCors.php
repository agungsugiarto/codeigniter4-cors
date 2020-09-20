<?php

namespace Fluent\Cors;

use CodeIgniter\Config\Config;
use CodeIgniter\HTTP\Request;
use CodeIgniter\HTTP\Response;
use Fluent\Cors\Contracts\CorsContract;

class ServiceCors implements CorsContract
{
    /** @var array $options */
    private $options;

    /**
     * Constructor Service Cors.
     *
     * @param array $options
     * @return void
     */
    public function __construct(array $options = [])
    {
        $this->options = $this->normalizeOptions($options);
    }

    /**
     * @inheritdoc
     */
    public function isCorsRequest(Request $request): bool
    {
        return $request->hasHeader('Origin') && !$this->isSameHost($request);
    }

    /**
     * @inheritdoc
     */
    public function isPreflightRequest(Request $request): bool
    {
        return $request->getMethod(true) === 'OPTIONS' && $request->hasHeader('Access-Control-Request-Method');
    }

    /**
     * @inheritdoc
     */
    public function handlePreflightRequest(Request $request): Response
    {
        $response = new Response(Config::get('App'));

        $this->configureAllowedOrigin($response, $request);
        
        if ($response->hasHeader('Access-Control-Allow-Origin')) {
            $this->configureAllowCredentials($response, $request);

            $this->configureAllowedMethods($response, $request);

            $this->configureAllowedHeaders($response, $request);

            $this->configureMaxAge($response);
        }

        return $response->setStatusCode(204);
    }

    /**
     * @inheritdoc
     */
    public function handleRequest(Request $request, Response $response): Response
    {
        $this->configureAllowedOrigin($response, $request);

        if ($response->hasHeader('Access-Control-Allow-Origin')) {
            $this->configureAllowCredentials($response, $request);

            $this->configureExposedHeaders($response);
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function varyHeader(Response $response, string $header): Response
    {
        if (! $response->hasHeader('Vary')) {
            $response->setHeader('Vary', $header);
        } elseif (! in_array($header, explode(', ', $response->getHeaderLine('Vary')))) {
            $response->setHeader('Vary', $response->getHeaderLine('Vary') . ', ' . $header);
        }

        return $response;
    }

    /**
     * Normalize options config.
     *
     * @param array $options
     * @return array
     */
    private function normalizeOptions(array $options = []): array
    {
        $options += [
            'allowedOrigins' => [],
            'supportsCredentials' => false,
            'allowedHeaders' => [],
            'exposedHeaders' => [],
            'allowedMethods' => [],
            'maxAge' => 0,
        ];

        // normalize array('*') to true
        if (in_array('*', $options['allowedOrigins'])) {
            $options['allowedOrigins'] = true;
        }
        if (in_array('*', $options['allowedHeaders'])) {
            $options['allowedHeaders'] = true;
        } else {
            $options['allowedHeaders'] = array_map('strtolower', $options['allowedHeaders']);
        }

        if (in_array('*', $options['allowedMethods'])) {
            $options['allowedMethods'] = true;
        } else {
            $options['allowedMethods'] = array_map('strtoupper', $options['allowedMethods']);
        }

        return $options;
    }

    /**
     * Is origin allowed.
     *
     * @param \CodeIgniter\HTTP\Request $request
     * @return bool
     */
    private function isOriginAllowed(Request $request): bool
    {
        if ($this->options['allowedOrigins'] === true) {
            return true;
        }

        if (! $request->hasHeader('Origin')) {
            return false;
        }

        $origin = $request->getHeaderLine('Origin');

        if (in_array($origin, $this->options['allowedOrigins'])) {
            return true;
        }

        return false;
    }

    /**
     * Configure allow origin.
     *
     * @param \CodeIgniter\HTTP\Response $response
     * @param \CodeIgniter\HTTP\Request  $request
     * @return void
     */
    private function configureAllowedOrigin(Response $response, Request $request)
    {
        if ($this->options['allowedOrigins'] === true && !$this->options['supportsCredentials']) {
            // Safe+cacheable, allow everything
            $response->setHeader('Access-Control-Allow-Origin', '*');
        } elseif ($this->isSingleOriginAllowed()) {
            // Single origins can be safely set
            $response->setHeader('Access-Control-Allow-Origin', array_values($this->options['allowedOrigins'])[0]);
        } else {
            // For dynamic headers, check the origin first
            if ($this->isOriginAllowed($request)) {
                $response->setHeader('Access-Control-Allow-Origin', $request->getHeaderLine('Origin'));
            }

            $this->varyHeader($response, 'Origin');
        }
    }

    /**
     * Is the singgle origin allowed.
     *
     * @return void
     */
    private function isSingleOriginAllowed(): bool
    {
        if ($this->options['allowedOrigins'] === true) {
            return false;
        }

        return count($this->options['allowedOrigins']) === 1;
    }

    /**
     * Configure allow methods.
     *
     * @param \CodeIgniter\HTTP\Response $response
     * @param \CodeIgniter\HTTP\Request  $request
     * @return void
     */
    private function configureAllowedMethods(Response $response, Request $request)
    {
        if ($this->options['allowedMethods'] === true) {
            $allowMethods = strtoupper($request->getHeaderLine('Access-Control-Request-Method'));
            $this->varyHeader($response, 'Access-Control-Request-Method');
        } else {
            $allowMethods = implode(', ', $this->options['allowedMethods']);
        }

        $response->setHeader('Access-Control-Allow-Methods', $allowMethods);
    }

    /**
     * Configure allow headers.
     *
     * @param \CodeIgniter\HTTP\Response $response
     * @param \CodeIgniter\HTTP\Request  $request
     * @return void
     */
    private function configureAllowedHeaders(Response $response, Request $request)
    {
        if ($this->options['allowedHeaders'] === true) {
            $allowHeaders = $request->getHeaderLine('Access-Control-Request-Headers');
            $this->varyHeader($response, 'Access-Control-Request-Headers');
        } else {
            $allowHeaders = implode(', ', $this->options['allowedHeaders']);
        }

        $response->setHeader('Access-Control-Allow-Headers', $allowHeaders);
    }

    /**
     * Configure allow credentials.
     *
     * @param \CodeIgniter\HTTP\Request $request
     * @return void
     */
    private function configureAllowCredentials(Response $response)
    {
        if ($this->options['supportsCredentials']) {
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        }
    }

    /**
     * Configure expose headers.
     *
     * @param \CodeIgniter\HTTP\Request $request
     * @return void
     */
    private function configureExposedHeaders(Response $response)
    {
        if ($this->options['exposedHeaders']) {
            $response->setHeader('Access-Control-Expose-Headers', implode(', ', $this->options['exposedHeaders']));
        }
    }

    /**
     * Configure max age.
     *
     * @param \CodeIgniter\HTTP\Request $request
     * @return void
     */
    private function configureMaxAge(Response $response)
    {
        if ($this->options['maxAge'] !== null) {
            $response->setHeader('Access-Control-Max-Age', (string) $this->options['maxAge']);
        }
    }

    /**
     * Cek is same host.
     *
     * @param \CodeIgniter\HTTP\Request $request
     * @return bool
     */
    private function isSameHost(Request $request): bool
    {
        return $request->getHeaderLine('Origin') === config('App')->baseURL;
    }
}
