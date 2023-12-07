# PHP RFC: Improve language coherence for the behaviour between offsets and containers

- Version: 0.1
- Date: 2023-12-07
- Author: Gina Peter Banyard <girgias@php.net>
- Status: Under Discussion
- Target Version: PHP 8.4
- Implementation: [https://github.com/php/php-src/pull/7173](https://github.com/php/php-src/pull/7173)
- First Published at: [http://wiki.php.net/rfc/container-offset-behaviour](http://wiki.php.net/rfc/container-offset-behaviour)

## Introduction

PHP supports accessing sub-elements of a type via an offset using brackets ``[]`` with the following notation ``$container[$offset]``. However, the behaviour of such access depends on the type of the container, the type of the offset, and the type of the operation. This behaviour is also highly inconsistent and difficult to anticipate.

The objectives of this RFC is to explain the current complicated behaviour, behaviour that we deem consistent and easy to reason about, and intermediate steps to go from the current behaviour to the desired targed behaviour.

We consider there to be seven (7) different operations that relate to containers and offsets, which are the following:

- Read

- Write

- Read-Write

- Appending, via the ``$container[] = $value`` syntax

- Unsetting

- Existence checks via `isset()` and/or ``exists()``

- Existence checks via the null coalesce operator ``??``

The reason for splitting the existence check operation into two distinct operations is that the behaviour sometimes differ between using ``iseet()``/``empty()`` and ``??``.

We consider there to exist thirten (13) different types of containers:

- null

- false

- true

- integers

- floating point numbers

- resources

- strings

- arrays

- Userland objects that do *not* implement ``ArrayAccess``

- Userland objects that implement `ArrayAccess`

- Internal objects that implement none of the following object handlers: ``read_dimension``, ``write_dimension``, ``has_dimension``, and ``unset_dimension``

- Internal objects that implement at least one, but not all of the following object handlers: `read_dimension`, `write_dimension`, `has_dimension`, or `unset_dimension``

- Internal objects that implement all of the following object handlers: `read_dimension`, `write_dimension`, `has_dimension`, and `unset_dimension`

- ``ArrayObject`` as its behaviour is rather peculiar

Finally, we consider there to exist the standard eight (8) built-in types in PHP for offsets, namely:

- null

- booleans

- integers

- floating point numbers

- resources

- strings

- arrays

- objects

## Current behaviour

Considering the large possible combination of containers, offsets, and operations we will start by explaining the current behaviour of certain categories of containers.

### Invalid container types

This sections covers a large section of types when used as a container, as this usage is invalid.

#### "Scalar" types

For the purpose of this section, ``true``, integers, floating point numbers, and resources are considered to be a "scalar" types, as the engine treets those container types identically.

- For read operations,`null` is returned and the followin warning is emitted:

```
Warning: Trying to access array offset on TYPE
```

- For write, read-write, and appending operations, the following error is thrown:

```
Cannot use a scalar value as an array
```

- For the unset operation, the following error is thrown:

```
Cannot unset offset in a non-array variable
```

- For existence operations, no warning is emitted and the behaviour is as if the offset did not exist.



#### Classes that do not implement ArrayAccess and Internal objects which do not implement any dimension object handler

For every single operation, regardless of the type of the offset, the following ``Error`` is emitted:

```
Cannot use object of type ClassName as array
```



### Container types that auto-vivify to array

Both `null` and `false` will be automatically converted to an array when trying to write to an offset. This behaviour for ``false`` has been deprecated in RFC LINK



TODO DESCRIBE IN DETAILS THE WARNINGS



### Arrays

### Strings

### Internal objects

### Userland classes that implement ArrayAccess

### ArrayObject

## Ideal behaviour

## Migration path

## Version

Next minor version, PHP 8.4, and next major version PHP 9.0.

## Vote

snippet
