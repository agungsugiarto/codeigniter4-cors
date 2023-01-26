<?php

namespace Fluent\Cors\Tests;

use CodeIgniter\Config\Factories;
use CodeIgniter\HTTP\Request;
use CodeIgniter\HTTP\Response;
use CodeIgniter\Test\CIUnitTestCase;
use Fluent\Cors\ServiceCors;

class CorsServiceTest extends CIUnitTestCase
{
    /** @var Request */
    protected function request()
    {
        return new Request(Factories::config('App'));
    }

    /** @var Response */
    protected function response()
    {
        return new Response(Factories::config('App'));
    }

    public function testIsCorsRequest()
    {
        $request = $this->request()->setHeader('Origin', 'http://foo-bar.test');

        $cors = new ServiceCors(config('Cors'));

        $this->assertTrue($cors->isCorsRequest($request));
    }

    public function testIsNotCorsRequest()
    {
        $request = $this->request()->setHeader('Foo', 'https://foo.test');

        $cors = new ServiceCors(config('Cors'));

        $this->assertFalse($cors->isCorsRequest($request));
    }

    public function testIsPreflightRequest()
    {
        $request = $this->request()
            ->withMethod('OPTIONS')
            ->setHeader('Access-Control-Request-Method', 'GET');

        $cors = new ServiceCors(config('Cors'));

        $this->assertTrue($cors->isPreflightRequest($request));
    }

    public function testIsNotPreflightRequest()
    {
        $request = $this->request()->withMethod('GET')
            ->setHeader('Access-Control-Request-Method', 'GET');

        $cors = new ServiceCors(config('Cors'));

        $this->assertFalse($cors->isPreflightRequest($request));
    }

    public function testVaryHeader()
    {
        $response = $this->response()
            ->setHeader('Vary', 'Access-Control-Request-Method');

        $cors = new ServiceCors(config('Cors'));

        $vary = $cors->varyHeader($response, 'Access-Control-Request-Method');

        $this->assertEquals($response->getHeaderLine('Vary'), $vary->getHeaderLine('Vary'));
    }
    
    public function testHandlePreflightRequest()
    {
        $request = $this->request()
            ->withMethod('OPTIONS')
            ->setHeader('Origin', 'http://foobar.com')
            ->setHeader('Access-Control-Request-Method', 'GET')
            ->setHeader('Access-Control-Request-Headers', 'X-CSRF-TOKEN');
            
        $cors = new ServiceCors(config('Cors'));

        $expected = $cors->handlePreflightRequest($request);

        $this->assertEmpty($expected->getHeaderLine('Access-Control-Allow-Credentials'));
        $this->assertEmpty($expected->getHeaderLine('Access-Control-Expose-Headers'));
        $this->assertEquals('GET', $expected->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertEquals('Vary', $expected->header('Vary')->getName());
        $this->assertStringContainsString(
            "Access-Control-Request-Method, Access-Control-Request-Headers",
            $expected->getHeaderLine('Vary')
        );
        $this->assertEquals('X-CSRF-TOKEN', $expected->getHeaderLine('Access-Control-Allow-Headers'));
        $this->assertEquals(0, $expected->getHeaderLine('Access-Control-Max-Age'));
        $this->assertEquals(204, $expected->getStatusCode());
    }

    public function testHandleRequest()
    {
        $request = $this->request()
            ->withMethod('GET')
            ->setHeader('Origin', 'http://foo.test');

        $response = $this->response()
            ->setHeader('Access-Control-Allow-Origin', $request->getHeaderLine('Origin'));

        $cors = new ServiceCors(config('Cors'));

        $expected = $cors->addPreflightRequestHeaders($response, $request);

        $this->assertEquals('*', $expected->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertEquals('Access-Control-Allow-Origin', $expected->header('Access-Control-Allow-Origin')->getName());
    }

    public function testHandlePreflightRequestWithRestricAllowedHeaders()
    {
        $request = $this->request()
            ->withMethod('OPTIONS')
            ->setHeader('Origin', 'http://foobar.com')
            ->setHeader('Access-Control-Request-Method', 'GET')
            ->setHeader('Access-Control-Request-Headers', 'X-CSRF-TOKEN');

        $config = config('Cors');
        $config->allowedHeaders = ['SAMPLE-RESTRICT-HEADER'];

        $cors = new ServiceCors($config);

        $expected = $cors->handlePreflightRequest($request);

        $this->assertNotEquals(
            $request->getHeaderLine('Access-Control-Request-Headers'),
            $expected->getHeaderLine('Access-Control-Allow-Headers')
        );
    }

    public function testHandlePreflightRequestWithSameRestricAllowedHeaders()
    {
        $request = $this->request()
            ->withMethod('OPTIONS')
            ->setHeader('Origin', 'http://foobar.com')
            ->setHeader('Access-Control-Request-Method', 'GET')
            ->setHeader('Access-Control-Request-Headers', 'X-CSRF-TOKEN');

        $config = config('Cors');
        $config->allowedHeaders = ['X-CSRF-TOKEN'];

        $cors = new ServiceCors($config);

        $expected = $cors->handlePreflightRequest($request);

        $this->assertEquals(
            $request->getHeaderLine('Access-Control-Request-Headers'),
            strtoupper($expected->getHeaderLine('Access-Control-Allow-Headers'))
        );
    }

    public function testHandlePreflightRequestWithRestrictAllowedOrigins()
    {
        $request = $this->request()
            ->withMethod('OPTIONS')
            ->setHeader('Origin', 'http://foobar.com')
            ->setHeader('Access-Control-Request-Method', 'GET')
            ->setHeader('Access-Control-Request-Headers', 'X-CSRF-TOKEN');

        $config = config('Cors');
        $config->allowedOrigins = ['http://foo.com'];

        $cors = new ServiceCors($config);

        $expected = $cors->handlePreflightRequest($request);

        $this->assertNotEquals(
            $request->getHeaderLine('Origin'),
            $expected->getHeaderLine('Access-Control-Allow-Origin')
        );
    }

    public function testHandlePreflightRequestWithSameRestrictAllowedOrigins()
    {
        $request = $this->request()
            ->withMethod('OPTIONS')
            ->setHeader('Origin', 'http://foo.com')
            ->setHeader('Access-Control-Request-Method', 'GET')
            ->setHeader('Access-Control-Request-Headers', 'X-CSRF-TOKEN');

        $config = config('Cors');
        $config->allowedOrigins = ['http://foo.com'];

        $cors = new ServiceCors($config);

        $expected = $cors->handlePreflightRequest($request);

        $this->assertEquals(
            $request->getHeaderLine('Origin'),
            $expected->getHeaderLine('Access-Control-Allow-Origin')
        );
    }

    public function testHandlePreflightRequestWithExposeHeaders()
    {
        $request = $this->request()
            ->withMethod('GET')
            ->setHeader('Origin', 'http://foo.com')
            ->setHeader('Access-Control-Request-Headers', 'X-CSRF-TOKEN');

        $config = config('Cors');
        $config->exposedHeaders = ['X-My-Custom-Header', 'X-Another-Custom-Header'];

        $cors = new ServiceCors($config);

        $expeted = $cors->addActualRequestHeaders($this->response(), $request);

        $this->assertEquals(
            "X-My-Custom-Header, X-Another-Custom-Header",
            $expeted->getHeaderLine('Access-Control-Expose-Headers')
        );
    }

    public function testHandlePreflightRequestWithExposeHeadersNotSet()
    {
        $request = $this->request()
            ->withMethod('GET')
            ->setHeader('Origin', 'http://foo.com')
            ->setHeader('Access-Control-Request-Headers', 'X-CSRF-TOKEN');

        $cors = new ServiceCors(config('Cors'));

        $expeted = $cors->addPreflightRequestHeaders($this->response(), $request);

        $this->assertEmpty(
            $expeted->getHeaderLine('Access-Control-Expose-Headers')
        );
    }
}
