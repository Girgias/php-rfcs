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

We consider there to be nine (9) different operations that relate to containers and offsets,
which are the following:

- Read
- Write
- Read-Write
- Appending, via the ``$container[] = $value`` syntax
- Unsetting
- Existence checks via `isset()` and/or ``empty()``
- Existence checks via the null coalesce operator ``??``
- Fetch
- Fetch-Append

// TODO Add fetching and fetch-appending operations
// TODO Add ++/-- operations (need tests) a FETCH_DIM_RW happens?
TODO: Figure out what is called when doing `$r = &$container[$offset1]`? A: Write op is done but the read handler is called

The reason for splitting the existence check operation into two distinct operations is that the behaviour sometimes differ between using ``iseet()``/``empty()`` and ``??``.

Fetching happens when retrieving a reference to the content of an offset,
this happens in the obvious `$ref = &$container[$offset]`, but also when doing write/unset operations
on nested dimension (e.g. `$container[$offset1][$offset2]`).

The peculiar fetch-append operation happens when retrieving a reference of an append operation.
For example `$r = &$container[];`, or a more common use `$container[][$offset] = $value`.

In general, a nested operation will perform all the necessary fetch/read operations,
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
- For write, read-write, appending, fetch, and fetch-append operations, the following error is thrown:
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
- For write, append, fetch, and fetch-append operations the container is converted to array.
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

- For write, append, fetch, and fetch-append operations the container is converted to array,
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
 - Attempting to append or fetch-append to a string will throw the following error:
   ```
   [] operator not supported for strings
   ```
 - Fetch operations will throw different errors depending on the fetch operation,
   after the type of the offset has been checked:
   - For attempting to retrieve a reference to a string offset:
     ```
     Cannot create references to/from string offsets
     ```
   - For attempting to use the string offset as a container:
     ```
     Cannot use string offset as an array
     ```
   - For attempting to use the string offset as an object:
     ```
     Cannot use string offset as an object
     ```
   - For attempting to use increment or decrement the string offset:
     ```
     Cannot increment/decrement string offsets
     ```

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

The following types `null`, `false`, `true`, and `float` have very simple behaviour,
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
[Offset types that warn about being cast to int](#offset_types_that_warn_about_being_cast_to_int)
but rather than emitting the ``Warning: String offset cast occurred`` warning
it emits a ``Warning: Illegal string offset "%s"`` warning.

One difference however, is that this warning is also emitted for
existence checks via the null coalesce operator `??`,
but existence checks with ``isset()`` and ``empty()`` remain silent.

However, it turns out that the behaviour of ``isset()`` and ``empty()`` is completely broken in this case.
It always indicates that an offset does not exist, when in fact it can be accessed:
```php
<?php
$s = "abcdefghijklmnopqrst";
$o = "5x4";
var_dump(isset($s[$o]));
var_dump(empty($s[$o]));
var_dump($s[$o] ?? "default");
var_dump($s[$o]);
```
results in the following output:
```text
bool(false)
bool(true)

Warning: Illegal string offset "5x4" in /tmp/preview on line 7
string(1) "f"

Warning: Illegal string offset "5x4" in /tmp/preview on line 8
string(1) "f"
```

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

The `check_empty` parameter of the `has_dimension` is there to indicate to the handler if
the existence check is a call to `isset()` or `empty()` and the handler must implement the logic
for determining if the value is falsy or not.

The ``write_dimension`` handler is also responsible for the appending operation,
in which case the ``offset`` parameter is the `NULL` pointer.
Therefore, it is possible for an internal object to allowing writing to an offset,
but not appending to the object by throwing en exception when the ``offset`` pointer is null.
``SplFixedArray`` for example does this.

Obviously, the `read_dimension` handler is called for read operations with the `type` being `BP_VAR_R` in that case.

However, the `read_dimension` handler is also called for existence checks via the null coalesce operator `??`,
in which case `BP_VAR_IS` is passed to the `type` parameter.

Finally, the `read_dimension` handler is also called for fetch and fetch-append operations.
In which case the `type` parameter might be `BP_VAR_W`, `BP_VAR_RW`, or `BP_VAR_UNSET`
depending on what the purpose of the fetch is.
(Note: retrieving a reference is a `BP_VAR_W` operation.)
For the fetch-append operation the `offset` parameter is the `NULL` pointer, mimicking the behaviour of the `write_handler`.

This effectively means that the `read_dimension` handler must handle every possible `BP_VAR_*` type
and possibly not having an offset.

The complexity of these requirements for the `read_dimension` handler are generally not understood,
and was the source of a bug in `PDORow` which did a NULL pointer dereference.
(TODO Fix and link)

The only extension that properly implements all this complexity is SimpleXML
and uses it to support auto-vivification of XML elements.

One additional requirement all overridden dimension handlers need to follow is to
forward calls to userland methods if a child class implements `ArrayAccess`.
If not, the child class's ArrayAccess methods are never called.
Such bugs have existed in ext/dom. (TODO Fix and link)

### Userland classes that implement ArrayAccess

Userland classes can overload the dimension access operators by implementing the `ArrayAccess` interface.
The four interface methods roughly correspond to the four relevant dimension object handlers.

The interface methods are called in the following way for the different operations:

- Read:
  the `ArrayAccess::offsetGet($offset)` method is called with `$offset` being equal to the value between `[]`
- Write:
  the `ArrayAccess::offsetSet($offset, $value)` method is called with `$offset` being equal to the value between `[]`
  and `$value` being the value that is being assigned to the offset. 
- Read-Write:
  the `ArrayAccess::offsetGet($offset)` method is called with `$offset` being equal to the value between `[]`,
  the binary operation is then performed, and if the binary operation succeeds
  the `ArrayAccess::offsetSet($offset, $value)` method is called with `$value` being the result of the binary operation 
- Appending:
  the `ArrayAccess::offsetSet($offset, $value)` method is called with `$offset` being equal to `null`
  and `$value` being the value that is being appended to the container.
- Unsetting:
  the `ArrayAccess::offsetUnset($offset)` method is called with `$offset` being equal to the value between `[]`
- Existence checks via isset():
  the `ArrayAccess::offsetExists($offset)` method is called with `$offset` being equal to the value between `[]`
- Existence checks via empty():
  the `ArrayAccess::offsetExists($offset)` method is called with `$offset` being equal to the value between `[]`
  if `true` is returned, a call to `ArrayAccess::offsetGet($offset)` is made to check the value is falsy or not.
- Existence checks via the null coalesce operator `??`:
  the `ArrayAccess::offsetExists($offset)` method is called with `$offset` being equal to the value between `[]`
  if `true` is returned, a call to `ArrayAccess::offsetGet($offset)` is made to retrieve the value.
  (Note this is handled by the default `read_dimension` object handler instead of the `has_dimension` handler)
- Fetch:
  the `ArrayAccess::offsetGet($offset)` method is called with `$offset` being equal to the value between `[]`
- Fetch Append:
  the `ArrayAccess::offsetGet($offset)` method is called with `$offset` being equal to `null`

Because `ArrayAccess::offsetGet($offset)` is called for fetching operations, and if it does not return an object or by-reference,
the following a notice is emitted:
```text
Notice: Indirect modification of overloaded element of ClassName has no effect in %s on line %d
```

### ArrayObject

TODO, mess as it interfaces between the world of ArrayAccess and object handlers.
And also implements an ``append()`` method that is not called with the ``$o[] = $v``
syntax.

## Ideal behaviour

### Invalid container types

Trying to use an invalid container type as a container should throw a `TypeError`
for every single operation, regardless of the type of the offset.

This includes using `false` as a container.

The error message should be standardized to be consistent and descriptive for all types.
One possibility is `Cannot use value of type TYPE as an array`.

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

#### For userland

Introduce new, more granular, interfaces:
 - `DimensionReadable`: which would have the equivalent of `offsetGet()` and `offsetExists()`
 - `DimensionWritable`: which would have the equivalent of `offsetSet()`
 - `DimensionUnsetable`: which would have the equivalent of `offsetUnset()`
 - `Appendable`: which would have a single method `append(mixed $value): mixed` that is called when appending
 - `DimensionFetchable`: which would extend `DimensionReadable` and have a method that returns by-reference
 - `FetchAppendable`: which would extend `Appendable` and have a method that returns by-reference the appended value

```php
interface DimensionReadable
{
    public function offsetGet(mixed $offset): mixed;

    public function offsetExists(mixed $offset): bool;
}

interface DimensionFetchable extends DimensionReadable
{
    public function &offsetFetch(mixed $offset): mixed;
}

interface DimensionWritable
{
    public function offsetWrite(mixed $offset, mixed $value): void;
}

interface DimensionUnsetable
{
    public function offsetUnset(mixed $offset): void;
}

interface Appendable
{
    public function append(mixed $value): void;
}

interface FetchAppendable extends Appendable
{
    public function &fetchAppend(): mixed;
}
```

Ideally, we would want the interfaces to have generic types,
as this would allow TypeErrors to be thrown by the engine without needing
to manually handle the type of the offset and/or value.

However, `mixed` allows us to migrate to generic types if we ever get them.

Intersection types makes the addition and usage of more granular interfaces possible.

Those new interfaces and methods provide clearer semantics and behaviour that is known
to be supported or not by the class, while simplifying the implementation of said classes.

#### For internals and extensions

If the object does not support being used as a container then the handlers should
be the ``NULL`` pointer.
Moreover, the object should implement the relevant interfaces for the capabilities
that it supports.

For this purpose, we move all the `_dimension` handlers to the `zend_class_entry` structure,
we also add new handlers which correspond to the above interface which are all defined in a new struct:
```c
typedef struct _zend_class_dimensions_functions {
	/* rv is a slot provided by the callee that is returned */
	zval *(*read_dimension)(zend_object *object, zval *offset, zval *rv);
	bool  (*has_dimension)(zend_object *object, zval *offset);
	zval *(*fetch_dimension)(zend_object *object, zval *offset, zval *rv);
	void  (*write_dimension)(zend_object *object, zval *offset, zval *value);
	/* rv is a slot provided by the callee that is returned */
	void  (*append)(zend_object *object, zval *value);
	zval *(*fetch_append)(zend_object *object, zval *rv);
	void  (*unset_dimension)(zend_object *object, zval *offset);
} zend_class_dimensions_functions;
```

Some key distinctions to note with those new handlers and how the engine would behave.
The `has_dimension` handler does not know if it is being called with `empty()`,
instead if the offset exists, the `read_dimension` is called and then the value of it is checked if it is falsy.
Similarly, the null coalesce operator also behaves this way, meaning the `read_dimension` handler is only ever called for reads.

## Motivations

The over-arching goal of the proposed semantics is to make it obvious and intuitive
what will happen when using offsets and containers in PHP.

### Throwing Errors for invalid container types for all operations

This should be self-explanatory, attempting to use a type which is not a container as a container is a programming error.

This is also true when actually checking for the existence of an offset.

### Throwing Errors for invalid offset types for all operations

Similarly, using invalid offset types on a container is a programming error,
regardless of checking for the existence of an offset or not.

Moreover, `array` already behaves this way.

### Remove custom support for `empty()` in object handlers

This adds implementation complexity on the part of the handler,
and can lead to unintuitive semantics if the handler considers non-falsy things empty.

Moreover, this is a requirement if we ever want to make `empty()` not a language construct and just a simple function.

## Migration path

To go from the current semantics and behaviour to the desired semantics we propose
the following changes for PHP 8.4, and PHP 9.0:

### Changes in PHP 8.4

#### Add granular interfaces

Add the interfaces that were described in the Objects container improvements section.

Cross-version compatible code can use DNF types to type their input arguments, e.g:
```php
function foo(ArrayAccess|(DimensionReadable&DimensionWritable)) {
    /* Do something useful */
}
```

#### Disallow resources to be used as offsets

Considering the phasing out of resources,
resources being generally considered equivalent as objects,
a warning having been emitted for using resources as offset,
we propose to promote this warning to a TypeError in PHP 8.4.

This removes variations and complexity to the engine.

#### Disallow leading numeric strings to be used as string offsets

Considering the prolonged existence of notice/warnings when using numeric strings,
and the fact `isset()/empty()` is completely broken with such offsets,
we propose to promote this warning to the usual `Cannot access offset of type %s on string` error.

#### Normalize the behaviour of invalid string offsets

This effectively means that non integer-numeric strings used as an offset for strings
with the null coalesce operator `??` would throw the following error:
```
Cannot access offset of type %s on string
```

#### Emit warning on read-write operations on `null` container

Emit the same warning as a simple read operation when using `null` as a container:
```
Warning: Trying to access array offset on null
```

#### Improved error messages

// TODO better, specify operation first? So it is generic with objects which do not implement all interfaces

Standardize error message for invalid containers to be:
```
Type TYPE cannot be used as an array, attempted to OPERATION
```
Where `OPERATION` is one of the following:
 - `read offset of type TYPE on it`
 - `write offset of type TYPE on it`
 - `unset offset of type TYPE on it`
 - `check existence of offset of type TYPE on it`
 - `append value to it`
 - TODO: `fetch`
 - TODO: `fetch append`

// TODO: Improve messages for invalid offset types

#### Emit warnings for invalid offset types on arrays

Emit the following warnings when using invalid offsets on an array,
this includes `null`, `bool`, and `float` types:

```
Warning: offset of type TYPE has been cast to (int|string)
```

#### Emit warning for checking existence of string offset with invalid offset types

```
Cannot access offset of type TYPE on string in isset or empty
```

#### Emit warnings for checking existence of offsets on invalid container types

This includes `false`, `true`, `int`, `float`, and `resource`.

// TODO: Warning message, see Improve error messages section
```
Warning: type TYPE cannot be used like an array
```

Note: this does *not* include `null`, which will continue to short-cut existence checks.

#### Internal objects must implement the relevant interfaces

This will be checked in DEBUG builds

TODO: ext/ffi CData might need to be converted to an interface and have concrete final classes for different data.

#### ArrayObject changes

TODO: After going through ArrayObject hell

### Changes in PHP 9.0

Promote all warnings to `Error`

## Version

Next minor version, PHP 8.4, and next major version PHP 9.0.

## Vote

VOTING_SNIPPET

## Future scope

Phase out `ArrayAccess`
c.f. https://wiki.php.net/rfc/phase_out_serializable

## References

Current behaviour has been mostly discovered and documented by adding behavioural tests in https://github.com/php/php-src/pull/12723

Behaviour for ArrayObject mostly comes out of attempting to fix various bugs in https://github.com/php/php-src/pull/12037
