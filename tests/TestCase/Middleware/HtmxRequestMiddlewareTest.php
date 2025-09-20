<?php
declare(strict_types=1);

namespace CakeHtmx\Test\TestCase\Middleware;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use CakeHtmx\Middleware\HtmxRequestMiddleware;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Test case for \CakeHtmx\Middleware\HtmxRequestMiddleware
 */
final class HtmxRequestMiddlewareTest extends TestCase
{
    /**
     * The middleware under test.
     *
     * @var \CakeHtmx\Middleware\HtmxRequestMiddleware
     */
    private HtmxRequestMiddleware $middleware;

    /**
     * Setup method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new HtmxRequestMiddleware();
    }

    /**
     * Test that HX-Request header with "true" sets the `htmx` detector.
     *
     * @return void
     */
    public function testProcessSetsHtmxTrueWhenHeaderTrue(): void
    {
        $request = (new ServerRequest())->withHeader('HX-Request', 'true');

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($req) {
                $this->assertTrue($req->is('htmx'));
                $this->assertFalse($req->is('boosted'));
                $this->assertFalse($req->is('historyRestoreRequest'));
                $this->assertTrue($req->is('htmx-noboost'));

                return new Response();
            });

        $res = $this->middleware->process($request, $handler);
        $this->assertInstanceOf(Response::class, $res);
    }

    /**
     * Test that HX-Boosted header with "true" sets the `boosted` detector,
     * and disables `htmx-noboost`.
     *
     * @return void
     */
    public function testProcessSetsBoostedTrueWhenHeaderTrue(): void
    {
        $request = (new ServerRequest())
            ->withHeader('HX-Request', 'true')
            ->withHeader('HX-Boosted', 'true');

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($req) {
                $this->assertTrue($req->is('htmx'));
                $this->assertTrue($req->is('boosted'));
                $this->assertFalse($req->is('htmx-noboost'));

                return new Response();
            });

        $this->middleware->process($request, $handler);
    }

    /**
     * Test that HX-History-Restore-Request header with "true" sets
     * the `historyRestoreRequest` detector.
     *
     * @return void
     */
    public function testProcessSetsHistoryRestoreRequestTrueWhenHeaderTrue(): void
    {
        $request = (new ServerRequest())
            ->withHeader('HX-Request', 'true')
            ->withHeader('HX-History-Restore-Request', 'true');

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($req) {
                $this->assertTrue($req->is('htmx'));
                $this->assertTrue($req->is('historyRestoreRequest'));
                $this->assertFalse($req->is('boosted'));
                $this->assertTrue($req->is('htmx-noboost'));

                return new Response();
            });

        $this->middleware->process($request, $handler);
    }

    /**
     * Test that "false" values in HX headers evaluate correctly as false.
     *
     * @return void
     */
    public function testProcessFalseValuesAreHandled(): void
    {
        $request = (new ServerRequest())
            ->withHeader('HX-Request', 'false')
            ->withHeader('HX-Boosted', 'false')
            ->withHeader('HX-History-Restore-Request', 'false');

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($req) {
                $this->assertFalse($req->is('htmx'));
                $this->assertFalse($req->is('boosted'));
                $this->assertFalse($req->is('historyRestoreRequest'));
                $this->assertFalse($req->is('htmx-noboost'));

                return new Response();
            });

        $this->middleware->process($request, $handler);
    }

    /**
     * Test that when no HX headers are present, all detectors evaluate to false.
     *
     * @return void
     */
    public function testProcessMissingHeadersDefaultToFalse(): void
    {
        $request = new ServerRequest();

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($req) {
                $this->assertFalse($req->is('htmx'));
                $this->assertFalse($req->is('boosted'));
                $this->assertFalse($req->is('historyRestoreRequest'));
                $this->assertFalse($req->is('htmx-noboost'));

                return new Response();
            });

        $this->middleware->process($request, $handler);
    }
}
