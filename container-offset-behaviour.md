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

The objectives of this RFC is to explain the current complicated behaviour,
behaviour that we deem consistent and easy to reason about,
and intermediate steps to go from the current behaviour to the desired target behaviour.

We consider there to be seven (7) different operations that relate to containers and offsets,
which are the following:

- Read
- Write
- Read-Write
- Appending, via the ``$container[] = $value`` syntax
- Unsetting
- Existence checks via `isset()` and/or ``exists()``
- Existence checks via the null coalesce operator ``??``

The reason for splitting the existence check operation into two distinct operations is that the behaviour sometimes differ between using ``iseet()``/``empty()`` and ``??``.

It should be noted that these operations can also be "nested" (e.g. `$container[$offset1][$offset2]`),
where one peculiar operation is possible, an appending fetch in a write/appending operation `$container[][$offset] = $value`.
In general, a nested operation will perform all the necessary read operations,
interpreting the returned value as a container, until it reaches the final dimension.

We consider there to exist thirteen (13) different types of containers:

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
- Internal objects that override none of the following object handlers: ``read_dimension``, ``write_dimension``, ``has_dimension``, and ``unset_dimension``
- Internal objects that override at least one, but not all the following object handlers: `read_dimension`, `write_dimension`, `has_dimension`, or `unset_dimension`
- Internal objects that override all the following object handlers: `read_dimension`, `write_dimension`, `has_dimension`, and `unset_dimension`
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

Note: the behaviour of integer strings used as offsets for arrays being automatically converted to `int` is out of scope for this RFC.


## Current behaviour

Considering the large possible combination of containers, offsets, and operations we will start by explaining the current behaviour of certain categories of containers.

### Invalid container types

This sections covers a large section of types when used as a container, as this usage is invalid.

#### "Scalar" types

For the purpose of this section,
``true``, integers, floating point numbers,
and resources are considered to be a "scalar" types,
as the engine treats those container types identically.

- For read operations, `null` is returned and the following warning is emitted:
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

For every single operation, regardless of the type of the offset, the following ``Error`` is thrown:
```
Cannot use object of type ClassName as array
```


### null type as container

PHP supports a feature called auto-vivification to array when writing to an offset when the container is of type ``null``.

Therefore, the behaviour depending on the operator is as follows:

- For read operations,`null` is returned, the container continues to be `null`, and the followin warning is emitted:
  ```
  Warning: Trying to access array offset on null
  ```
- For write, and appending operations the container is converted to array.
  And thus behave like an array, meaning the behaviour depends on the offset type.
  Please see the array section for details.
- For read-write operations, the container is converted to array,
  before the read operation.
  And thus behave like an array, meaning the behaviour depends on the offset type.
  Please see the array section for details.
- For the unset operation, the container continues to be `null`
  and no warning or error is emitted/thrown.
- For existence operations, no warning is emitted
  and the behaviour is as if the offset did not exist.

### false as container

PHP also supports auto-vivification to array for `false` containers,
however this was
[deprecated in PHP 8.1](https://wiki.php.net/rfc/autovivification_false).

Therefore, the behaviour depending on the operator is as follows:
- For read operations,`null` is returned,
   the container continues to be `false`, and the following warning is emitted:
   ```
   Warning: Trying to access array offset on false
   ```

- For write, and appending operations the container is converted to array,
  Emitting the following deprecation notice:
  ```
  Deprecated: Automatic conversion of false to array is deprecated
  ```
  And thus behave like an array, meaning the behaviour depends on the offset type.
  Please see the array section for details.

- For read-write operations, the container is converted to array, before the read operation,
  Emitting the following deprecation notice:
  ```
  Deprecated: Automatic conversion of false to array is deprecated
  ```
  And thus behave like an array, meaning the behaviour depends on the offset type.
  Please see the array section for details.

- For the unset operation, the container continues to be `false`
  and the following deprecation notice is emitted:
  ```
  Deprecated: Automatic conversion of false to array is deprecated
  ```

- For existence operations, no warning is emitted and the behaviour is as if the offset did not exist.



### Arrays

Arrays are the ubiquitous container type in PHP and support all the operations, thus the behaviour is only affected by the type of offsets used.

#### Valid offsets

Arrays in PHP accepts offsets of either type int or string and in those cases the behaviour is as expected.

One thing to note is that when attempting to read an undefined offset the following warning is emitted:

```
Warning: Undefined array key KEY_NAME
```


#### Offset types cast to int

The following offset types are cast to int silently:

- `false` is cast to 0
- `true` is cast to 1
- Non-fractional floating point numbers which fit in an int are cast to their int value

Offsets of type resource are cast to int with the following warning:
```
Warning: Resource ID#%d used as offset, casting to integer (%d)
```

Offsets of type float that are fractional, non-finite,
or do not fit in an integer are cast to int with the following deprecation notice:
```
Deprecated: Implicit conversion from float %F to int loses precision
```


#### Offset types cast to string

- ``null`` is cast to an empty string

#### Invalid offsets

The following offset types are invalid offsets types for arrays:

- arrays
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

Strings in PHP are effectively byte-arrays,
as such the only valid type of offsets are integers.
However, the behaviour in regard to string offsets is extremely inconsistent and complicated.
To showcase the current behaviour we will explain the behaviour by going through each different offset type.

Moreover, some operations are invalid on string offsets:
 - Attempting to use a Read-Write operations on a string offset will throw the following error:
   ```
   Cannot use assign-op operators with string offsets
   ```
 - Attempting to unset a string offset will throw the following error:
   ```
   Cannot unset string offsets
   ```
 - Attempting to append to a string will throw the following error:
   ```
   [] operator not supported for strings
   ```
 - Nested operations, except nested reads, on a string will throw the following error:
   ```
   Cannot use string offset as an array
   ```
   But only *after* it has checked the type of the offset from the nested dimension.

Attempting to read a non initialized string offset emits the following warning:
```
Warning: Uninitialized string offset INTEGER
```

Finally, attempting to write more than one byte to a string offset will emit the following warning:
```
Warning: Only the first byte will be assigned to the string offset
```

#### Integer offsets

Integers are the only valid offset type,
however, some integers values remain invalid offsets.

Indeed, a negative offset can be outside the range of valid string offsets.
Negative offsets start counting from the end of the string,
if the absolute value of the offset is greater than ``strlen($string)``
it implies that the negative offset points to a byte before the first byte of the string,
thus being invalid, when attempting to perform a write operation in such cases
the following warning is emitted:
```
Warning: Illegal string offset %s
```

#### Offset types that warn about being cast to int

The following types `null`, `false`, `true`, and floating point numbers have very simple behaviour,
they simply emit the following warning on read, write,
and also read-write (prior to the ``Error`` being thrown) operations:
```
Warning: String offset cast occurred
```

Before being cast to integers and following the behaviour of an integer offset.

However, there is a caveat for floating point numbers that are fractional,
non-finite, or do not fit in an integer which emit the following deprecation notice
when using an existence check with ``isset()`` or ``empty()``:
```
Deprecated: Implicit conversion from float %F to int loses precision
```

#### Invalid offsets

The following offset types are invalid string offsets types:

 - arrays
 - objects
 - resources

For Read, Write,
Existence checks via the null coalesce operator `??`,
and even Read-Write the following error is thrown:
```
Cannot access offset of type %s on string
```

For ``isset()`` and ``empty()`` no warning is emitted and the behaviour is as if the offset did not exist.


#### String offsets

Using a string as an offset adds yet another layer of complexity as a string
might be:
- Numeric integer
- Numeric float
- Leading numeric integer
- Leading numeric float
- Non-numeric

Although the concept of leading numeric strings has been mostly been removed with
the [Saner numeric strings RFC](https://wiki.php.net/rfc/saner-numeric-strings)
due to backwards compatibility concerns some part of the engine are still aware of them,
string offsets being one of such case.

##### Numeric integer

Numeric integer strings behave like a normal integer type.

##### Leading numeric integer

Leading numeric integers act similarly to
[Offset types that warn about being cast to int](LINK WITH ANCHOR#) //TODO After render
but rather than emitting the ``Warning: String offset cast occurred`` warning
it emits a ``Warning: Illegal string offset "%s"`` warning.

One difference however, is that this warning is also emitted for
existence checks via the null coalesce operator `??`,
but existence checks with ``isset()`` and ``empty()`` remain silent.

##### Other strings

Non-numeric, numeric float, and leading numeric float
string offsets behave like an invalid string offset, with one exception,
they do not throw an error for existence checks via the null coalesce operator `??`.

Meaning the behaviour is identical to existence checks with ``isset()`` and ``empty()``.


### Internal objects

Internal objects can overload the different operations by replacing
the following mandatory object handlers:
 - ``read_dimension(zend_object *object, zval *offset, int type, zval *rv)``
 - ``write_dimension(zend_object *object, zval *offset, zval *value)``
 - ``has_dimension(zend_object *object, zval *member, int check_empty)``
 - ``unset_dimension(zend_object *object, zval *offset)``

The default handlers provided by ``std_object_handlers``,
which are used by userland objects,
verifies if `ArrayAccess` is implemented and calls the relevant method,
or throw an `Error` if not.

One important thing to note is that internal objects can only overload *some*
of the handlers.
One such example is the DOM extension, that only overwrites the read and has handlers
for `DOMNodeMap` and `DOMNodeList`.
Other extensions overwrite the handler to immediately throw an error,
or customize the error message (e.g. `PDORow` for write and unset operations).

Moreover, it is *not required* for an internal object that overwrites those handlers
to implement ``ArrayAccess``, one such example is ``SimpleXMLElement``.

The ``write_dimension`` handler is also responsible for the appending operation,
in which case the ``offset`` parameter is the `NULL` pointer.
Therefore, it is possible for an internal object to allowing writing to an offset,
but not appending to the object by throwing en exception when the ``offset`` pointer is null.
``SplFixedArray`` for example does this.

The ``read_dimension`` handler is not only called for read operations.
It is also responsible for existence checks via the null coalesce operator
`??`, in which case ``BP_VAR_IS`` is passed to the ``type`` parameter.
The ``read_dimension`` handler must also deal with the case where the ``offset``
`zval` pointer is the ``NULL`` pointer.
This exoteric case happens during a nested assignment, which requires fetching intermediate values:
```php
$object[][$offset] = $value;
```
Example: https://3v4l.org/VDVeX
NOTE: PDORow is bugged and does not null check, SimpleXML possibly allows a write need to check


TODO Determine if the custom handler MUST handle the case where userland child class overwrites the ArrayAccess methods

TODO: Check behaviour of stuff doing ``unset($o[][$index])`` and ``unset($o[$key][$index])``

TODO: Check behaviour with nested index where append operation is present for isset/empty

### Userland classes that implement ArrayAccess

TODO Explain Read, Write, Appending (with nested appending on a write operation), `isset()`, ``unset()``

TODO: Inform about known issues with offsetGet() and lack of returning by reference.

Q: How null coalesce operator work.
A: First a call to ``offsetExists()`` is made and then a call to ``offsetGet()``

Q: How ReadWrite operations work.
A: First a call to ``offsetGet()`` is made,
   the binary operation is performed, and then a call to ``offsetSet()``

Q: How existence checks with ``empty()`` work:
Answer: First a call to ``offsetExists()`` is made and then a call to ``offsetGet()``


### ArrayObject

TODO, mess as it interfaces between the world of ArrayAccess and object handlers.
And also implements an ``append()`` method that is not called with the ``$o[] = $v``
syntax.

## Ideal behaviour

### Invalid container types

Trying to use an invalid container type as a container should throw a `TypeError`
for every single operation, regardless of the type of the offset.

This includes using `false` as a container.

### null type as a container

For read, and read-write operations a `TypeError` should be thrown.
The current behaviour for `null` as container for other operations should be identical.

### Array

Valid offset types should only be ``int`` and ``string``,
other offset types should throw a ``TypeError``,
regardless of the operation being performed.

### Strings

Valid offset types should only be ``int``,
other offset types should throw a ``TypeError``,
regardless of the operation being performed.

### Objects container improvements

If the object does not support being used as a container then the handlers should
be the ``NULL`` pointer.

``has_dimension(const zend_object *object, const zval *offset, zval *rv)``
Meaning extension cannot overload the ``empty()`` check anymore,
but also that the null coalesce operator is handled by this handler.

// Having an Append handler that returns the newly created offset could make sense?
This would stop requiring the read handler to deal with the appending operation.

``zval* append(const zend_object *object, const zval *offset, zval *rv)``
Return a reference to the newly created value, easier to expose to userland

TODO: A lot more and design thinking, take inspiration from Raku?
Ruby is interesting but does some weird stuff with the nb of arguments and order.
Python is not massively useful as it doesn't support appending which is PHP's major hurdle

For example for nested indexes RW (or intermediary append) force the
``offsetGet()`` to return by ref (even for objects?), otherwise throw Error? 

## Motivations

### Throwing Errors for invalid container types for all operations

### Throwing Errors for invalid offset types for all operations

### Remove custom support for `empty()`

Confusing semantics, requirement if we ever want to make `empty()` not a language construct and just a simple function

## Migration path

## Version

Next minor version, PHP 8.4, and next major version PHP 9.0.

## Vote

VOTING_SNIPPET
