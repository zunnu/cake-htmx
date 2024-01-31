# cake-htmx

CakePHP integration for [htmx](https://htmx.org/).

Supported CakePHP Versions >= 4.x.x. 5.x support will be added later.

## Installing Using [Composer][composer]

`cd` to the root of your app folder (where the `composer.json` file is) and run the following command:

```
composer require zunnu/cake-htmx
```
Then load the plugin by using CakePHP's console:

```
./bin/cake plugin load CakeHtmx
```
To install htmx please browse [their documentation](https://htmx.org/docs/#installing)

## Usage

Main functionality is currently wrapped inside Htmx component.
To load the component you will need to modify your `src/Controller/AppController.php` and load the Htmx component in the `initialize()` function
```php
$this->loadComponent('CakeHtmx.Htmx');
```

### Request

You can use detector to check if the request is Htmx.  
```php
$this->getRequest()->is('htmx')  // Always true if the request is performed by Htmx
$this->getRequest()->is('boosted') // Indicates that the request is via an element using hx-boost
$this->getRequest()->is('historyRestoreRequest') // True if the request is for history restoration after a miss in the local history cache
```
Using the component you can check more specific details about the request.  
```php
$this->Htmx->getCurrentUrl();  // The current URL of the browser
$this->Htmx->getPromptResponse(); // The user response to an hx-prompt
$this->Htmx->getTarget(); // The id of the target element if it exists
$this->Htmx->getTriggerName(); // The name of the triggered element if it exists
$this->Htmx->getTriggerId(); // The id of the triggered element if it exists
```

### Response
- `redirect`

Htmx can trigger a client side redirect when it receives a response with the `HX-Redirect` [header](https://htmx.org/reference/#response_headers).  

```php
$this->Htmx->redirect('/somewhere-else');
```

- `clientRefresh`

Htmx will trigger a page reload when it receives a response with the `HX-Refresh` [header](https://htmx.org/reference/#response_headers). `clientRefresh` is a custom response that allows you to send such a response. It takes no arguments, since Htmx ignores any content.

```php
$this->Htmx->clientRefresh();
```

- `stopPolling`

When using a [polling trigger](https://htmx.org/docs/#polling), Htmx will stop polling when it encounters a response with the special HTTP status code 286. `stopPolling` is a custom response with that status code.

```php
$this->Htmx->stopPolling();
```

See the documentation for all the remaining [available headers](https://htmx.org/reference/#response_headers).  
```php
$this->Htmx->location($location) // Allows you to do a client-side redirect that does not do a full page reload
$this->Htmx->pushUrl($url) // pushes a new url into the history stack
$this->Htmx->replaceUrl($url) // replaces the current URL in the location bar
$this->Htmx->reswap($option) // Allows you to specify how the response will be swapped
$this->Htmx->retarget($selector); // A CSS selector that updates the target of the content update to a different element on the page
```
Additionally, you can trigger [client-side events](https://htmx.org/headers/hx-trigger/) using the `addTrigger` methods.

```php
$this->Htmx
    ->addTrigger('myEvent')
    ->addTriggerAfterSettle('myEventAfterSettle')
    ->addTriggerAfterSwap('myEventAfterSwap');
```

If you want to pass details along with the event you can use the second argument to send a body. It supports strings or arrays.

```php
$this->Htmx->addTrigger('myEvent', 'Hello from myEvent')
->addTriggerAfterSettle('showMessage', [
    'level' => 'info',
    'message' => 'Here is a Message'
]);
```

You can call those methods multiple times if you want to trigger multiple events.

```php
$this->Htmx
    ->addTrigger('trigger1', 'A Message')
    ->addTrigger('trigger2', 'Another Message')
```

## CSRF token

To add CSRF token to all your request add below code to your layout.

```php
document.body.addEventListener('htmx:configRequest', (event) => {
    event.detail.headers['X-CSRF-Token'] = "<?= $this->getRequest()->getAttribute('csrfToken') ?>";
})
```

## License

Licensed under [The MIT License][mit].

[cakephp]:http://cakephp.org
[composer]:http://getcomposer.org
[mit]:http://www.opensource.org/licenses/mit-license.php
