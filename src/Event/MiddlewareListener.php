<?php
declare(strict_types=1);

namespace CakeHtmx\Event;

use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use CakeHtmx\Middleware\HtmxRequestMiddleware;

/**
 * Register the middlewares of this plugin
 */
class MiddlewareListener implements EventListenerInterface
{
    /**
     * Implemented Events
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'Server.buildMiddleware' => [
                'callable' => 'buildMiddleware',
                'priority' => 50,
            ],
        ];
    }

    /**
     * Register middleware
     *
     * @param \Cake\Event\Event $event The buildMiddleware event that was fired
     * @param @param \Cake\Http\MiddlewareQueue|null $middlewareQueue MiddlewareQueue or null.
     * @return void
     */
    public function buildMiddleware(Event $event, $middlewareQueue): void
    {
        $middlewareQueue->add(new HtmxRequestMiddleware());
    }
}
