# cake-htmx

CakePHP integration for [htmx](https://htmx.org/).

Supported CakePHP Versions >= [4.x](https://github.com/zunnu/cake-htmx/tree/4.x) and 5.x.

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

## Rendering blocks and OOB Swap
The `setBlock()` function allows you to render a specific block while removing other blocks that might be rendered. This is particularly useful when you need to update only a portion of your view.

```php
$this->Htmx->setBlock('userTable');
```
The `addBlock()` function allows you to add a specific block to the list of blocks that should be rendered.

```php
$this->Htmx->addBlock('userTable');
```
The `addBlocks()` function allows you to add multiple blocks to the list of blocks that should be rendered
```php
$this->Htmx->addBlocks(['userTable', 'pagination']);
```

### OOB Swap
Htmx supports updating multiple targets by returning multiple partial responses with [`hx-swap-oop`](https://htmx.org/docs/#oob_swaps).
See the example `Users index search functionality with pagination update`
Note if you are working with tables like in the example. You might need to add
```javascript
<script type="text/javascript">
    htmx.config.useTemplateFragments = true;
</script>
```
In your template or layout.

## Examples

### Users index search functionality

In this example, we will implement a search functionality for the users' index using Htmx to filter results dynamically. We will wrap our table body inside a [viewBlock](https://book.cakephp.org/5/en/views.html#using-view-blocks) called `usersTable`. When the page loads, we will render the `usersTable` [viewBlock](https://book.cakephp.org/5/en/views.html#using-view-blocks).

```php
// Template/Users/index.php

<?= $this->Form->control('search', [
    'label' => false, 
    'placeholder' => __('Search'),
    'type' => 'text', 
    'required' => false, 
    'class' => 'form-control input-text search',
    'value' => !empty($search) ? $search : '',
    'hx-get' => $this->Url->build(['controller' => 'Users', 'action' => 'index']),
    'hx-trigger' => "keyup changed delay:200ms",
    'hx-target' => "#search-results",
    'templates' => [
        'inputContainer' => '<div class="col-10 col-md-6 col-lg-5">{{content}}</div>'
    ]
]); ?>

<table id="usersTable" class="table table-hover table-white-bordered">
    <thead>
        <tr>
            <th scope="col"><?= 'id' ?></th>
            <th scope="col"><?= 'Name' ?></th>
            <th scope="col"><?= 'Email' ?></th>
            <th scope="col"><?= 'Modified' ?></th>
            <th scope="col"><?= 'Created' ?></th>
            <th scope="col" class="actions"><?= 'Actions' ?></th>
        </tr>
    </thead>
    
    <tbody id="search-results">
        <?php $this->start('usersTable'); ?>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user->id ?></td>
                    <td><?= h($user->name) ?></td>
                    <td><?= h($user->email) ?></td>
                    <td><?= $user->modified ?></td>
                    <td><?= $user->created ?></td>
                    <td class="actions">
                        <?= $this->Html->link('Edit',
                            [
                                'action' => 'edit',
                                $user->id
                            ],
                            [
                                'escape' => false
                            ]
                        ); ?>
                        <?= $this->Form->postLink('Delete',
                            [
                                'action' => 'delete',
                                $user->id
                            ],
                            [
                                'confirm' => __('Are you sure you want to delete user {0}?', $user->email),
                                'escape' => false
                            ]
                        ); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php $this->end(); ?>

        <?php echo $this->fetch('usersTable'); ?>
    </tbody>
</table>
```
In out controller we will check if the request is Htmx and if so then we will only render the `usersTable` [viewBlock](https://book.cakephp.org/5/en/views.html#using-view-blocks).

```php
// src/Controller/UsersController.php

public function index()
{
    $search = null;
    $query = $this->Users->find('all');

    if ($this->request->is('get')) {
        if(!empty($this->request->getQueryParams())) {
            $data = $this->request->getQueryParams();

            if(isset($data['search'])) {
                $data = $data['search'];
                $conditions = [
                    'OR' => [
                        'Users.id' => (int)$data,
                        'Users.name LIKE' => '%' . $data . '%',
                        'Users.email LIKE' => '%' . $data . '%',
                    ],
                ];
                $query = $query->where([$conditions]);
                $search = $data;
            }
        }
    }

    $users = $query->toArray();
    $this->set(compact('users', 'search'));

    if($this->getRequest()->is('htmx')) {
        $this->viewBuilder()->disableAutoLayout();

        // we will only render the usersTable viewblock
        $this->Htmx->setBlock('usersTable');
    }
}
```

### Users index search functionality with pagination update
In this example, we will implement a dynamic search functionality for the users' index using Htmx. This will allow us to filter results in real-time and update pagination accordingly. We will wrap our table body inside a [viewBlock](https://book.cakephp.org/5/en/views.html#using-view-blocks) called `usersTable` and our pagination to `pagination` block. When the page loads, we will render both the `usersTable` and `pagination` [viewBlock](https://book.cakephp.org/5/en/views.html#using-view-blocks).

```php
// Template/Users/index.php

<?= $this->Form->control('search', [
    'label' => false, 
    'placeholder' => __('Search'),
    'type' => 'text', 
    'required' => false, 
    'class' => 'form-control input-text search',
    'value' => !empty($search) ? $search : '',
    'hx-get' => $this->Url->build(['controller' => 'Users', 'action' => 'index']),
    'hx-trigger' => 'keyup changed delay:200ms',
    'hx-target' => '#search-results',
    'hx-push-url' => 'true',
    'templates' => [
        'inputContainer' => '<div class="col-10 col-md-6 col-lg-5">{{content}}</div>'
    ]
]); ?>

<table id="usersTable" class="table table-hover table-white-bordered">
    <thead>
        <tr>
            <th scope="col"><?= 'id' ?></th>
            <th scope="col"><?= 'Name' ?></th>
            <th scope="col"><?= 'Email' ?></th>
            <th scope="col"><?= 'Modified' ?></th>
            <th scope="col"><?= 'Created' ?></th>
            <th scope="col" class="actions"><?= 'Actions' ?></th>
        </tr>
    </thead>
    
    <tbody id="search-results">
        <?php $this->start('usersTable'); ?>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user->id ?></td>
                    <td><?= h($user->name) ?></td>
                    <td><?= h($user->email) ?></td>
                    <td><?= $user->modified ?></td>
                    <td><?= $user->created ?></td>
                    <td class="actions">
                        <?= $this->Html->link('Edit',
                            [
                                'action' => 'edit',
                                $user->id
                            ],
                            [
                                'escape' => false
                            ]
                        ); ?>
                        <?= $this->Form->postLink('Delete',
                            [
                                'action' => 'delete',
                                $user->id
                            ],
                            [
                                'confirm' => __('Are you sure you want to delete user {0}?', $user->email),
                                'escape' => false
                            ]
                        ); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php $this->end(); ?>

        <?php echo $this->fetch('usersTable'); ?>
    </tbody>
</table>

// pagination
<?php $this->start('pagination'); ?>
    <nav aria-label="Page navigation" id="pagination">
        <ul class="pagination justify-content-center">
            <?php $this->Paginator->setTemplates([
                'prevActive' => '<li class="page-item pagination-previous"><a class="page-link" hx-get="{{url}}" hx-target="#search-results" hx-push-url="true" href="#">{{text}}</a></li>',
                'prevDisabled' => '<li class="page-item disabled pagination-previous"><a class="page-link" hx-get="{{url}}" hx-target="#search-results" hx-push-url="true" href="#">{{text}}</a></li>',
                'number' => '<li class="page-item"><a class="page-link" hx-get="{{url}}" hx-target="#search-results" hx-push-url="true" href="#">{{text}}</a></li>',
                'current' => '<li class="page-item active"><a class="page-link" hx-get="{{url}}" hx-target="#search-results" hx-push-url="true" href="#">{{text}}</a></li>',
                'nextActive' => '<li class="page-item pagination-next"><a class="page-link" hx-get="{{url}}" hx-target="#search-results" hx-push-url="true" href="#">{{text}}</a></li>',
                'nextDisabled' => '<li class="page-item disabled pagination-next"><a class="page-link" hx-get="{{url}}" hx-target="#search-results" hx-push-url="true" href="#">{{text}}</a></li>',
                'first' => '<li class="page-item pagination-next"><a class="page-link" hx-get="{{url}}" hx-target="#search-results" hx-push-url="true" href="#">{{text}}</a></li>',
                'last' => '<li class="page-item pagination-next"><a class="page-link" hx-get="{{url}}" hx-target="#search-results" hx-push-url="true" href="#">{{text}}</a></li>',
            ]); ?>
            <?= $this->Paginator->first('<i class="fas fa-angles-left"></i>', ['escape' => false]) ?>
            <?= $this->Paginator->prev('<i class="fas fa-chevron-left"></i>', ['escape' => false]) ?>
            <?= $this->Paginator->numbers(['first' => 1, 'last' => 1, 'modulus' => 3]) ?>
            <?= $this->Paginator->next('<i class="fas fa-chevron-right"></i>', ['escape' => false]) ?>
            <?= $this->Paginator->last('<i class="fas fa-angles-right"></i>', ['escape' => false]) ?>
        </ul>
    </nav>
<?php $this->end(); ?>

<?= $this->fetch('pagination'); ?>
```
In out controller we will check if the request is Htmx and if so then we will only render the `usersTable` [viewBlock](https://book.cakephp.org/5/en/views.html#using-view-blocks).

```php
// src/Controller/UsersController.php

public function index()
{
    $search = null;
    $query = $this->Users->find('all');

    if ($this->request->is('get')) {
        if(!empty($this->request->getQueryParams())) {
            $data = $this->request->getQueryParams();

            if(isset($data['search'])) {
                $data = $data['search'];
                $conditions = [
                    'OR' => [
                        'Users.id' => (int)$data,
                        'Users.name LIKE' => '%' . $data . '%',
                        'Users.email LIKE' => '%' . $data . '%',
                    ],
                ];
                $query = $query->where([$conditions]);
                $search = $data;
            }
        }
    }

    $this->paginate['limit'] = 200;
    $users = $this->paginate($query);
    $this->set(compact('users', 'search'));

    if($this->getRequest()->is('htmx')) {
        $this->viewBuilder()->disableAutoLayout();

        // render users table and pagination blocks
        $this->Htmx->addBlock('usersTable')->addBlock('pagination');
    }
}
```

## License

Licensed under [The MIT License][mit].

[cakephp]:http://cakephp.org
[composer]:http://getcomposer.org
[mit]:http://www.opensource.org/licenses/mit-license.php
