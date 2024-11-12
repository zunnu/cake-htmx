<?php

declare(strict_types=1);

namespace CakeHtmx\Middleware;

use Cake\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * HtmxRequest middleware
 */
class HtmxRequestMiddleware implements MiddlewareInterface
{
    /**
     * Process method.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        ServerRequest::addDetector(
            'htmx',
            function ($request) {
                return filter_var($request->getHeaderLine('HX-Request'), FILTER_VALIDATE_BOOLEAN);
            }
        );

        ServerRequest::addDetector(
            'boosted',
            function ($request) {
                return filter_var($request->getHeaderLine('HX-Boosted'), FILTER_VALIDATE_BOOLEAN);
            }
        );

        ServerRequest::addDetector(
            'historyRestoreRequest',
            function ($request) {
                return filter_var($request->getHeaderLine('HX-History-Restore-Request'), FILTER_VALIDATE_BOOLEAN);
            }
        );

        return $handler->handle($request);
    }
}
