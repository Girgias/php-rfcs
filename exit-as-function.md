# PHP RFC: Transform exit() from a language construct into a standard function

- Version: 0.2
- Date: 2024-05-05
- Author: Gina Peter Banyard <girgias@php.net>
- Status: Under Discussion
- Target Version: PHP 8.4
- Implementation: <https://github.com/php/php-src/pull/13483>
- First Published at: <http://wiki.php.net/rfc/exit-as-function>

## Introduction

The `exit` language construct (and its alias `die`) can be used on its own without parentheses 
((which is somewhat akin to using a constant as it can be used in expressions: https://3v4l.org/sL9Q5))
to terminate a PHP script with status code `0`, or it can be used like a "function" which accepts an
optional argument `$status` that can either be an integer, in which case the PHP script will be terminated
with the given integer as the status code, or it can be a string, in which case the PHP script is terminated
with status code `0` and the string is printed to STDOUT.

However, because `exit()` is not a proper function it cannot be called with a named argument,
passed to functions as a `callable`, does not respect the `strict_types` declare,
and most confusingly, it does not follow the usual type juggling semantics.

Indeed, any value which is *not* an integer, is cast to a string.
This means passing an array or a resource to `exit()` will not throw a `TypeError`
but print `Array` or `Resource id #%d` respectively with the relevant warning being emitted.
However, it does throw a `TypeError` for non-`Stringable` objects.

Furthermore, arguments of type `bool` are cast to `string` instead of `int`, violating the standard type juggling semantics
for a `string|int` union type. This is something that we find especially confusing for CLI scripts which may have a
boolean `$has_error` variable passed to `exit()` with the assumption that `false` will be coerced to `0`
and `true` coerced to `1`.

Since PHP 8.0 `exit()` doesn't use the bailout mechanism anymore,
but throws a special kind of exception which cannot be caught,
and which does not execute `finally` blocks. ((https://github.com/php/php-src/pull/5768))

Finally, there didn't seem to ever have been a necessity for `exit()` to be its own dedicated opcode.

## Proposal

We propose to make `exit()` a proper function with the following signature:
```php
function exit(string|int $status = 0): never {}
```

Parsing of the keywords remains unchanged, but instead of being compiled to a `ZEND_EXIT` opcode they are compiled to
a function call.

Therefore, it will remain impossible to declare functions named `exit` or `die` in namespaces,
or to disable/remove them via the `disable_functions` INI directive.

## Backward Incompatible Changes

The impact of this RFC is deemed to be low.

The behaviour of values of different types passed to `exit()` will be altered to match the usual type juggling semantics:

| Argument passed       | Current behaviour | New behaviour | Consequences                                                                                                                         |
|-----------------------|-------------------|---------------|--------------------------------------------------------------------------------------------------------------------------------------|
| int                   | int               | int           | No change, interpreted as exit code                                                                                                  |
| string                | string            | string        | No change, interpreted as status message                                                                                             |
| bool                  | string            | int           | Was status message, now exit code                                                                                                    |
| float                 | string            | int           | Was status message, now exit code, with a possible `"Implicit conversion from float to int loses precision"` deprecation notice        |
| null                  | string            | int           | Was status message, now exit code, with `"Passing null to parameter #1 ($status) of type string|int is deprecated"` deprecation notice |
| stringable object     | string            | string        | No change, interpreted as status message                                                                                             |
| non-stringable object | TypeError         | TypeError     | None                                                                                                                                 |
| array                 | string            | TypeError     | Was status message with warning, now TypeError                                                                                       |
| resource              | string            | TypeError     | Was status message with warning, now TypeError                                                                                       |


## Future scope

These are ideas for future proposals that are *not* part of this RFC:

- Deprecate using `exit` without parentheses
- Execute `finally` blocks when `exit` is called
- Allow disabling `exit()`/`die()` functions via the `disable_functions` INI directive, similarly to how it is possible to disable `assert()`


## Version

Next minor version, PHP 8.4.

## Vote

VOTING_SNIPPET

## Changelog

In a prior version of this RFC the `T_EXIT` token was removed.

## Notes
