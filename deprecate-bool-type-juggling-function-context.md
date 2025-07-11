# PHP RFC: Deprecate type juggling to and from bool type within the function type juggling context

- Version: 0.2
- Date: 2025-06-02
- Author: Gina Peter Banyard <girgias@php.net>
- Status: Under Discussion
- Target Version: PHP 8.5
- Implementation: https://github.com/php/php-src/pull/18879
- First Published at: https://wiki.php.net/rfc/deprecate-function-bool-type-juggling

## Introduction

PHP has a few type juggling contexts, which are described on the
[Type Juggling](https://www.php.net/manual/en/language.types.type-juggling.php)
documentation page.
Currently, there are 6:

- Numeric, for arithmetic operators
- String, for `echo`, `print`, string interpolation, and string concatenation
- Logical, for conditional statements and logical operators
- Integral and string, for bitwise operators
- Comparative, for comparison operators
- Function, for arguments and return values of functions and typed properties

As this RFC is about amending the semantics of the function type juggling context,
we will describe it in full.

As said previously, the function type juggling context refers to type coercions that occur
when passing a value to a function/method argument, returning a value from
a function with a return type, and assigning a value to a typed property.

In this context only scalar (`int`, `float`, `string`, and `bool`) types
can be coerced to one of the other scalar types,
with an additional possibility for `Stringable` objects to be type juggled for a `string` type.

Moreover, it is possible to prevent coercion of scalar types (except for widening of `int` to `float`)
in this context altogether by using the
[`strict_types=1`](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.strict)
declare statement in a file.

However, the singleton types `true` and `false` do not permit the coercion of other scalar types to them.
Meaning that returning `0` from a function that declares a return type of `array|false` causes a `TypeError` to be thrown.
This behaviour happens even if the `strict_types` declare is not used.

## History and prior discussions

The behaviour of type juggling to and from `bool` has been discussed various times.
First in the controversial
[Coercive Types for Function Arguments](https://wiki.php.net/rfc/coercive_sth)
RFC from Zend Technologies et al. in 2015 which was "competing" with the - accepted -
[Scalar Type Declarations (v5)](https://wiki.php.net/rfc/scalar_type_hints_v5)
RFC from Anthony Ferrara (based on prior work from Andrea Faulds).
The RFC from Zend Technologies et al. proposed to ban type juggling from `float` to `bool`. [1]
We could not determine what led to this choice in both discussion threads, [2], [3]
however, the reply from Pierre Joye [4] may be part of the reason.

Currently, only the `float` values `-0.0` and `0.0` are implicitly converted to `false`.
This means `NAN` is converted to `true`,
which leads to different behaviour when `NAN` is first cast to `int` and then to `bool`
(as `(int) NAN === 0`, which is converted to `false`).
It is also common to deal with floating point numbers that are _close_ to `0` but not exactly zero,
which compounded with the _generally_ known fact that comparing floats is tricky, [5]
calls into question how sensible it is to allow implicit conversions from `float` to `bool`.

Type juggling of strings to `bool` is similarly error-prone. The strings `""` and `"0"`
are converted to `false`, but `"false"`, `" "`, and other strings which an `(int)` cast converts to `0`
are coerced to `true`.
This contrasts to many, if not all, other programming languages where only the empty string `""` is falsy,
which can be a point of confusion.

In 2020, this topic was briefly approached by Nikita Popov's
[Union Types 2.0](https://wiki.php.net/rfc/union_types_v2)
RFC, which laid down the behavioural semantics of scalar union types in coercive typing mode.
It also excluded implicit coercion to the singleton types `null` and `false`.
The behaviour can be seen in the
[Conversion Table](https://wiki.php.net/rfc/union_types_v2#conversion_table)
where `bool` will convert to any available scalar type, and `int`, `float`, `string`
will convert to another scalar type if it is part of the union type before defaulting to `bool`.

In 2021, the proposal to
[Deprecate boolean to string coercion](https://wiki.php.net/rfc/deprecate-boolean-string-coercion)
was brought to internals by Ilija Tovilo and me.
This proposal suggested deprecating implicit coercions from `bool` to `string`.
Those can only happen in two type juggling contexts: the function one, and the string one
(the latter referring `echo`, `print`, string concatenation, and string interpolation as said in the introduction).
As the impact from deprecating `bool` to `string` conversion was high,
notably within php-src's own test suite which `echo`s many boolean values,
we did not bring this proposal to a vote.

In 2022, we proposed to add the
[true singleton type](https://wiki.php.net/rfc/true-type)
to PHP. Part of this proposal was to emit a compile time error if the union type
`true|false` (or the reverse) is used instead of `bool`.
This is because both the `true` and `false` singleton types do not permit type juggling,
but the `bool` type does.

Later in 2022, the
[Stricter implicit boolean coercions](https://wiki.php.net/rfc/stricter_implicit_boolean_coercions)
RFC was proposed, and ultimately declined.
This RFC proposed to _narrow_ the set of valid integers, floating point numbers, and strings
that would be coerced to `bool` in the function type juggling context.
This narrowing was designed to be consistent with `bool` being type juggled to the other scalar types,
while still providing better type safety and data loss prevention.
However, the set of accepted values can be considered "arbitrary" as the filter extension,
and values for INI settings accept a wider set of values.

## Motivation

In our opinion, boolean values being coerced to one of the other scalar types is indicative of a bug in the code.
This is especially true in PHP where _many_ functions return `false` on failure.
We even found bugs in php-src's test suite while implementing this RFC
where `false` was coerced into various scalar types. [7],[8]

Similarly, as seen in the previous section,
coercing `string` and `float` values to `bool` is somewhat dubious in nature
as these values are usually handled with different logic depending on the domain,
and it can also hide bugs, which was the case in some php-src tests. [9]

Therefore, the only reasonable coercion in our opinion is from `int` to `bool`.
Nonetheless, we believe that deprecating implicit coercions from `int` to `bool`
is something we should pursue for consistency with the rest of the proposal,
it would mean the type declaration `true|false` would be identical to `bool`,
and it is still likely to point to a bug.
Even if it is common, especially within php-src's test suite, to use `0` and `1` as `false` and `true` respectively.

The final motivation is that this change is, in our opinion,
the last remaining hurdle for a potential proposal to unify PHP's typing modes. [6]
The problems with PHP's split typing modes are somewhat well-known, but we will repeat some of them here:

- Too strict may lead to too lax
  - Perceived need for "strict casts"
  - The `Stringable` interface was added to allow objects which implement `__toString()` to still be allowed in functions wanting a string
- Closures/callables follow the typing mode in which they were declared, and not the typing mode where they are used
  - An important consequence of this is that any Closure called by the engine is done in weak mode
- Limited scope of the `declare(strict_types=1)` statement
  - Users don't know what it does 
  - Follow-up proposals to add more to restrict type juggling in different contexts [10]
- Potential complications with moving the implementation of C functions into plain PHP

## Counter-arguments

A common counter-argument to the deprecation of implicit coercions from scalars to bool
is when dealing with external inputs, such a `$_GET` and `$_POST`,
is that the content of the input does not matter and one only cares if it is truthy or falsy.
And that this proposal forces the use of `(bool)` cast when not required.
However, in general the `(bool)` cast *is* required even when `strict_types` is not used.
It is not possible to trust external inputs, especially if they come from `$_GET` or `$_POST`
as they may *not* contain a scalar value.
Indeed, PHP has this convenient feature which allows an `array` to be passed via the query parameters e.g.
`example.com?colors[]=red&colors[]=blue` gives us `$_GET['colors']` as an array of two elements
`['red', 'blue']` or via a form in `$_POST`:
```php
<form enctype="multipart/form-data" action="submit.php" method="POST">
    <input name="languages[main]">
    <input name="languages[secondary]">
    <input name="languages[fallback]">
</form>
```
which would give us the following `$_POST` array:
```php
$_POST = [
    'languages' => [
        'main' => 'value1',
        'secondary' => 'value2',
        'fallback' => 'value3',
    ]
] 
```

Another counter-argument is that unifying typing modes is a futile exercise because it causes unnecessary disruption
for those wanting the weak mode semantics, and people wanting the strict mode semantics are unwilling to compromise.
However, users that are unwilling to compromise would be using static analysis tools that are _stricter_ than the strict type mode.
These tools were not widely developed 10 years ago when PHP 7.0 was released.
And in our experience seeing the impact of the RFC on php-src and Symfony, implicit coercion to/and from bool
(except `int` to `bool`) "almost always" hides a bug in the code.

## Proposal

We propose to deprecate type coercions to and from `bool` in the function type juggling context.

The long-term benefits of this proposal are the following:

- The union type `true|false` is identical to `bool`
- Potential unification of PHP's typing modes [6]
- Engine simplification

This also means that in PHP 9, implicit coercion of scalar values will
choose the target type in the following order of preference:
1. `int`
2. `float`
3. `string`

Rather than the current order of:
1. `int`
2. `float`
3. `string`
4. `bool`

## Backward Incompatible Changes

Implicit type coercions to and from `bool` will emit a deprecation notice in PHP 8.5,
and support for it removed in PHP 9.0.

Some examples of function signatures which would cause deprecation notices to be emitted if `true` or `false` is
passed to them:

```php
function example1(?int $v) {}
function example2(?string $v) {}
function example3(?float $v) {}
function example4(int|string $v) {}
function example5(int|float $v) {}
function example6(float|string $v) {}
function example7(int|float|string $v) {}
```

## Unaffected PHP Functionality

- Type juggling for logical operators (e.g. `&&`, `||`) is not affected nor changed.
- Type juggling for `echo`, `print`, string concatenation, and string interpolation is not affected nor changed.
- Type juggling for arithmetic operators (e.g. `+`, `/`) is not affected nor changed.
- Type juggling for array keys is not affected nor changed.
- Type juggling for bitwise operators (e.g. `&`, `|`) is not affected nor changed.
- Type juggling for comparison operators (e.g. `==`, `>`) is not affected nor changed.

## Version

Next minor version, PHP 8.5, and next major version, PHP 9.0.

## Vote

VOTING_SNIPPET

## Future scope

These are relevant topics, which may be addressed in other RFCs:

- Unify PHP's typing modes [6]
- Deprecate `bool` to `string` implicit type conversions in the String Type Juggling Context
- Deprecate `int` to `float` implicit type conversions in the Function Type Juggling Context when loss of precision occurs
- Deprecate `float` to `string` implicit type conversions in the Function Type Juggling Context
- Deprecate `NAN` being cast to another type.
- Change array offsets to use the semantics of the function type juggling mode

## References

[1]: https://wiki.php.net/rfc/coercive_sth#coercion_rules ("Coercion Rules" section of the RFC)

[2]: https://externals.io/message/83405 (externals.io link to the mailing list thread "Coercive Scalar Type Hints RFC")

[3]: https://externals.io/message/84559 (externals.io link to the mailing list thread "\[VOTE]\[RFC] Coercive Scalar Type Hints")

[4]: https://externals.io/message/83405#83407 (externals.io link to Pierre Joye reply on the mailing list "Coercive Scalar Type Hints RFC" thread)

[5]: https://randomascii.wordpress.com/2012/02/25/comparing-floating-point-numbers-2012-edition/ (Comparing Floating Point Numbers, 2012 Edition, Posted on February 25, 2012 by brucedawson)

[6]: https://github.com/Girgias/unify-typing-modes-rfc ("Unify PHP's typing modes \(aka remove strict_types declare\)" Meta RFC draft)

[7]: https://github.com/php/php-src/pull/18891 ("ext/date: Remove implicit bool type coercions in tests" GitHub PR)

[8]: https://github.com/php/php-src/commit/0ab5f70b3cc9705873586657f9910a7dd7d466f4#diff-8e6160f67a736edea82a97e96f05126baf60b9f3ec704ba71fad0ff585cb13a0 (Diff of file `ext/spl/tests/bug36287.phpt` from php-src commit "ext/spl: Remove bool type coercions in tests")

[9]: https://github.com/php/php-src/commit/5bd18e3fdc1abdedd5c418095fd8a41f77bae146#diff-d3729e7ef900aea0d9fb54384139cf1507e1baab5dd7d69381bc4ba14e8e5b24 (Diff of file `ext/zlib/tests/gh16883.phpt` from php-src commit "ext/zlib: Refactor tests \(#18887\)")

[10]: https://wiki.php.net/rfc/strict_operators (Strict operators directive RFC)

1. "Coercion Rules" section of the RFC https://wiki.php.net/rfc/coercive_sth#coercion_rules
2. externals.io link to the mailing list thread "Coercive Scalar Type Hints RFC": https://externals.io/message/83405
3. externals.io link to the mailing list thread "\[VOTE]\[RFC] Coercive Scalar Type Hints": https://externals.io/message/84559
4. externals.io link to Pierre Joye reply on the mailing list "Coercive Scalar Type Hints RFC" thread: https://externals.io/message/83405#83407
5. Comparing Floating Point Numbers, 2012 Edition, Posted on February 25, 2012 by brucedawson: https://randomascii.wordpress.com/2012/02/25/comparing-floating-point-numbers-2012-edition/
6. "Unify PHP's typing modes (aka remove strict_types declare)" Meta RFC draft: https://github.com/Girgias/unify-typing-modes-rfc
7. "ext/date: Remove implicit bool type coercions in tests" GitHub PR: https://github.com/php/php-src/pull/18891
8. Diff of file `ext/spl/tests/bug36287.phpt` from php-src commit "ext/spl: Remove bool type coercions in tests": https://github.com/php/php-src/commit/0ab5f70b3cc9705873586657f9910a7dd7d466f4#diff-8e6160f67a736edea82a97e96f05126baf60b9f3ec704ba71fad0ff585cb13a0
9. Diff of file `ext/zlib/tests/gh16883.phpt` from php-src commit "ext/zlib: Refactor tests (#18887)": https://github.com/php/php-src/commit/5bd18e3fdc1abdedd5c418095fd8a41f77bae146#diff-d3729e7ef900aea0d9fb54384139cf1507e1baab5dd7d69381bc4ba14e8e5b24
10. Strict operators directive RFC: https://wiki.php.net/rfc/strict_operators
