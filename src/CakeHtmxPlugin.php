<?php
declare(strict_types=1);

namespace CakeHtmx;

use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventManager;
use CakeHtmx\Event\MiddlewareListener;

/**
 * Plugin for CakeHtmx
 */
class CakeHtmxPlugin extends BasePlugin
{
    /**
     * Load all the plugin configuration and bootstrap logic.
     *
     * The host application is provided as an argument. This allows you to load
     * additional plugin dependencies, or attach events.
     *
     * @param \Cake\Core\PluginApplicationInterface $app The host application
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        EventManager::instance()->on(new MiddlewareListener());
    }
}
