# PHP RFC: Change behaviour of array sort functions to return a copy of the sorted array

- Version: 0.1
- Date: 2024-10-20
- Author: Gina Peter Banyard <girgias@php.net>
- Status: Under Discussion
- Target Version: PHP 8.5
- Implementation: TBD
- First Published at: https://wiki.php.net/rfc/array-sort-return-array

## Introduction

The PHP array sort functions take the array to sort as a by-reference array to sort the array in place.
However, since PHP 5.6 these function have only ever returned `true`.

Moreover, it would only return `false` (or `null`) prior to this version in case of a memory allocation failure.
This effectively means the return values of these functions are useless.
Similarly, `array_walk()`, `array_walk_recursive()`, and `shuffle()` have only ever returned `true`
(or in case of type errors `null` or `false` which are proper `TypeError`s as of PHP 8.0) since at least PHP 5.3.
One last array function that now only returns `true` since PHP 8.0 is `array_multisort()`,
when all warnings and false returns were converted to `ValueError`s and `TypeError`s.

This behaviour is annoying as it makes writing code in a functional way more cumbersome and tedious.
Indeed, when working with higher order functions (e.g. `array_map()`),
one cannot just use these functions as callbacks
and must instead wrap them in a closure which returns the sorted array.

## Proposal

Change the return value of:

- `sort()`
- `rsort()`
- `asort()`
- `arsort()`
- `ksort()`
- `krsort()`
- `natsort()`
- `natcasesort()`
- `usort()`
- `uasort()`
- `uksort()`
- `array_multisort()`
- `shuffle()`
- `array_walk()`
- `array_walk_recursive()`

from `true` to a copy of the `array` that is sorted, traversed, or shuffled.

This operation is effectively "for free" as we just increment the reference count of the sorted array by one.

## Backward Incompatible Changes

Sorting an empty array and checking the return value of one of the `sort()` functions
would now be falsy rather than `true`.

## Unaffected PHP Functionality

The functions continue to take the first parameter by-reference and do the sorting in-place.

The sort methods from the SPL `ArrayObject` class are not affected by this change.

## Version

Next minor version, PHP 8.5.

## Vote

VOTING_SNIPPET

## References
