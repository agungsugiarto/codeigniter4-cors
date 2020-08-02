<?php

namespace Fluent\Cors;

use CodeIgniter\Config\Services;
use CodeIgniter\HTTP\Request;
use CodeIgniter\HTTP\Response;

class ServiceCors
{
    private $options;

    public function __construct(array $options = [])
    {
        $this->options = $this->normalizeOptions($options);
    }

    public function isCorsRequest(Request $request): bool
    {
        return $request->hasHeader('Origin') && !$this->isSameHost($request);
    }

    public function isPreflightRequest(Request $request): bool
    {
        return $request->getMethod() === 'OPTIONS' && $request->hasHeader('Access-Control-Request-Method');
    }

    public function handlePreflightRequest(Request $request): Response
    {
        $response = Services::response();

        $response->setStatusCode(204);

        return $this->addPreflightRequestHeaders($response, $request);
    }

    public function addPreflightRequestHeaders(Response $response, Request $request): Response
    {
        $this->configureAllowedOrigin($response, $request);
        
        if ($response->hasHeader('Access-Control-Allow-Origin')) {
            $this->configureAllowCredentials($response, $request);

            $this->configureAllowedMethods($response, $request);

            $this->configureAllowedHeaders($response, $request);

            $this->configureMaxAge($response, $request);
        }

        return $response;
    }

    public function isOriginAllowed(Request $request): bool
    {
        if ($this->options['allowedOrigins'] === true) {
            return true;
        }

        if (!$request->hasHeader('Origin')) {
            return false;
        }

        $origin = $request->getHeader('Origin');

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

    public function addActualRequestHeaders(Response $response, Request $request): Response
    {
        $this->configureAllowedOrigin($response, $request);

        if ($response->hasHeader('Access-Control-Allow-Origin')) {
            $this->configureAllowCredentials($response, $request);

            $this->configureExposedHeaders($response, $request);
        }

        return $response;
    }

    public function varyHeader(Response $response, $header): Response
    {
        if (!$response->hasHeader('Vary')) {
            $response->setHeader('Vary', $header);
        } elseif (!in_array($header, explode(', ', $response->getHeader('Vary')))) {
            $response->setHeader('Vary', $response->getHeader('Vary') . ', ' . $header);
        }

        return $response;
    }

    private function normalizeOptions(array $options = []): array
    {
        $options += [
            'allowedOrigins' => [],
            'allowedOriginsPatterns' => [],
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
                $response->setHeader('Access-Control-Allow-Origin', $request->getHeader('Origin'));
            }

            $this->varyHeader($response, 'Origin');
        }
    }

    private function isSingleOriginAllowed(): bool
    {
        if ($this->options['allowedOrigins'] === true || !empty($this->options['allowedOriginsPatterns'])) {
            return false;
        }

        return count($this->options['allowedOrigins']) === 1;
    }

    private function configureAllowedMethods(Response $response, Request $request)
    {
        if ($this->options['allowedMethods'] === true) {
            $allowMethods = strtoupper($request->getHeader('Access-Control-Request-Method'));
            $this->varyHeader($response, 'Access-Control-Request-Method');
        } else {
            $allowMethods = implode(', ', $this->options['allowedMethods']);
        }

        $response->setHeader('Access-Control-Allow-Methods', $allowMethods);
    }

    private function configureAllowedHeaders(Response $response, Request $request)
    {
        if ($this->options['allowedHeaders'] === true) {
            $allowHeaders = $request->getHeader('Access-Control-Request-Headers');
            $this->varyHeader($response, 'Access-Control-Request-Headers');
        } else {
            $allowHeaders = implode(', ', $this->options['allowedHeaders']);
        }
        $response->setHeader('Access-Control-Allow-Headers', $allowHeaders);
    }

    private function configureAllowCredentials(Response $response, Request $request)
    {
        if ($this->options['supportsCredentials']) {
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        }
    }

    private function configureExposedHeaders(Response $response, Request $request)
    {
        if ($this->options['exposedHeaders']) {
            $response->setHeader('Access-Control-Expose-Headers', implode(', ', $this->options['exposedHeaders']));
        }
    }

    private function configureMaxAge(Response $response, Request $request)
    {
        if ($this->options['maxAge'] !== null) {
            $response->setHeader('Access-Control-Max-Age', (int) $this->options['maxAge']);
        }
    }

    private function isSameHost(Request $request): bool
    {
        return $request->getHeader('Origin') === config('App')->baseURL;
    }
}
