# materia

materia is a library intended to provide easy integration of addons by way of phar archives or 

## copyright

(c) 2011 emberlabs.org

## license

This library is licensed under the MIT license; you can find a full copy of the license itself in the file /LICENSE

## requirements

* PHP 5.3.0 or newer

## usage

We'll assume you're using this git repository as a git submodule, and have it located at `includes/emberlabs/materia/` according to namespacing rules, for easy autoloading.

### general example

``` php
	<?php
	include __DIR__ . '/includes/emberlabs/materia/Loader.php';
	$materia = new \emberlabs\materia\Loader();
```
