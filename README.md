## Introduction

Cache System Smart PRO Technology

## Installation

```bash
composer require prismo-smartpro/cache
```

## Example of Use

```php
<?php

require "vendor/autoload.php";

use SmartPRO\Technology\CacheControl;

/*
 * Folder where the cache will be saved
 * Cache file extension
 */
$cache = new CacheControl(__DIR__ . "/cache", "cache");

/*
 * Creating a new cache
 * Expiry in minutes
 */

$cache->set("profile=john", [
    "name" => "John Walker",
    "age" => 35,
    "email" => "john@walker.com"
], 30);

/*
 * Checks if the cache exists, if it exists, return the cache, if it doesn't exist, create it using the function
 */

$withCallable = $cache->get("data", function () {
    return array(
        array("name" => "John Walker", "age" => 37),
        array("name" => "John Samuel", "age" => 17)
    );
}, 15);

var_dump($withCallable);

/*
 * Search for a cached file
 * If it doesn't exist, return null
 * If it has already expired, return null and delete the cache
 */

$data = $cache->get("profile=john");

/*
 * Delete cache
 */

$cache->delete("profile=john");
```