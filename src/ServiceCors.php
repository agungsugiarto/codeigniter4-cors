<?php

namespace Fluent\Cors;

use Fluent\Cors\Config\Cors;
use CodeIgniter\Config\Services;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ServiceCors
{
    /** @var string[] */
    private array $allowedOrigins = [];

    /** @var string[] */
    private array $allowedOriginsPatterns = [];

    /** @var string[] */
    private array $allowedMethods = [];

    /** @var string[] */
    private array $allowedHeaders = [];

    /** @var string[] */
    private array $exposedHeaders = [];

    private bool $supportsCredentials = false;

    private ?int $maxAge = 0;

    private bool $allowAllOrigins = false;

    private bool $allowAllMethods = false;

    private bool $allowAllHeaders = false;

    public function __construct(?Cors $config = null)
    {
        if ($config) {
            $this->setOptions($config);
        }
    }

    /**
     * @param array|CorsInputOptions $options
     */
    public function setOptions(Cors $config): void
    {
        $this->allowedOrigins         = $config->allowedOrigins;
        $this->allowedOriginsPatterns = $config->allowedOriginsPatterns;
        $this->allowedMethods         = $config->allowedMethods;
        $this->allowedHeaders         = $config->allowedHeaders;
        $this->supportsCredentials    = $config->supportsCredentials;
        $this->maxAge                 = $config->maxAge;
        $this->exposedHeaders         = $config->exposedHeaders;

        $this->normalizeOptions();
    }

    private function normalizeOptions(): void
    {
        // Normalize case
        $this->allowedHeaders = array_map('strtolower', $this->allowedHeaders);
        $this->allowedMethods = array_map('strtoupper', $this->allowedMethods);

        // Normalize ['*'] to true
        $this->allowAllOrigins = in_array('*', $this->allowedOrigins);
        $this->allowAllHeaders = in_array('*', $this->allowedHeaders);
        $this->allowAllMethods = in_array('*', $this->allowedMethods);

        // Transform wildcard pattern
        if (! $this->allowAllOrigins) {
            foreach ($this->allowedOrigins as $origin) {
                if (strpos($origin, '*') !== false) {
                    $this->allowedOriginsPatterns[] = $this->convertWildcardToPattern($origin);
                }
            }
        }
    }

    /**
     * Create a pattern for a wildcard, based on Str::is() from Laravel.
     *
     * @see https://github.com/laravel/framework/blob/5.5/src/Illuminate/Support/Str.php
     *
     * @param string $pattern
     *
     * @return string
     */
    private function convertWildcardToPattern($pattern)
    {
        $pattern = preg_quote($pattern, '#');

        // Asterisks are translated into zero-or-more regular expression wildcards
        // to make it convenient to check if the strings starts with the given
        // pattern such as "*.example.com", making any string check convenient.
        $pattern = str_replace('\*', '.*', $pattern);

        return '#^'.$pattern.'\z#u';
    }

    public function isCorsRequest(RequestInterface $request): bool
    {
        return $request->hasHeader('Origin');
    }

    public function isPreflightRequest(RequestInterface $request): bool
    {
        return $request->getMethod(true) === 'OPTIONS' && $request->hasHeader('Access-Control-Request-Method');
    }

    public function handlePreflightRequest(RequestInterface $request): ResponseInterface
    {
        $response = Services::response()->setStatusCode(204);
        $response = $this->addPreflightRequestHeaders($response, $request);

        return $response;
    }

    public function addPreflightRequestHeaders(ResponseInterface $response, RequestInterface $request): ResponseInterface
    {
        $response = $this->configureAllowedOrigin($response, $request);

        if ($response->hasHeader('Access-Control-Allow-Origin')) {
            $response = $this->configureAllowCredentials($response, $request);
            $response = $this->configureAllowedMethods($response, $request);
            $response = $this->configureAllowedHeaders($response, $request);
            $response = $this->configureMaxAge($response, $request);
        }

        return $response;
    }

    public function isOriginAllowed(RequestInterface $request): bool
    {
        if ($this->allowAllOrigins === true) {
            return true;
        }

        $origin = (string) $request->getHeaderLine('Origin');

        if (in_array($origin, $this->allowedOrigins)) {
            return true;
        }

        foreach ($this->allowedOriginsPatterns as $pattern) {
            if (preg_match($pattern, $origin)) {
                return true;
            }
        }

        return false;
    }

    public function addActualRequestHeaders(ResponseInterface $response, RequestInterface $request): ResponseInterface
    {
        $response = $this->configureAllowedOrigin($response, $request);

        if ($response->hasHeader('Access-Control-Allow-Origin')) {
            $response = $this->configureAllowCredentials($response, $request);
            $response = $this->configureExposedHeaders($response, $request);
        }

        return $response;
    }

    private function configureAllowedOrigin(ResponseInterface $response, RequestInterface $request): ResponseInterface
    {
        if ($this->allowAllOrigins === true && ! $this->supportsCredentials) {
            // Safe+cacheable, allow everything
            $response = $response->setHeader('Access-Control-Allow-Origin', '*');
        } elseif ($this->isSingleOriginAllowed()) {
            // Single origins can be safely set
            $response = $response->setHeader('Access-Control-Allow-Origin', array_values($this->allowedOrigins)[0]);
        } else {
            // For dynamic headers, set the requested Origin header when set and allowed
            if ($this->isCorsRequest($request) && $this->isOriginAllowed($request)) {
                $response = $response->setHeader('Access-Control-Allow-Origin', (string) $request->getHeaderLine('Origin'));
            }

            $response = $this->varyHeader($response, 'Origin');
        }

        return $response;
    }

    private function isSingleOriginAllowed(): bool
    {
        if ($this->allowAllOrigins === true || count($this->allowedOriginsPatterns) > 0) {
            return false;
        }

        return count($this->allowedOrigins) === 1;
    }

    private function configureAllowedMethods(ResponseInterface $response, RequestInterface $request): ResponseInterface
    {
        if ($this->allowAllMethods === true) {
            $allowMethods = strtoupper((string) $request->getHeaderLine('Access-Control-Request-Method'));
            $response = $this->varyHeader($response, 'Access-Control-Request-Method');
        } else {
            $allowMethods = implode(', ', $this->allowedMethods);
        }

        $response = $response->setHeader('Access-Control-Allow-Methods', $allowMethods);

        return $response;
    }

    private function configureAllowedHeaders(ResponseInterface $response, RequestInterface $request): ResponseInterface
    {
        if ($this->allowAllHeaders === true) {
            $allowHeaders = (string) $request->getHeaderLine('Access-Control-Request-Headers');
            $response = $this->varyHeader($response, 'Access-Control-Request-Headers');
        } else {
            $allowHeaders = implode(', ', $this->allowedHeaders);
        }
        $response = $response->setHeader('Access-Control-Allow-Headers', $allowHeaders);

        return $response;
    }

    private function configureAllowCredentials(ResponseInterface $response, RequestInterface $request): ResponseInterface
    {
        if ($this->supportsCredentials) {
            $response = $response->setHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    private function configureExposedHeaders(ResponseInterface $response, RequestInterface $request): ResponseInterface
    {
        if ($this->exposedHeaders) {
            $response = $response->setHeader('Access-Control-Expose-Headers', implode(', ', $this->exposedHeaders));
        }

        return $response;
    }

    private function configureMaxAge(ResponseInterface $response, RequestInterface $request): ResponseInterface
    {
        if ($this->maxAge !== null) {
            $response = $response->setHeader('Access-Control-Max-Age', (string) $this->maxAge);
        }

        return $response;
    }

    public function varyHeader(ResponseInterface $response, string $header): ResponseInterface
    {
        if (! $response->hasHeader('Vary')) {
            $response = $response->setHeader('Vary', $header);
        } elseif (! in_array($header, explode(', ', (string) $response->getHeaderLine('Vary')))) {
            $response = $response->setHeader('Vary', ((string) $response->getHeaderLine('Vary')).', '.$header);
        }

        return $response;
    }
}
