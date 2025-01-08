# PHP RFC: Deprecate type juggling to and from bool type within the function type juggling context

- Version: 0.1
- Date: 2025-01-DD
- Author: Gina Peter Banyard <girgias@php.net>
- Status: Under Discussion
- Target Version: PHP 8.5
- Implementation: TBD
- First Published at: https://wiki.php.net/rfc/SLUG-FOR-RFC-TITLE

## Introduction

PHP has a few type juggling contexts which are described on the
[Type Juggling](https://www.php.net/manual/en/language.types.type-juggling.php)
documentation page.
The function type juggling context refers to the type coercion that occurs
when passing a value to a function/method argument, returning a value from
a function/argument with a return type, and assigning a value to a typed property.

In this context only scalar (`int`, `float`, `string`, and `bool`) types can be type juggled.
However, type juggling to the singleton types `true` and `false` is not possible. 
Moreover, it is possible to prevent coercion of scalar types (except for widening of `int` to `float`)
in this context altogether by using the
[`strict_types=1`](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.strict)
declare statement in a file.

The behaviour of type juggling to and from `bool` has been discussed various times.
First in the controversial
[Coercive Types for Function Arguments](https://wiki.php.net/rfc/coercive_sth)
RFC from Zend Technologies et al. in 2015 which was "competing" with the - accepted -
[Scalar Type Declarations (v5)](https://wiki.php.net/rfc/scalar_type_hints_v5)
RFC from Anthony Ferrara (based on prior work from Andrea Faulds).
The RFC from Zend Technologies et al. proposed to ban type juggling from `float` to `bool`. [1]
We could not determine what lead to this choice in both discussion threads, [2] [3]
however the reply from Pierre Joye [4] may be part of the reason.

Currently only the `float` values `-0.0` and `0.0` are implicitly converted to `false`.
This means `NAN` is converted to `true`,
which leads to different behaviour when `NAN` is first cast to `int` and then to `bool`
(as `(int) NAN === 0`, which is converted to `false`).
It is also common to deal with floating point numbers that are _close_ to `0` but not exactly zero,
which compounded with the _generally_ known fact that comparing floats is tricky, [5]
calls into question how sensible it is to allow implicit conversions from `float` to `bool`.

Type juggling of strings to `bool` is similarly error-prone. The strings `""` and `"0"`
are converted to `false`, but `"false"`, `" "`, and other strings which an `(int)` cast converts to `0`
are coerced to `true`.

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
was brought to internals by Ilija Tovilo and myself.
This proposal suggested deprecating implicit coercions from `bool` to `string`.
Those can only happen in two type juggling contexts: the function one, and the string one
(the latter refers to displaying output via `echo` or `print`, string concatenation, and string interpolation).
As the impact from deprecating `bool` to `string` conversion was high, notably within php-src's own test suite,
we did not bring this proposal to a vote.

In 2022, we proposed to add the
[true singleton type](https://wiki.php.net/rfc/true-type)
to PHP. Part of this proposal was to emit a compile time error if the union type
`true|false` (or the reverse) is used instead of `bool`.
This is because both the `true` and `false` singleton types do not allow type juggling,
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

## Proposal

We propose to deprecate type coercions to and from `bool` in the
function type juggling context.
Providing a boolean value to a parameter/property of a different type very likely points to a programming bug.
And as we have seen, coercing `string` and `float` values to `bool` is somewhat dubious in nature
since these values are usually handled with different logic in different domains.
The only implicit coercion that makes sense most of the time is the one from `int` to `bool`,
but for consistency's sake we believe this should also be deprecated.

The long term benefits of this proposal are the following:

- The union type `true|false` is identical to `bool`
- A potential unification of PHP's typing modes [6]
- Engine simplification

// TODO Show how it impacts conversion table for union types

## Backward Incompatible Changes

## Version

Next minor version, PHP 8.5, and next major version PHP 9.0.

## Vote

VOTING_SNIPPET

## Future scope

- Unify PHP's typing modes
- Deprecate `bool` to `string` implicit type conversions in the String Type Juggling Context
- Deprecate `NAN` being cast to another type. 

## References

[1] "Coercion Rules" section of the RFC https://wiki.php.net/rfc/coercive_sth#coercion_rules

[2] externals.io link to the mailing list thread "Coercive Scalar Type Hints RFC": https://externals.io/message/83405

[3] externals.io link to the mailing list thread "[VOTE][RFC] Coercive Scalar Type Hints": https://externals.io/message/84559

[4] externals.io link to Pierre Joye reply on the mailing list "Coercive Scalar Type Hints RFC" thread: https://externals.io/message/83405#83407

[5] Comparing Floating Point Numbers, 2012 Edition, Posted on February 25, 2012 by brucedawson: https://randomascii.wordpress.com/2012/02/25/comparing-floating-point-numbers-2012-edition/

[6] "Unify PHP's typing modes (aka remove strict_types declare)" Meta RFC draft: https://github.com/Girgias/unify-typing-modes-rfc
