<?php
declare(strict_types=1);

namespace CakeHtmx\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use CakeHtmx\Controller\Component\HtmxComponent;

/**
 * Test case for \CakeHtmx\Controller\Component\HtmxComponent.
 */
final class HtmxComponentTest extends TestCase
{
    /**
     * The component under test.
     *
     * @var \CakeHtmx\Controller\Component\HtmxComponent
     */
    private HtmxComponent $Htmx;

    /**
     * Host controller instance.
     *
     * @var \Cake\Controller\Controller
     */
    private Controller $Controller;

    /**
     * setUp method.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $request = new ServerRequest();

        // CakePHP 5: Controller no longer accepts Response as 2nd arg.
        $this->Controller = new Controller($request);
        $this->Controller = $this->Controller->setResponse(new Response());

        $registry = new ComponentRegistry($this->Controller);
        $this->Htmx = new HtmxComponent($registry);
    }

    /**
     * tearDown method.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Htmx, $this->Controller);
        parent::tearDown();
    }

    /**
     * beforeRender(): on a non-htmx request, no headers are written
     * even if triggers have been added.
     *
     * @return void
     */
    public function testBeforeRenderNonHtmxDoesNotWriteHeaders(): void
    {
        // Attach detector to current (non-htmx) request
        $req = $this->Controller->getRequest();
        $req->addDetector('htmx', function ($r) {
            return filter_var($r->getHeaderLine('HX-Request'), FILTER_VALIDATE_BOOLEAN);
        });
        $this->Controller = $this->Controller->setRequest($req);

        // Sanity check: not htmx
        $this->assertFalse($this->Controller->getRequest()->is('htmx'));

        // Add a trigger; since not htmx, prepare() should not run
        $this->Htmx->addTrigger('alpha');

        $this->Htmx->beforeRender(new Event('Controller.beforeRender', $this->Controller));
        $this->assertSame('', $this->Controller->getResponse()->getHeaderLine('HX-Trigger'));
    }

    /**
     * beforeRender(): when request is htmx, accumulated triggers are applied.
     *
     * @return void
     */
    public function testBeforeRenderAppliesTriggersWhenHtmx(): void
    {
        // Use stubbed request that reports is('htmx') === true
        $this->Controller = $this->Controller->setRequest($this->makeHtmxTrueRequest());

        // Add triggers (component retains them)
        $this->Htmx->addTrigger('alpha')->addTrigger('beta');

        // Run the hook
        $this->Htmx->beforeRender(new Event('Controller.beforeRender', $this->Controller));

        // Assert headers were written by prepare()
        $this->assertSame('alpha,beta', $this->Controller->getResponse()->getHeaderLine('HX-Trigger'));
    }

    /**
     * Test afterRender(): renders chosen blocks and adds hx-swap-oob to subsequent ones.
     *
     * @return void
     * @link \CakeHtmx\Controller\Component\HtmxComponent::afterRender()
     */
    public function testAfterRenderBlocksAndOobSwap(): void
    {
        // Use a partial mock to force exists() true for our block names.
        $view = $this->getMockBuilder(View::class)
            ->setConstructorArgs([$this->Controller->getRequest()])
            ->onlyMethods(['exists'])
            ->getMock();

        $view->method('exists')->willReturnCallback(function (string $name): bool {
            return in_array($name, ['first', 'second'], true);
        });

        // Define two blocks
        $view->start('first');
        echo '<div id="one">A</div>';
        $view->end();

        $view->start('second');
        echo '<section data-x="y">B</section>';
        $view->end();

        $this->Htmx->addBlocks(['first', 'second']);

        // Prime content then ensure it's cleared/replaced
        $view->assign('content', 'ORIGINAL');

        $this->Htmx->afterRender(new Event('View.afterRender', $view));
        $content = $view->fetch('content');

        $this->assertStringContainsString('<div id="one">A</div>', $content);
        $this->assertStringContainsString(
            '<section data-x="y" hx-swap-oob="innerHTML">B</section>',
            $content,
        );
        $this->assertStringNotContainsString('ORIGINAL', $content);
    }

    /**
     * Test header getter helpers.
     *
     * @return void
     */
    public function testHeaderGetters(): void
    {
        $req = $this->Controller->getRequest()
            ->withHeader('HX-Current-Url', 'https://example.test/page')
            ->withHeader('HX-Prompt', 'yes')
            ->withHeader('HX-Target', 'list')
            ->withHeader('HX-Trigger-Name', 'delete')
            ->withHeader('HX-Trigger', 'btn-42');
        $this->Controller = $this->Controller->setRequest($req);

        $registry = new ComponentRegistry($this->Controller);
        $this->Htmx = new HtmxComponent($registry);

        $this->assertSame('https://example.test/page', $this->Htmx->getCurrentUrl());
        $this->assertSame('yes', $this->Htmx->getPromptResponse());
        $this->assertSame('list', $this->Htmx->getTarget());
        $this->assertSame('delete', $this->Htmx->getTriggerName());
        $this->assertSame('btn-42', $this->Htmx->getTriggerId());
    }

    /**
     * Test simple response header mutators.
     *
     * @return void
     */
    public function testSimpleHeaderMutators(): void
    {
        $this->Htmx->location('/go');
        $this->assertSame('/go', $this->Controller->getResponse()->getHeaderLine('HX-Location'));

        $this->Htmx->pushUrl('/new');
        $this->assertSame('/new', $this->Controller->getResponse()->getHeaderLine('HX-Push-Url'));

        $this->Htmx->replaceUrl('/replace');
        $this->assertSame('/replace', $this->Controller->getResponse()->getHeaderLine('HX-Replace-Url'));

        $this->Htmx->reswap('none');
        $this->assertSame('none', $this->Controller->getResponse()->getHeaderLine('HX-Reswap'));

        $this->Htmx->retarget('#modal');
        $this->assertSame('#modal', $this->Controller->getResponse()->getHeaderLine('HX-Retarget'));
    }

    /**
     * prepare(): no headers when no triggers exist.
     *
     * @return void
     * @link \CakeHtmx\Controller\Component\HtmxComponent::prepare()
     */
    public function testPrepareNoTriggersNoHeaders(): void
    {
        $this->Htmx->prepare();
        $res = $this->Controller->getResponse();

        $this->assertSame('', $res->getHeaderLine('HX-Trigger'));
        $this->assertSame('', $res->getHeaderLine('HX-Trigger-After-Settle'));
        $this->assertSame('', $res->getHeaderLine('HX-Trigger-After-Swap'));
    }

    /**
     * prepare(): CSV encoding when all bodies are null.
     *
     * @return void
     * @link \CakeHtmx\Controller\Component\HtmxComponent::prepare()
     */
    public function testPrepareWithCsvEncodedTriggers(): void
    {
        $this->Htmx
            ->addTrigger('alpha')
            ->addTrigger('beta')
            ->addTriggerAfterSettle('gamma')
            ->addTriggerAfterSwap('delta');

        $this->Htmx->prepare();
        $res = $this->Controller->getResponse();

        $this->assertSame('alpha,beta', $res->getHeaderLine('HX-Trigger'));
        $this->assertSame('gamma', $res->getHeaderLine('HX-Trigger-After-Settle'));
        $this->assertSame('delta', $res->getHeaderLine('HX-Trigger-After-Swap'));
    }

    /**
     * prepare(): JSON encoding when any body is non-null.
     *
     * @return void
     * @link \CakeHtmx\Controller\Component\HtmxComponent::prepare()
     */
    public function testPrepareWithJsonEncodedTriggers(): void
    {
        // Reset response to avoid header accumulation
        $this->Controller = $this->Controller->setResponse(new Response());
        $registry = new ComponentRegistry($this->Controller);
        $this->Htmx = new HtmxComponent($registry);

        $this->Htmx
            ->addTrigger('saved', ['id' => 7])
            ->addTrigger('notify', 'ok') // multiple keys in JSON
            ->addTriggerAfterSettle('notice', 'ok')
            ->addTriggerAfterSwap('ping', ['a' => 1, 'b' => 2]);

        $this->Htmx->prepare();
        $res = $this->Controller->getResponse();

        $this->assertSame('{"saved":{"id":7},"notify":"ok"}', $res->getHeaderLine('HX-Trigger'));
        $this->assertSame('{"notice":"ok"}', $res->getHeaderLine('HX-Trigger-After-Settle'));
        $this->assertSame('{"ping":{"a":1,"b":2}}', $res->getHeaderLine('HX-Trigger-After-Swap'));
    }

    /**
     * Test redirect() sets HX-Redirect and 200 status.
     *
     * @return void
     * @link \CakeHtmx\Controller\Component\HtmxComponent::redirect()
     */
    public function testRedirect(): void
    {
        $this->Htmx->redirect('/somewhere');
        $resp = $this->Controller->getResponse();

        $this->assertSame('/somewhere', $resp->getHeaderLine('HX-Redirect'));
        $this->assertSame(200, $resp->getStatusCode());
    }

    /**
     * Test clientRefresh() sets HX-Refresh and 200 status.
     *
     * @return void
     * @link \CakeHtmx\Controller\Component\HtmxComponent::clientRefresh()
     */
    public function testClientRefresh(): void
    {
        $this->Htmx->clientRefresh();
        $resp = $this->Controller->getResponse();

        $this->assertSame('true', $resp->getHeaderLine('HX-Refresh'));
        $this->assertSame(200, $resp->getStatusCode());
    }

    /**
     * Test stopPolling() sets 286 status, optional headers, and body.
     *
     * @return void
     * @link \CakeHtmx\Controller\Component\HtmxComponent::stopPolling()
     */
    public function testStopPolling(): void
    {
        $resp = $this->Htmx->stopPolling('done', ['X-Test' => '1']);

        $this->assertInstanceOf(Response::class, $resp);
        /** @var \Cake\Http\Response $resp */
        $this->assertSame(286, $resp->getStatusCode());
        $this->assertSame('1', $resp->getHeaderLine('X-Test'));
        $this->assertSame('done', (string)$resp->getBody());
    }

    /**
     * Test block list helpers: setBlock(), addBlock(), addBlocks(), getBlocks().
     *
     * @return void
     * @link \CakeHtmx\Controller\Component\HtmxComponent::setBlock()
     * @link \CakeHtmx\Controller\Component\HtmxComponent::addBlock()
     * @link \CakeHtmx\Controller\Component\HtmxComponent::addBlocks()
     * @link \CakeHtmx\Controller\Component\HtmxComponent::getBlocks()
     */
    public function testBlockHelpers(): void
    {
        $this->Htmx->setBlock('usersTable');
        $this->assertSame(['usersTable'], $this->Htmx->getBlocks());

        $this->Htmx->addBlock('pagination');
        $this->assertSame(['usersTable', 'pagination'], $this->Htmx->getBlocks());

        // Replace with new list
        $this->Htmx->addBlocks(['a', 'b'], false);
        $this->assertSame(['a', 'b'], $this->Htmx->getBlocks());

        // Append to existing
        $this->Htmx->addBlocks(['c'], true);
        $this->assertSame(['a', 'b', 'c'], $this->Htmx->getBlocks());
    }

    /**
     * addBlocks(): default behavior appends to existing blocks (append = true).
     *
     * @return void
     */
    public function testAddBlocksDefaultAppends(): void
    {
        // Seed with one block
        $this->Htmx->setBlock('initial');
        $this->assertSame(['initial'], $this->Htmx->getBlocks());

        // Call addBlocks() WITHOUT the $append argument -> should append
        $this->Htmx->addBlocks(['a', 'b']); // default append=true
        $this->assertSame(['initial', 'a', 'b'], $this->Htmx->getBlocks());

        // Another default call should keep appending
        $this->Htmx->addBlocks(['c']);
        $this->assertSame(['initial', 'a', 'b', 'c'], $this->Htmx->getBlocks());
    }

    /**
     * afterRender(): skips non-existent blocks and still clears original content.
     *
     * @return void
     */
    public function testAfterRenderSkipsMissingBlocksAndClearsContent(): void
    {
        // Mock a view where only 'exists' returns false for all names
        $view = $this->getMockBuilder(View::class)
            ->setConstructorArgs([$this->Controller->getRequest()])
            ->onlyMethods(['exists'])
            ->getMock();

        $view->method('exists')->willReturn(false);

        // Seed content and request non-existent blocks
        $view->assign('content', 'ORIGINAL');
        $this->Htmx->addBlocks(['nope', 'missing', 'ghost']);

        $this->Htmx->afterRender(new Event('View.afterRender', $view));

        // Content should be cleared, and no appended markup
        $this->assertSame('', $view->fetch('content'));
    }

    /**
     * addTrigger(): later calls with the same key overwrite; mixed null/non-null bodies
     * force JSON encoding in prepare().
     *
     * @return void
     */
    public function testTriggersOverwriteAndMixedBodiesYieldJson(): void
    {
        // Overwrite same key
        $this->Htmx->addTrigger('event', null);
        $this->Htmx->addTrigger('event', ['id' => 123]); // last write wins

        // Mix of null and non-null across families => JSON everywhere
        $this->Htmx->addTrigger('another', null);
        $this->Htmx->addTriggerAfterSettle('settleOne', null);
        $this->Htmx->addTriggerAfterSettle('settleTwo', 'ok');
        $this->Htmx->addTriggerAfterSwap('swapOne', null);
        $this->Htmx->addTriggerAfterSwap('swapTwo', ['a' => 1]);

        $this->Htmx->prepare();
        $res = $this->Controller->getResponse();

        $this->assertSame('{"event":{"id":123},"another":null}', $res->getHeaderLine('HX-Trigger'));
        $this->assertSame('{"settleOne":null,"settleTwo":"ok"}', $res->getHeaderLine('HX-Trigger-After-Settle'));
        $this->assertSame('{"swapOne":null,"swapTwo":{"a":1}}', $res->getHeaderLine('HX-Trigger-After-Swap'));
    }

    /**
     * setBlock(null) should be safe and not change content or explode.
     *
     * @return void
     */
    public function testSetBlockNullIsSafe(): void
    {
        $view = $this->getMockBuilder(View::class)
            ->setConstructorArgs([$this->Controller->getRequest()])
            ->onlyMethods(['exists'])
            ->getMock();

        $view->method('exists')->willReturn(false);

        $view->assign('content', 'ORIGINAL');

        $this->Htmx->setBlock(null);
        $this->Htmx->afterRender(new Event('View.afterRender', $view));

        // Nothing was selected -> content stays as-is
        $this->assertSame('ORIGINAL', $view->fetch('content'));
    }

    /**
     * prepare(): must not drop existing response headers that the app has set.
     *
     * @return void
     */
    public function testPreparePreservesExistingHeaders(): void
    {
        // Seed an application header
        $this->Controller = $this->Controller->setResponse(
            $this->Controller->getResponse()->withHeader('X-App', 'keep'),
        );

        // Add a trigger so prepare() will write HX-Trigger
        $this->Htmx->addTrigger('alpha');
        $this->Htmx->prepare();

        $res = $this->Controller->getResponse();
        $this->assertSame('keep', $res->getHeaderLine('X-App'));
        $this->assertSame('alpha', $res->getHeaderLine('HX-Trigger'));
    }

    /**
     * Create a stubbed request where is('htmx') always returns true.
     *
     * Useful for simulating htmx requests in tests without adding
     * extra files or named classes.
     *
     * @return \Cake\Http\ServerRequest
     */
    private function makeHtmxTrueRequest(): ServerRequest
    {
        return new class extends ServerRequest {
            public function is(array|string $type, mixed ...$args): bool
            {
                if ($type === 'htmx' || (is_array($type) && in_array('htmx', $type, true))) {
                    return true;
                }

                return parent::is($type, ...$args);
            }
        };
    }

    /**
     * clearBlocks(): removes all blocks; afterRender() should not change content.
     *
     * @return void
     */
    public function testClearBlocks(): void
    {
        $view = new View($this->Controller->getRequest());
        $view->assign('content', 'ORIGINAL');

        $this->Htmx->setBlock('usersTable');
        $this->Htmx->clearBlocks();

        $this->assertSame([], $this->Htmx->getBlocks());

        $this->Htmx->afterRender(new Event('View.afterRender', $view));
        $this->assertSame('ORIGINAL', $view->fetch('content'));
    }
}
