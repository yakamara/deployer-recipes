Yakamara recipes for [Deployer](https://github.com/deployphp/deployer)
===============================

Installation
------------

```
composer require yakamara/deployer-recipes
```

Usage
-----

```php
require __DIR__ . '/vendor/yakamara/deployer-recipes/recipe/symfony.php'; // or redaxo.php
```

If the web server keeps serving the previous release after the symlink switch (opcache/realpath cache),
enable resetting the web opcache via a temporary script requested over http:

```php
set('clear_web_php_cache', true);
set('url', 'https://example.org'); // defaults to https://{{hostname}}
```
