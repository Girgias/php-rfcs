# PHP RFC: Warn on conversions from resource to string

- Version: 0.1
- Date: 2024-09-24
- Author: Gina Peter Banyard <girgias@php.net>
- Status: Under Discussion
- Target Version: PHP 8.5
- Implementation: https://github.com/php/php-src/pull/16022
- First Published at: https://wiki.php.net/rfc/warn-resource-to-string

## Introduction

PHP has a history of performing various forms of type conversions.
While most dubious cases of such conversions nowadays throw a `TypeError`,
or at least emit an `E_WARNING`, the conversion of resources to strings
does none of these things.

As a consequence it is possible to concatenate a resource with a string without any errors,
but performing any sort of arithmetics with it throws a `TypeError`:

```php
<?php

var_dump(STDERR . 'hello');
var_dump(STDERR + 10);

?>
```
will result in:
```text
string(19) "Resource id #3hello"

Fatal error: Uncaught TypeError: Unsupported operand types: resource + int in FILE
```

This is surprising behaviour, especially as a non-stringable object being used with
the concatenation operator would throw an `Error`.

Considering that resources are being phased out in favour of opaque objects
and do not support being converted to strings.
We propose to align the behaviour with that of converting an `array` to `string`,
which is to emit a warning on implicit *and* explicit conversions.

## Proposal

Emit an `E_WARNING` when a conversion from `resource` to `string` occurs.
Some common situations where this can occur:

- String concatenation
- String interpolation
- `echo`
- `(string)` cast

## Backward Incompatible Changes

As warnings could be promoted to an exception via an error handler,
this may result in exceptions being thrown in place where none previously could. 

## Version

Next minor version, PHP 8.5.

## Vote

VOTING_SNIPPET

## Future scope

- Promote warning to a `TypeError`

## References

