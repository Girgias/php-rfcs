# PHP RFC: Transform exit() from a language construct into a standard function

- Version: 0.1
- Date: 2024-05-05
- Author: Gina Peter Banyard <girgias@php.net>
- Status: Under Discussion
- Target Version: PHP 8.4
- Implementation: <https://github.com/php/php-src/pull/13483>
- First Published at: <http://wiki.php.net/rfc/exit-as-function>

## Introduction

The `exit` (and it's alias `die`) language construct can be used on its own as a "constant"
((We are using this terminology as it can be used in any place where an expression is expected, like a constant: https://3v4l.org/sL9Q5))
to terminate a PHP script with status code `0`, or it can be used like a "function" which accepts an
optional argument `$status` that can either be an integer, in which case the PHP script will be terminated
with the given integer as the status code, or it can be a string, in which case the PHP script is terminated
with status code `0` and the string is printed to STDOUT.

However, because `exit()` is not a proper function it cannot be called with named argument,
passed to functions as a `callable`, does not respect the `strict_types` declare,
and most confusingly, it does not follow the usual type juggling semantics.

Indeed, any value which is *not* an integer, is cast to a string.
This means passing an array or a resource to `exit()` will not throw a `TypeError`
but print `Array` or `Resource id #%d` respectively with the relevant warning being emitted.
However, it does throw a `TypeError` for non-`Stringable` objects.

Moreover, arguments of type `bool` are cast to `string` instead of `int` violating the standard type juggling semantics
for a `string|int` union type, this is something that we find especially confusing for CLI scripts that may have a
boolean `$has_error` variable that is passed to `exit()` with the assumption `false` will be coerced to `0`
and `true` coerced to `1`.

Finally, the need for `exit()` to be a language construct with its own dedicated opcode is not a requirement any more
since PHP 8.0 as the opcode throws a special kind of exception which cannot be caught,
((https://github.com/php/php-src/pull/5768))
nor executes `finally` blocks, to unwind the stack normally.

## Proposal

We propose to make `exit()` a proper function with the following signature:
```php
function exit(string|int $status = 0): never {}
```

And to make `die()` an alias of `exit()`, transform "constant" usages of `exit`/`die` to function calls at compile time.

It will continue to be impossible to declare `exit` or `die` functions in namespaces,
or disable/remove them via the `disable_functions` INI directive.

Moreover, as `exit` and `die` will no longer be keywords, it will be possible to use them as `goto` labels.

Another slight improvement is that the AST printing (seen via `assert()`)
now reflects what has actually been written in the source code rather than always using `exit`. 

## Backward Incompatible Changes

The impact of this RFC is deemed to be low.

The `T_EXIT` token will be removed because `exit` will no longer need to be parsed specially by the lexer.
As most PHP libraries that deal on an AST level use Nikita Popov's `php-parser` which creates its own AST,
this should have minimal impact on userland tooling.

Projects that directly use the tokenizer extensions, like Exakat, will need some straight-forward adaptation.

And the behaviour of value of different types passed to `exit()` will be altered to match the usual type juggling semantics:

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

- Deprecate using `exit` as a "constant"
- Execute `finally` blocks for `exit`s
- Allow disabling `exit()`/`die()` functions via the `disable_functions` INI directive, similarly to how it is possible to disable `assert()`


## Version

Next minor version, PHP 8.4.

## Vote

VOTING_SNIPPET

## Notes
