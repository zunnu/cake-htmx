<?php
declare(strict_types=1);

namespace CakeHtmx\Middleware;

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
     * @param \Cake\Http\ServerRequest $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request->addDetector(
            'htmx',
            function ($request) {
                return filter_var($request->getHeaderLine('HX-Request'), FILTER_VALIDATE_BOOLEAN);
            },
        );

        $request->addDetector(
            'boosted',
            function ($request) {
                return filter_var($request->getHeaderLine('HX-Boosted'), FILTER_VALIDATE_BOOLEAN);
            },
        );

        $request->addDetector(
            'historyRestoreRequest',
            function ($request) {
                return filter_var($request->getHeaderLine('HX-History-Restore-Request'), FILTER_VALIDATE_BOOLEAN);
            },
        );

        $request->addDetector(
            'htmx-noboost',
            function (ServerRequestInterface $request): bool {
                /** @var \Cake\Http\ServerRequest $request */
                return $request->is('htmx') && !$request->is('boosted');
            },
        );

        return $handler->handle($request);
    }
}
