# PHP RFC: Deprecate reflection-esque functions from SPL

- Version: 0.1
- Date: 2024-MM-DD
- Author: Gina Peter Banyard <girgias@php.net>
- Status: Under Discussion
- Target Version: PHP 8.5
- Implementation: TBD
- First Published at: https://wiki.php.net/rfc/deprecate-spl-reflection-functions

## Introduction

The SPL extension proposes a few functions that effectively do reflection on
classes and objects.
Those functions are:

- `spl_classes()`, which is effectively equivalent to `(new ReflectionExtension('spl'))->getClassNames()`
- `class_implements('ClassName')`, which is effectively equivalent to `(new ReflectionClass('ClassName'))->getInterfaceNames()`
- `class_uses('ClassName')`, which is effectively equivalent to `(new ReflectionClass('ClassName'))->getTraitNames()`
- `class_parents('ClassName')`, which does not have an equivalent in ext/reflection but can be achieved using a `while` loop and `ReflectionClass::getParentClass()`



## Proposal

We propose to deprecate the above SPL functions in PHP 8.5 and instead redirect users to
use Reflection to achieve the result of these functions.

A secondary proposal is to add the following two methods to `ReflectionClass`:

```php
/** @return list<ReflectionClass> */
public function getParentClasses(): array {}

/** @return list<string> */
public function getParentClassNames(): array {}
```

which follow the existing set of methods:

- `public function getInterfaces(): array {}`
- `public function getInterfaceNames(): array {}`
- `public function getTraits(): array {}`
- `public function getTraitNames(): array {}`

## Backward Incompatible Changes

Using the following functions will raise a deprecation notice

- `spl_classes()`
- `class_implements('ClassName')`
- `class_uses('ClassName')`
- `class_parents('ClassName')`

## Version

Next minor version, PHP 8.5, and next major version PHP 9.0.

## Vote

VOTING_SNIPPET

## Future scope


## References

