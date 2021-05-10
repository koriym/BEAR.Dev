# BEAR.DevTools

`HttpResource` starts a local server and becomes an HTTP client.

```php
$resource =  new HttpResource('127.0.0.1:8099', '/path/to/index.php',  '/path/to/curl.log');
$ro = $resource->get('/');
assert($ro->code === 200);
```

