<?php
declare(strict_types=1);

namespace CakeHtmx\Controller\Component;

use Cake\Controller\Component;
use Cake\Http\Response;

/**
 * Htmx component
 */
class HtmxComponent extends Component
{
    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [];

    /**
     * The name of the block.
     *
     * @var string|null
     */
    protected ?string $block = null;

    /**
     * List of triggers to use on request
     *
     * @var array
     */
    private array $triggers = [];

    /**
     * List of triggers to use on request after settle
     *
     * @var array
     */
    private array $triggersAfterSettle = [];

    /**
     * List of triggers to use on request after swap
     *
     * @var array
     */
    private array $triggersAfterSwap = [];

    /**
     * Get the callbacks this class is interested in.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'View.beforeRender' => 'beforeRender',
            'View.afterRender' => 'afterRender',
        ];
    }

    /**
     * Initialize properties.
     *
     * @param array<string, mixed> $config The config data.
     * @return void
     */
    public function initialize(array $config): void
    {
    }

    /**
     * beforeRender callback.
     *
     * @return void
     */
    public function beforeRender($event): void
    {
        if ($this->getController()->getRequest()->is('htmx')) {
            $this->prepare();
        }
    }

    /**
     * afterRender callback.
     *
     * If setBlock is used this will render the set block if it exists
     *
     * @return void
     */
    public function afterRender($event)
    {
        if(!empty($this->block) && $event->getSubject()->exists($this->block)) {
            $block = $event->getSubject()->fetch($this->block);
            $event->getSubject()->assign('content', $block);
        }
    }

    /**
     * The current URL of the browser when the htmx request was made.
     *
     * @return string|null
     */
    public function getCurrentUrl(): ?string
    {
        return $this->getController()->getRequest()->getHeaderLine('HX-Current-Url');
    }

    /**
     * The user response to an hx-prompt.
     *
     * @return string|null
     */
    public function getPromptResponse(): ?string
    {
        return $this->getController()->getRequest()->getHeaderLine('HX-Prompt');
    }

    /**
     * The id of the target element if it exists.
     *
     * @return string|null
     */
    public function getTarget(): ?string
    {
        return $this->getController()->getRequest()->getHeaderLine('HX-Target');
    }

    /**
     * The name of the triggered element if it exists.
     *
     * @return string|null
     */
    public function getTriggerName(): ?string
    {
        return $this->getController()->getRequest()->getHeaderLine('HX-Trigger-Name');
    }

    /**
     * The id of the triggered element if it exists.
     *
     * @return string|null
     */
    public function getTriggerId(): ?string
    {
        return $this->getController()->getRequest()->getHeaderLine('HX-Trigger');
    }

    /**
     * Do a client-side redirect that does not do a full page reload
     *
     * @param string $url Where to redirect
     * @return \Cake\Http\Response|null
     */
    public function location(string $url): ?Response
    {
        $response = $this->getController()->getResponse()->withHeader('HX-Location', $url);
        $this->getController()->setResponse($response);

        return $this->getController()->getResponse();
    }

    /**
     * Pushes a new url into the history stack
     *
     * @param string $url Url to push
     * @return \Cake\Http\Response|null
     */
    public function pushUrl(string $url): ?Response
    {
        $response = $this->getController()->getResponse()->withHeader('HX-Push-Url', $url);
        $this->getController()->setResponse($response);

        return $this->getController()->getResponse();
    }

    /**
     * Replaces the current URL in the location bar
     *
     * @param string $url Url to replace
     * @return \Cake\Http\Response|null
     */
    public function replaceUrl(string $url): ?Response
    {
        $response = $this->getController()->getResponse()->withHeader('HX-Replace-Url', $url);
        $this->getController()->setResponse($response);

        return $this->getController()->getResponse();
    }

    /**
     * Specify how the response will be swapped
     *
     * @param string $option For available values see https://htmx.org/attributes/hx-swap/
     * @return \Cake\Http\Response|null
     */
    public function reswap(string $option): ?Response
    {
        $response = $this->getController()->getResponse()->withHeader('HX-Reswap', $option);
        $this->getController()->setResponse($response);

        return $this->getController()->getResponse();
    }

    /**
     * A CSS selector that updates the target of the content update to a different element on the page
     *
     * @param string $selector Selector name
     * @return \Cake\Http\Response|null
     */
    public function retarget(string $selector): ?Response
    {
        $response = $this->getController()->getResponse()->withHeader('HX-Retarget', $selector);
        $this->getController()->setResponse($response);

        return $this->getController()->getResponse();
    }

    /**
     * Add client side trigger
     *
     * @link https://htmx.org/headers/hx-trigger/
     * @param string $key
     * @param array|string|null $body
     * @return static
     */
    public function addTrigger(string $key, string|array|null $body = null): static
    {
        $this->triggers[$key] = $body;

        return $this;
    }

    /**
     * Add client side trigger that will be called after settle
     *
     * @link https://htmx.org/headers/hx-trigger/
     * @param string $key
     * @param array|string|null $body
     * @return static
     */
    public function addTriggerAfterSettle(string $key, string|array|null $body = null): static
    {
        $this->triggersAfterSettle[$key] = $body;

        return $this;
    }

    /**
     * Add client side trigger that will be called after swap
     *
     * @link https://htmx.org/headers/hx-trigger/
     * @param string $key
     * @param array|string|null $body
     * @return static
     */
    public function addTriggerAfterSwap(string $key, string|array|null $body = null): static
    {
        $this->triggersAfterSwap[$key] = $body;

        return $this;
    }

    /**
     * Prepare the response
     *
     * @return \Cake\Http\Response|null
     */
    public function prepare(): ?Response
    {
        $response = $this->getController()->getResponse();

        if (!empty($this->triggers)) {
            $response = $response->withHeader('HX-Trigger', $this->encodeTriggers($this->triggers));
        }

        if (!empty($this->triggersAfterSettle)) {
            $response = $response->withHeader('HX-Trigger-After-Settle', $this->encodeTriggers($this->triggersAfterSettle));
        }

        if (!empty($this->triggersAfterSwap)) {
            $response = $response->withHeader('HX-Trigger-After-Swap', $this->encodeTriggers($this->triggersAfterSwap));
        }

        $this->getController()->setResponse($response);

        return $this->getController()->getResponse();
    }

    /**
     * Trigger a client side redirect
     *
     * @param string $to Where to redirect
     * @return \Cake\Http\Response|null
     */
    public function redirect(string $to): ?Response
    {
        $response = $this->getController()->getResponse();
        $response = $response->withHeader('HX-Redirect', $to)->withStatus(200);
        $this->getController()->setResponse($response);

        return $this->getController()->getResponse();
    }

    /**
     * Trigger a page reload
     *
     * @return \Cake\Http\Response|null
     */
    public function clientRefresh(): ?Response
    {
        $response = $this->getController()->getResponse();
        $response = $response->withHeader('HX-Refresh', 'true');
        $response = $response->withStatus(200);
        $this->getController()->setResponse($response);

        return $this->getController()->getResponse();
    }

    /**
     * Stop Htmx polling
     *
     * @param string $content The content that will be set
     * @param array $headers The headers that will be set
     * @return \Cake\Http\Response|null
     */
    public function stopPolling($content = '', array $headers = []): ?Response
    {
        $response = $this->getController()->getResponse();

        if (!empty($headers)) {
            foreach ($headers as $name => $value) {
                $response = $response->withHeader($name, $value);
            }
        }

        $response = $response->withStatus(286);
        $response = $response->withStringBody($content);
        $this->getController()->setResponse($response);

        return $this->getController()->getResponse();
    }

    /**
     * Encode triggers
     *
     * @param array  $triggers List of triggers
     * @return string
     */
    private function encodeTriggers(array $triggers): string
    {
        $hasNonNullable = count($triggers) !== count(array_filter($triggers, 'is_null'));

        if ($hasNonNullable) {
            return json_encode($triggers);
        }

        return implode(',', array_keys($triggers));
    }

    /**
     * Set a specific block to render
     * 
     * @param null|string $block  Name of the block
     */
    public function setBlock($block): static
    {
        $this->block = $block;

        return $this;
    }

    /**
     * Get the block that will be rendered
     * 
     * @return null|string
     */
    public function getBlock(): ?string
    {
        return $this->block;
    }
}
