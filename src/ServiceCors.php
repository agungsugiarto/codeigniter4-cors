<?php

namespace Fluent\Cors;

use CodeIgniter\Config\Factories;
use CodeIgniter\HTTP\Request;
use CodeIgniter\HTTP\Response;

class ServiceCors
{
    /** @var array */
    protected $options;

    public function __construct(array $options = [])
    {
        $this->options = $this->normalizeOptions($options);
    }

    protected function normalizeOptions(array $options = []): array
    {
        $options = array_merge([
            'allowedOrigins' => [],
            'allowedOriginsPatterns' => [],
            'supportsCredentials' => false,
            'allowedHeaders' => [],
            'exposedHeaders' => [],
            'allowedMethods' => [],
            'maxAge' => 0,
        ], $options);

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
     * {@inheritdoc}
     */
    public function isCorsRequest(Request $request): bool
    {
        return $request->hasHeader('Origin') && !$this->isSameHost($request);
    }

    /**
     * {@inheritdoc}
     */
    public function isPreflightRequest(Request $request): bool
    {
        return $request->getMethod() === 'options' && $request->hasHeader('Access-Control-Request-Method');
    }

    /**
     * {@inheritdoc}
     */
    public function handlePreflightRequest(Request $request): Response
    {
        $response = new Response(Factories::config('App'));

        $response->setStatusCode(204);

        return $this->handleRequest($response, $request);
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(Response $response, Request $request): Response
    {
        $this->configureAllowedOrigin($response, $request);

        if ($response->hasHeader('Access-Control-Allow-Origin')) {
            $this->configureAllowCredentials($response);

            $this->configureAllowedMethods($response, $request);

            $this->configureAllowedHeaders($response, $request);

            $this->configureMaxAge($response);
        }

        return $response;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isOriginAllowed(Request $request): bool
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

        foreach ($this->options['allowedOriginsPatterns'] as $pattern) {
            if (preg_match($pattern, $origin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function addActualRequestHeaders(Response $response, Request $request): Response
    {
        $this->configureAllowedOrigin($response, $request);

        if ($response->hasHeader('Access-Control-Allow-Origin')) {
            $this->configureAllowCredentials($response);

            $this->configureExposedHeaders($response);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function varyHeader(Response $response, $header): Response
    {
        if (! $response->hasHeader('Vary')) {
            $response->setHeader('Vary', $header);
        } elseif (! in_array($header, explode(', ', $response->getHeaderLine('Vary')))) {
            $response->setHeader('Vary', $response->getHeaderLine('Vary') . ', ' . $header);
        }

        return $response;
    }

    protected function configureAllowedOrigin(Response $response, Request $request)
    {
        if ($this->options['allowedOrigins'] === true && ! $this->options['supportsCredentials']) {
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

    protected function isSingleOriginAllowed(): bool
    {
        if ($this->options['allowedOrigins'] === true || ! empty($this->options['allowedOriginsPatterns'])) {
            return false;
        }

        return count($this->options['allowedOrigins']) === 1;
    }

    protected function configureAllowedMethods(Response $response, Request $request)
    {
        if ($this->options['allowedMethods'] === true) {
            $allowMethods = strtoupper($request->getHeaderLine('Access-Control-Request-Method'));
            $this->varyHeader($response, 'Access-Control-Request-Method');
        } else {
            $allowMethods = implode(', ', $this->options['allowedMethods']);
        }

        $response->setHeader('Access-Control-Allow-Methods', $allowMethods);
    }

    protected function configureAllowedHeaders(Response $response, Request $request)
    {
        if ($this->options['allowedHeaders'] === true) {
            $allowHeaders = $request->getHeaderLine('Access-Control-Request-Headers');
            $this->varyHeader($response, 'Access-Control-Request-Headers');
        } else {
            $allowHeaders = implode(', ', $this->options['allowedHeaders']);
        }

        $response->setHeader('Access-Control-Allow-Headers', $allowHeaders);
    }

    protected function configureAllowCredentials(Response $response)
    {
        if ($this->options['supportsCredentials']) {
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        }
    }

    protected function configureExposedHeaders(Response $response)
    {
        if ($this->options['exposedHeaders']) {
            $response->setHeader('Access-Control-Expose-Headers', implode(', ', $this->options['exposedHeaders']));
        }
    }

    protected function configureMaxAge(Response $response)
    {
        if ($this->options['maxAge'] !== null) {
            $response->setHeader('Access-Control-Max-Age', (string) $this->options['maxAge']);
        }
    }

    protected function isSameHost(Request $request): bool
    {
        return $request->getHeaderLine('Origin') === Factories::config('App')->baseURL;
    }
}
