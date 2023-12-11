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



Note: the behaviour of integer strings used as offsets for arrays being automatically converted to `int` is out of scope of this RFC.



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



### null type as container

PHP supports a feature called auto-vivification to array when writting to an offset when the container is of type ``null``.

Therefore the behaviour depending on the operator is as follows:

- For read operations,`null` is returned, the container continues to be `null`, and the followin warning is emitted:

```
Warning: Trying to access array offset on null
```

- For write, and appending operations the container is converted to array. And thus behave like an array, meaning the behaviour depends on the offset type. Please see the array section for details.

- For read-write operations, the container is converted to array, before the read operation. And thus behave like an array, meaning the behaviour depends on the offset type. Please see the array section for details.

- For the unset operation, the container continues to be `null` and no warning or error is emitted/thrown.

- For existence operations, no warning is emitted and the behaviour is as if the offset did not exist.

### false as container

PHP also supports auto-vivification to array for `false` containers, however this was deprecated in PHP 8.1 LINK TO RFC.



Therefore the behaviour depending on the operator is as follows:

- For read operations,`null` is returned, the container continues to be `false`, and the followin warning is emitted:

```
Warning: Trying to access array offset on false
```

- For write, and appending operations the container is converted to array,
  Emitting the following deprecation notice:
  ```
  Deprecated: Automatic conversion of false to array is deprecated
  ```
  And thus behave like an array, meaning the behaviour depends on the offset type. Please see the array section for details.

- For read-write operations, the container is converted to array, before the read operation,
  Emitting the following deprecation notice:`Deprecated: Automatic conversion of false to array is deprecated`
  And thus behave like an array, meaning the behaviour depends on the offset type. Please see the array section for details.

- For the unset operation, the container continues to be `false` and the following deprecation notice is emitted:
  
  ```
  Deprecated: Automatic conversion of false to array is deprecated
  ```

- For existence operations, no warning is emitted and the behaviour is as if the offset did not exist.



### Arrays

Arrays are the ubiquitious container type in PHP and support all of the operations, thus the behaviour is only affected by the type of offsets used.

#### Valid offsets

Arrays in PHP accepts offstes of either type int or string and in those cases the behaviour is as expected.

One thing to note is that when attempting to read an undefined offset the following warning is emitted:

```
Warning: Undefined array key KEY_NAME
```



#### Offset types cast to int

The following offset types are cast to int silently:

- `false` is cast to 0

- `true` is cast to 1

- Non fractional floating point numbers which fit in an int are cast to their int value

Offsets of type resource are cast to int with the following warning:

```
Warning: Resource ID#%d used as offset, casting to integer (%d)
```

Offsets of type float that are fractional, non-finite, or do not fit in an integer are cast to int with the following deprecation notice:

```
Deprecated: Implicit conversion from float %F to int loses precision
```



#### Offset types cast to string

- ``null`` is cast to an empty string

#### Invalid offsets

The following offset types are invalid offsets types for arrays:

-  arrays

- objects

The behaviour is identical for all operations except existence checks with ``isset()``/``empty()``.

Generally the following error is thrown:

```
Cannot access offset of type TYPE on array
```

For ``isset()`` and ``empty()`` the following error is thrown:

```
Cannot access offset of type TYPE in isset or empty
```



### Strings

TODO it is a mess    

### Internal objects

### Userland classes that implement ArrayAccess

### ArrayObject

## Ideal behaviour

## Migration path

## Version

Next minor version, PHP 8.4, and next major version PHP 9.0.

## Vote

snippet
