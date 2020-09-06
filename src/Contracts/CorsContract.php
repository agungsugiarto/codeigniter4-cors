<?php

namespace Fluent\Cors\Contracts;

use CodeIgniter\HTTP\Request;
use CodeIgniter\HTTP\Response;

interface CorsContract
{
    /**
     * Handles a preflight request.
     *
     * @param \CodeIgniter\HTTP\Request   $request
     * @return \CodeIgniter\HTTP\Response
     */
    public function handlePreflightRequest(Request $request): Response;

    /**
     * Handle the actual request.
     *
     * @param \CodeIgniter\HTTP\Request  $request
     * @param \CodeIgniter\HTTP\Response $response
     * @return \CodeIgniter\HTTP\Response
     */
    public function handleRequest(Request $request, Response $response): Response;

    /**
     * Returns wheter or not the request is a CORS request.
     *
     * @param \CodeIgniter\HTTP\Request  $request
     * @return bool
     */
    public function isCorsRequest(Request $request): bool;

    /**
     * Returns wheter or not the request is a preflight request.
     *
     * @param @param \CodeIgniter\HTTP\Request  $request
     * @return bool
     */
    public function isPreflightRequest(Request $request): bool;

    /**
     * Vary headers options.
     *
     * @param \CodeIgniter\HTTP\Response $response
     * @param string                     $header
     * @return \CodeIgniter\HTTP\Response
     */
    public function varyHeader(Response $response, string $header): Response;
}
