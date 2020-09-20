<?php

namespace Fluent\Cors\Tests;

use CodeIgniter\Config\Config;
use CodeIgniter\HTTP\Request;
use CodeIgniter\HTTP\Response;
use CodeIgniter\Test\CIUnitTestCase;
use Fluent\Cors\ServiceCors;

class CorsServiceTest extends CIUnitTestCase
{
    /** @var \Fluent\Cors\Config\Cors */
    protected $config;

    /**
     * Set request instance.
     *
     * @return \CodeIgniter\HTTP\Request
     */
    protected function setRequest()
    {
        return new Request(Config::get('App'));
    }

    /**
     * Set response instance.
     *
     * @return \CodeIgniter\HTTP\Response
     */
    protected function setResponse()
    {
        return new Response(Config::get('App'));
    }

    public function testIsCorsRequest()
    {
        $request = $this->setRequest()->setHeader('Origin', 'http://foo.bar.com');

        $cors = new ServiceCors([]);

        $this->assertTrue($cors->isCorsRequest($request));
    }

    public function testIsNotCorsRequest()
    {
        $request = $this->setRequest()
            ->setHeader('Foo', 'https://foo.com');

        $cors = new ServiceCors([]);

        $this->assertFalse($cors->isCorsRequest($request));
    }

    public function testIsPreflightRequest()
    {
        $request = $this->setRequest()
            ->setMethod('OPTIONS')
            ->setHeader('Access-Control-Request-Method', 'GET');

        $cors = new ServiceCors([]);

        $this->assertTrue($cors->isPreflightRequest($request));
    }

    public function testIsNotPreflightRequest()
    {
        $request = $this->setRequest()->setMethod('GET')
            ->setHeader('Access-Control-Request-Method', 'GET');

        $cors = new ServiceCors([]);

        $this->assertFalse($cors->isPreflightRequest($request));
    }

    public function testVaryHeader()
    {
        $response = $this->setResponse()
            ->setHeader('Vary', 'Access-Control-Request-Method');

        $cors = new ServiceCors([]);

        $vary = $cors->varyHeader($response, 'Access-Control-Request-Method');

        $this->assertEquals($response->getHeaderLine('Vary'), $vary->getHeaderLine('Vary'));
    }
    
    public function testHandlePreflightRequest()
    {
        $request = $this->setRequest()
            ->setMethod('OPTIONS')
            ->setHeader('Access-Control-Request-Method', 'GET')
            ->setHeader('Access-Control-Request-Headers', 'X-CSRF-TOKEN');
            
        $cors = new ServiceCors([
            'allowedHeaders'      => ['*'],
            'allowedMethods'      => ['*'],
            'allowedOrigins'      => ['*'],
            'exposedHeaders'      => [],
            'maxAge'              => 0,
            'supportsCredentials' => false,
        ]);

        $expected = $cors->handlePreflightRequest($request);

        $this->assertEmpty($expected->getHeaderLine('Access-Control-Allow-Credentials'));
        $this->assertEmpty($expected->getHeaderLine('Access-Control-Expose-Headers'));
        $this->assertEquals('GET', $expected->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertEquals('Vary', $expected->getHeader('Vary')->getName());
        $this->assertEquals('X-CSRF-TOKEN', $expected->getHeaderLine('Access-Control-Allow-Headers'));
        $this->assertEquals(0, $expected->getHeaderLine('Access-Control-Max-Age'));
        $this->assertEquals(204, $expected->getStatusCode());
    }

    public function testHandleRequest()
    {
        $request = $this->setRequest()
            ->setMethod('GET')
            ->setHeader('Origin', 'http://foo.bar.com');

        $response = $this->setResponse()
            ->setHeader('Access-Control-Allow-Origin', $request->getHeaderLine('Origin'));

        $cors = new ServiceCors([
            'allowedHeaders'      => ['*'],
            'allowedMethods'      => ['*'],
            'allowedOrigins'      => ['*'],
            'exposedHeaders'      => [],
            'maxAge'              => 0,
            'supportsCredentials' => false,
        ]);

        $expected = $cors->handleRequest($request, $response);

        $this->assertEquals('*', $expected->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertEquals('Access-Control-Allow-Origin', $expected->getHeader('Access-Control-Allow-Origin')->getName());
    }
}
