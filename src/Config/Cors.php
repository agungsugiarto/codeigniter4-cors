<?php

namespace Fluent\Cors\Config;

use CodeIgniter\Config\BaseConfig;

class Cors extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Cross-Origin Resource Sharing (CORS) Configuration
     * --------------------------------------------------------------------------
     *
     * Here you may configure your settings for cross-origin resource sharing
     * or "CORS". This determines what cross-origin operations may execute
     * in web browsers. You are free to adjust these settings as needed.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
     *
     */

    /**
     * --------------------------------------------------------------------------
     * Allowed HTTP headers
     * --------------------------------------------------------------------------
     *
     * Indicates which HTTP headers are allowed.
     *
     * @var array
     */
    public $allowedHeaders = ['*'];

    /**
     * --------------------------------------------------------------------------
     * Allowed HTTP methods
     * --------------------------------------------------------------------------
     *
     * Indicates which HTTP methods are allowed.
     *
     * @var array
     */
    public $allowedMethods = ['*'];

    /**
     * --------------------------------------------------------------------------
     * Allowed request origins
     * --------------------------------------------------------------------------
     *
     * Indicates which origins are allowed to perform requests.
     * Patterns also accepted, for example *.foo.com
     *
     * @var array
     */
    public $allowedOrigins = ['*'];

    /**
     * --------------------------------------------------------------------------
     * Allowed origins patterns
     * --------------------------------------------------------------------------
     *
     * Patterns that can be used with `preg_match` to match the origin.
     *
     * @var array
     */
    public $allowedOriginsPatterns = [];

    /**
     * --------------------------------------------------------------------------
     * Exposed headers
     * --------------------------------------------------------------------------
     *
     * Headers that are allowed to be exposed to the web server.
     *
     * @var array
     */
    public $exposedHeaders = [];

    /**
     * --------------------------------------------------------------------------
     * Max age
     * --------------------------------------------------------------------------
     *
     * Indicates how long the results of a preflight request can be cached.
     *
     * @var int
     */
    public $maxAge = 0;

    /**
     * --------------------------------------------------------------------------
     * Whether or not the response can be exposed when credentials are present
     * --------------------------------------------------------------------------
     *
     * Indicates whether or not the response to the request can be exposed when the
     * credentials flag is true. When used as part of a response to a preflight
     * request, this indicates whether or not the actual request can be made
     * using credentials.  Note that simple GET requests are not preflighted,
     * and so if a request is made for a resource with credentials, if
     * this header is not returned with the resource, the response
     * is ignored by the browser and not returned to web content.
     *
     * @var boolean
     */
    public $supportsCredentials = false;
}
