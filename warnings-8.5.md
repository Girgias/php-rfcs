# PHP RFC: Warnings for PHP 8.5

- Version: 0.1
- Date: 2025-07-14
- Author: Gina Peter Banyard <girgias@php.net>
- Status: Under Discussion
- Target Version: PHP 8.5
- Implementation: TBD
- First Published at: https://wiki.php.net/rfc/warnings-php-8-5

## Introduction

The RFC proposes to add a few warnings when encountering certain edge cases in PHP 8.5,
and promote those to error in PHP 9.

The following list provides a short overview of the warnings being introduced,
while more detailed explanation is provided in the Proposal section:

- Coercing `NAN` to other types
- Destructuring non-`array` values
- Using offsets on non-container values in `isset()`/`empty()`
- Using invalid offset types when checking string offsets with `isset()`/`empty()`

## Proposal

Each proposed warning is voted separately and requires a 2/3 majority.

### Coercing NAN to other types

- Author: Gina Peter Banyard <girgias@php.net>

The floating point value `NAN` is returned by functions to indicate some sort of error.

However, `NAN` can be coerced to other types without error and hide bugs (except for functions requiring an `int`).
More concerning is that the value `NAN` is considered truthy and will be coerced to the value `true`,
except when using an explicit `(int)` cast, in which case it is coerced to `0` which is falsy.

As having a `NAN` value is unlikely and points to some sort of bug in the code,
we propose to emit the following warning when `NAN` is coerced (be that implicitly or explicitly) to another type:

> Warning: unexpected NAN value was coerced to TYPE

This affects passing `NAN` as an argument to parameters which do not declare `float` as a type, all casting operators,
logical operators, if statements, and ternary operator.

### Destructuring non-array values

- Author: Gina Peter Banyard <girgias@php.net>

The `list($v1, $v2) = $array` or `[$v1, $v2] = $array` language construction
allows destructing array values into their own variables.

When attempting to destructure an object the following `Error` is thrown:

> Error: Cannot use object of type stdClass as array

However, when using scalar values (`bool`, `int`, `float`, `string`), `null`, or `resource`
no `Error` is thrown and each variable is assigned `null`.

We propose to emit the following warning when destructuring non-array values:

> Warning: Cannot use TYPE as array

### Using offsets on non-container values in isset()/empty()

- Author: Gina Peter Banyard <girgias@php.net>

This is lifted from the [Improve language coherence for the behaviour of offsets and containers](https://wiki.php.net/rfc/container-offset-behaviour) RFC as it is time-sensitive.

When attempting to check if an offset exists or is empty on objects that do not implement `ArrayOffset`
(or the relevant C object handlers) then the following error is thrown:

> Error: Cannot use object of type TYPE as array

However, when using scalar values (`bool`, `int`, `float`, `string`), `null`, or `resource`
no `Error` is thrown and `false` is returned for `isset()` and `true` is returned for `empty()`.

As this points to some code expectation being violated,
we propose to emit the following warning when using a scalar value or a `resource` as a container:  

> Warning: Cannot use TYPE as array

We do not propose to warn when using `null` as a container as `null` supports auto-vivification to array,
and can be used as a sentinel value.


### Using invalid offset types when checking string offsets with isset()/empty()

- Author: Gina Peter Banyard <girgias@php.net>

This is lifted from the [Improve language coherence for the behaviour of offsets and containers](https://wiki.php.net/rfc/container-offset-behaviour) RFC as it is time-sensitive.

When attempting to check an array offset with `isset()` or `empty()`,
with an object or array as the offset the following `TypeError` is thrown:

> TypeError: Illegal offset type in isset or empty

This is similar to a `TypeError` being thrown by the `ArrayAccess::offsetExists()` (/the equivalent C object handler)
for some internal classes like `SplFixedArray`.

However, when using a string as a container (e.g. `$string[$offset]`) invalid offsets are ignored
and `false` is returned for `isset()` and `true` is returned for `empty()`.
This behaviour is even stranger as the null-coalescing operator *does* throw the following `TypeError`,
using the following code snippet as an example:
```php

$s = 'abcdefg';
$o = new stdClass();
var_dump(isset($s[$o]));
var_dump($s[$o] ?? 'z');
```
we get the following:
```php
bool(false)
Fatal error: Uncaught TypeError: Cannot access offset of type stdClass on string
```

As this likely points to a bug and to normalize the behaviour with the null coalesced operator,
we propose to emit the following warning when using an invalid offset type for a string offset:

> Warning: Cannot access offset of type TYPE on string

## Backward Incompatible Changes

For PHP 8.5 additional warnings will be emitted.
The promotions of warnings to the appropriate `Error` will happen no earlier than PHP 9. 
