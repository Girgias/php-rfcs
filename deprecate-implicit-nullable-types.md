# PHP RFC: Deprecate implicitly nullable parameter types

- Version: 0.1
- Date: 2023-12-20
- Authors: Máté Kocsis <kocsismate@php.net>, Gina Peter Banyard <girgias@php.net>
- Status: Under Discussion
- Target Version: PHP 8.4
- Implementation: [https://github.com/php/php-src/pull/12959](https://github.com/php/php-src/pull/12959)
- First Published at: [http://wiki.php.net/rfc/deprecate-implicitly-nullable-types](http://wiki.php.net/rfc/deprecate-implicitly-nullable-types)

## Introduction

PHP supports nullable type declarations as of PHP 7.1 with the [``?T`` syntax](https://wiki.php.net/rfc/nullable_types),
and with the [`T|null` syntax](https://wiki.php.net/rfc/union_types_v2) as of PHP 8.0 when generalized support for union types was added.

However, it has been possible to use [scalar type declarations](https://wiki.php.net/rfc/scalar_type_hints_v5) as of PHP 7.0,
the [`callable` type](https://wiki.php.net/rfc/callable) as of PHP 5.4, `array` as of PHP 5.1, and class types as of PHP 5.0.

As it was impossible to have a default value for such types,
PHP 5.1 made it possible to use `null` (and only `null`) as a default value for such type and making these types implicitly nullable.

However, the implicitly nullable semantics are confusing and conflict with other language rules,
in particular because type declarations of these types can be misleading as to what types they actually accept.

Furthermore, this syntax still permits signatures such as:
```php
function foo(T1 $a, T2 $b = null, T3 $c) {}
```
which appears to suggest an optional parameter before a required one.
Even though signatures which contain an optional parameter before a required one were [deprecated in PHP 8.0](https://github.com/php/php-src/pull/5067),
the case of implicitly nullable types was left alone at that time due to BC concerns.
This exclusion caused some bugs in the detection of which signatures should emit the deprecation notice.
Indeed, the following signature only emits a deprecation as of [PHP 8.1](https://github.com/php/php-src/commit/c939bd2f10b41bced49eb5bf12d48c3cf64f984a):
```php
function bar(T1 $a, ?T2 $b = null, T3 $c) {}
```
And the signature that uses the generalized union type signature:
```php
function test(T1 $a, T2|null $b = null, T3 $c) {}
```
only emits the deprecation notice properly as of [PHP 8.3](https://github.com/php/php-src/pull/11497).

It should be noted that the example signatures above were deprecated prior to the introduction of [named parameters](https://wiki.php.net/rfc/named_params),
which actually allowed calls to such functions in 8.0, but this was [corrected in PHP 8.1](https://github.com/php/php-src/commit/afc4d67c8b4e02a985a4cd27b8e79b343eb3c0ad). [1]

Therefore, as of PHP 8.1, any parameter that has a default value prior to a required one is effectively a required parameter,
and will throw an `ArgumentCountError` exception if the parameter is not provided,
be that positionally or via a named argument.
In consequence, support for implicitly nullable types already causes confusions about what should be permitted and what should not.

Another issue with implicitly nullable types is in relation to inheritance.
It is rather confusing that if a child class has the exact same type signature as the parent,
but a different default value, this would cause an LSP violation error to be thrown.

It should be noted, that prior to PHP 7.1 it was *actually* possible to violate
the LSP by changing the default value away from `null`.
This was fixed as part of the introduction of [nullable types](https://wiki.php.net/rfc/nullable_types).

As demonstrated, supporting this "feature" not only causes confusion
for userland, but is also a source of bugs and unneeded complexity within the engine which needs to handle its edge cases
(e.g. to promote an implicitly nullable intersection type to a DNF type).

Implicitly nullable types were added to work around the limitations of PHP 5's primitive type declaration system;
as those limitations do not exist anymore, we propose to deprecate this feature.

## Proposal

Deprecate implicitly nullable types which are written with the following signature:
```php
function foo(T $var = null) {}
```
by emitting the following deprecation notice at compile time:
```php
Deprecated: Implicitly marking parameter $var as nullable is deprecated, the explicit nullable type must be used instead
```

## Backward Incompatible Changes

Using an implicitly nullable type will emit a deprecation notice.

There exist a variety of userland tools to automatically update implicit
nullable types to explicit nullable types.
One such example is the ``nullable_type_declaration_for_default_null_value``
fixer from [PHP-CS-Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer).

As the ``?T`` syntax has existed since PHP 7.1, which is 7 years old,
we deem version cross compatibility to be a non-issue.

## Version

Next minor version, PHP 8.4.

## Vote

VOTING_SNIPPET

## References

[1] https://externals.io/message/114007#114026
