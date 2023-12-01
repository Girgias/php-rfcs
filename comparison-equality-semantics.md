# PHP RFC: New comparison and equality semantics

1555 start 1725 end 1h30 total



PHP is a programing language that will coerce types in various situations, one such situation is comparisons. Although this is a well known issue within the language and the community has moved towards using identity checks (``===``) instead of equality checks (``==``) to work around this surprising behaviour, there does not exist comparison operators (``<``, ``>``, ``<=``, ``>=``) which do not engage in type coercions.

https://wiki.php.net/rfc/strict_operators



Great than/less than operator execution order to check and do

Moreover, the behaviour of both equality and comparison operators can be overloaded by internal classes. However, it's semantics are unsound as the operators are not polymorfic (e.g. ``$A == $B`` may not produce the same behaviour as ``$B == $A``).

This RFC will give an overview of the current semantics and behaviour of the comparison/equality operators, a new set of proposed semantics, its implication for userland and internal extensions, a BC impact analysis of the proposal, and future proposals which are enabled by this RFC.



Note: when we are talking about equating we mean the operators ``==``, ``!=``, and ``<>``, when the word comparison, compare is used we mean the operators ``<``, ``>``, ``>=``, ``=<``.



## Current semantics and behaviour





## Proposed semantics

### For next minor version

A proper uncomparable/unequatable state within the PHP engine to be able to emit warnings, UncomparableErrors, and polymorphic comparison.

Comparisons are only valid on numeric values (i.e. ``int``, ``float`` and numeric strings), and for internal objects implementing a compare handler. String comparisons can be replaced by using the ``strcmp()`` function. Array comparisons should be replace by a custom compare function. `null`, `false`, `true`, and resources are uncomparable.

Equating two strings will always perform the comparison as strings.

``null``, ``false``, and ``true`` are only equatable with themselves and do not cause the other operand or themself to be type juggled. For boolean comparisons a straight ``if ($value) {}`` or ``if (!$value) {}`` can be used.

floats TODO should make it a warning to compared two floats together with

Comparing two objects 

- Compile time warning if ``null``, ``false``, or ``true`` is used as a constant operand for ``==``/``!=``/``<>``.

- Runtime warning if ``null`` is equated with a non-null value that currently returns an equal result.

- Runtime warning if  a value of type `bool` is equated with a non-boolan value that currently returns an equal result.

- Uncomparable runtime warning when comparing booleans

- Uncomparable runtime warning when comparing a boolean to any other type

- Runtime warning when equating two strings which are equated numerically

- Uncomparable Runtime warning when comparing two strings

- Uncomparable runtime warning when comparing arrays or an array to any other type.

- Uncomparable runtime warning when comparing resources.

- Uncomparable runtime warning when comparing two uncomparable objects

- Uncomparable runtime warning when comparing an object to any other type

### For next major version

- Any uncomparable warning emitted would be converted to throw a ``UncomparableError``.

- Equating two strings will always perform the comparison as string and never perform the comparison numerically, even if both strings are numeric. TODO Polyfill

- ``null`` is only equatable with itself

- ``true`` is only equatable with itself

- ``false`` is only equatable with itself

- 



## Userland and Internal implication

### Userland

### Internals

Extensions that declare objects which support equatable or comparable objects will need to be modified to follow the new returned typed of the compare handler or implement the equatable handler instead.

Any call to ``zend_compare()`` will be affected

Add ComparisonEnum

```php
enum Comparison {
    case Equal;
    case LeftGreater;
    case RightGreater;
    case Uncomparable;
}
```

New ``fcmp(float $left, float $right, float $epsilon = EPSILON)`` function

### Engine

New opcode for ``>`` / ``>=`` 



## BC impact analysis



## Future proposals enabled by this RFC

### Userland object comparisons

Support for two interfaces ``Equatable`` for objects that can be considered equal and ``Comparable`` for objects that can be compared, those would mimic the new internal object handlers.

Note adding support for userland object comparisons is a pre-requisite for a range operator ``^..^``/``^..``/``..^``/``..`` to support objects.

### Spaceship operator to return Uncomparable as a value <=>

By using the new ComparisonEnum, BC compatible for userland compare callbacks to use ``int|ComparisonEnum`` return type. Only in next major version.



# Version

Next minor version, PHP 8.4, and next major version PHP 9.0.

# Vote

snippet
