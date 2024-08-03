# PHP RFC: New comparison and equality semantics

- Version: 0.1
- Date: 2024-01-09
- Author: Gina Peter Banyard <girgias@php.net>
- Status: Under Discussion
- Target Version: PHP 8.4
- Implementation: [https://github.com/php/php-src/pull/TODO](https://github.com/php/php-src/pull/12500)
- First Published at: [http://wiki.php.net/rfc/comparison-semantics-improvements](http://wiki.php.net/rfc/comparison-semantics-improvements)

## Introduction

PHP is a programing language that will coerce types in various situations, one such situation is comparisons.
Although this is a well known issue within the language and the community has moved towards using identity checks (``===``) instead of equality checks (``==``) to work around this surprising behaviour,
there does not exist inequalities operators (``<``, ``>``, ``<=``, ``>=``) which do not engage in type coercions.

An RFC that didn't proceed was the [`strict_operators` RFC] which would have been similar
to the `strict_type` declare which currently exist in PHP.
It is our opinion that the aforementioned declare has split the PHP ecosystem in two,
and the better approach is to change the current semantics where PHP coerces types
rather than introducing another declare that would create 4 different possible behaviours
depending on which declares are enabled or not.

TODO: Check about transitivity/symmetry/reflexivity
> https://softwareengineering.stackexchange.com/a/318651

TODO Wording around:
https://wiki.php.net/rfc/strict_operators

Greater than/less than operator execution order to check and do

Moreover, the behaviour of both equality and comparison operators can be overloaded by internal classes.
However, it's semantics are unsound as the operators are not polymorphic
(e.g. ``$A == $B`` may not produce the same behaviour as ``$B == $A``).

This RFC will give an overview of the current semantics and behaviour of the comparison/equality operators,
a new set of proposed semantics,
its implication for userland and internal extensions,
a BC impact analysis of the proposal,
and future proposals which are enabled by this RFC.

Note: when we are talking about equating we mean the operators ``==``, ``!=``, and ``<>``,
when the word comparison is used we mean the operators ``<``, ``>``, ``>=``, ``=<``.

Note: The behaviour of 

## Current semantics and behaviour

For the purpose of this RFC we consider there to be ten (10) types:

- `null`
- `false`
- `true`
- `int`
- `float`
- `string`
- `array`
- `resource`
- Objects that do not overload the `compare` object handler
- Internal Objects that overload the `compare` object handler

## Ideal semantics

We think comparisons in PHP should behave according to the following guidelines:

- A proper representation for when a comparison is incomparable exists 
- Comparing incomparable values should throw an error
- `null` should only be equatable, and be equal only against itself
- `false` should only be equatable, and be equal only against itself
- `true` should only be equatable, and be equal only against itself
- Integers should be comparable against integers, floating point numbers, and numeric strings only
- Floating point numbers should be comparable against integers, floating point numbers, and numeric strings only
- Equating two floats should throw an error or warn
- Strings should only be equatable
- Equating two strings should never perform a numerical comparison
- Arrays should only be equatable
- Resources should only be equatable, and never be coerced to an integer
- Internal object should distinguish support for equating them and comparing them
- The behaviour of equating two objects should be identical regardless of operation order
  (i.e. it should be commutative)

We will now provide some motivation for some of those points.

## Proposed semantics

### For next minor version

A proper Incomparable/un-equatable state within the PHP engine to be able to emit warnings, `UncomparableError`s, and polymorphic comparison.

Comparisons are only valid on numeric values (i.e. ``int``, ``float`` and numeric strings), and for internal objects implementing a compare handler. String comparisons can be replaced by using the ``strcmp()`` function. Array comparisons should be replaced by a custom compare function. `null`, `false`, `true`, and resources are uncomparable.

Equating two strings will always perform the comparison as strings.

``null``, ``false``, and ``true`` are only equatable with themselves and do not cause the other operand or themselves to be type juggled.
For boolean comparisons a straight ``if ($value) {}`` or ``if (!$value) {}`` can be used.

floats TODO should make it a warning to compared two floats together with

Comparing two objects 

- Compile time warning if ``null``, ``false``, or ``true`` is used as a constant operand for ``==``/``!=``/``<>``.

- Runtime warning if ``null`` is equated with a non-null value that currently returns an equal result.

- Runtime warning if  a value of type `bool` is equated with a non-boolean value that currently returns an equal result.

- Incomparable runtime warning when comparing booleans
- Incomparable runtime warning when comparing a boolean to any other type
- Runtime warning when equating two strings which are equated numerically
- Incomparable Runtime warning when comparing two strings
- Incomparable runtime warning when comparing arrays or an array to any other type.
- Incomparable runtime warning when comparing resources.
- Incomparable runtime warning when comparing two incomparable objects
- Incomparable runtime warning when comparing an object to any other type

### For next major version

- Any incomparable warning emitted would be converted to throw a ``IncomparableError``.

- Equating two strings will always perform the comparison as string and never perform the comparison numerically,
  even if both strings are numeric. TODO Polyfill

- ``null`` is only equatable with itself
- ``true`` is only equatable with itself
- ``false`` is only equatable with itself
  
  

## Userland and Internal implication

### Internals

Extensions that declare objects which support equatable or comparable objects will need to be modified to follow the new returned typed of the compare handler or implement the equatable handler instead.

Any call to ``zend_compare()`` will be affected

Add Comparison and Equal enumerations

```php
enum Comparison {
    case Equal;
    case LeftGreater;
    case RightGreater;
    case Incomparable;
}
```

```php
enum Equal {
    case Equal;
    case NotEqual;
    case Incomparable;
}
```

### Engine

New opcode for ``>`` / ``>=``

### Userland

New ``fcmp(float $left, float $right, float $epsilon = EPSILON): Comparison`` function
to compare two floating point numbers

## BC impact analysis

TODO

## Future proposals enabled by this RFC

### Userland object comparisons

Support for two interfaces ``Equatable`` for objects that can be considered equal and ``Comparable`` for objects that can be compared,
those would mimic the new internal object handlers.

Note adding support for userland object comparisons is a pre-requisite for a range operator ``^..^``/``^..``/``..^``/``..`` to support objects.

### Spaceship operator to return Incomparable as a value <=>

By using the new ComparisonEnum, BC compatible for userland compare callbacks to use ``int|ComparisonEnum`` return type.
Only possible in the next major version.

# Version

Next minor version, PHP 8.4, and next major version PHP 9.0.

# Vote

VOTING_SNIPPET
