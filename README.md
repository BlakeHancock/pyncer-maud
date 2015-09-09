# Maud
A PHP 5.5+ library to help with implementing extensions into your apps so that you can extend a single codebase to meet the unique needs of each client.

## Example usage

The following assumes all the neccessary files have been loaded either manually or via an auto loader.

### Eval Style Hook Handler With A File Hook Provider:

**Initialize:**
```php
$hookHandler = new EvalHookHandler();
$hookHandler->setCacheDirectory(__DIR__ . '/cache');

if (!$hookHandler->load()) {
    $hookProvider = new FileHookProvider();
    $hookProvider->addHookFile('my_alias', __DIR__ . '/my_hooks.php');

    $hookHandler->build($hookProvider);
    $hookHandler->save();
}

Hook::init($hookHandler);
```

**my_hooks.php:**
```php
function my_header() {
    echo 'Hello ';
}

function my_footer() {
    echo ' World';
}
```

**index.php:**
```php
$hook = Hook::get('my_header');
($hook ? eval($hook) : null);

echo 'Cruel';

$hook = Hook::get('my_footer');
($hook ? eval($hook) : null);
```

The resulting output of index.php would be: *Hello Cruel World*

### Include Style Hook Handler With A Closure Hook Provider:

Becasue the closure hook provider uses reflection, *use* statements are not supported. You must also ensure your opening and closing function brackets do not share the same line as others.

**Initialize:**
```php
$hookHandler = new IncludeHookHandler();
$hookHandler->setCacheDirectory(__DIR__ . '/cache');

if (!$hookHandler->load()) {
    $hookProvider = new ClosureHookProvider();
    $hookProvider->addHook('my_alias', 'my_header', function() {
        echo 'Woah, ';
        $cruel = 'Cool'; // We can modify values in the hook's scope.
    });
    $hookProvider->addHook('my_alias', 'my_footer', function() {
        echo ' World';
        return true; // Prevent default behaviour
    });

    $hookHandler->build($hookProvider);
    $hookHandler->save();
}

Hook::init($hookHandler);
```

**index.php:**
```php
$cruel = 'Cruel';

$hook = Hook::get('my_header');
// We support both methods because the include hook handler could return 'eval' if the cache is not saved.
$hookResult = ($hook ? (Hook::getMethod() == Hook::METHOD_EVAL ? eval($hook) : include($hook)) : null);
if (!$hookResult) {
    echo 'What\'s Up ';
}

echo $cruel;

$hook = Hook::get('my_footer');
$hookResult = ($hook ? (Hook::getMethod() == Hook::METHOD_EVAL ? eval($hook) : include($hook)) : null);
if (!$hookResult) {
    echo ' Planet';
}
```

The resulting output of index.php would be: *Woah, What's Up Cool World*

## Other Notes
1. Any *use* statements included in the code sent to the hook builder will be added to the included/evaluated hook.
2. If you have two or more code strings that share the same hook, you can specify an order priority by extending the *getHookPriority* method of the *HookProviderInterface*. Code will be ordered in ascending order based on the integer returned. If you return -1, it will be placed at the end of the list in whatever order they were added in.
3. You can *return;* from the code at any time without affecting any other code strings of the same hook.
4. Returning a non null value will stop any remaining code blocks that have non null returns from being used.
5. The *require* and *include* statements are supported, those files will be treated as hook files.