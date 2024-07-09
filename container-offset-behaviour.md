# PHP RFC: Improve language coherence for the behaviour of offsets and containers

- Version: 0.2
- Date: 2023-12-07
- Author: Gina Peter Banyard <girgias@php.net>
- Status: Under Discussion
- Target Version: PHP 8.4
- Implementations:
  - New handler API: <https://github.com/Girgias/php-src/pull/19>
  - `ArrayObject`: <https://github.com/php/php-src/pull/12037>
  - Original string offset clean-up PR: <https://github.com/php/php-src/pull/7173>
- First Published at: <http://wiki.php.net/rfc/container-offset-behaviour>
- Markdown source: <https://github.com/Girgias/php-rfcs/blob/master/container-offset-behaviour.md>

## Introduction

PHP supports accessing sub-elements of a type via an offset using brackets ``[]`` with the following notation ``$container[$offset]``.
However, the behaviour of such accesses depends not only on the type of the container and that of the offset,
but also on the operation that is being performed while accessing the offset.
The existing behaviour is highly inconsistent and difficult to anticipate.

The objectives of this RFC is to showcase the current complicated behaviour.
Present behaviour that we deem to be coherent and easy to reason about.
And a path to go from the existing behaviour to the desired target behaviour.

To explain the current language semantics we will describe and explain the different:

- Operations relating to containers and offsets
- Types that can be used as offsets
- Types that can be used as containers

### Operations

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

The read, write, read-write, appending, and unsetting operations are what one would expect.

We split the existence check operation into two distinct sub-operations as the behaviour between
``isset()``/``empty()`` and the null coalesce operator ``??`` is sometimes different.

A fetch operation occurs when reference to the offset must be acquired,
be that explicitly when taking a reference (e.g. `$r = &$container[$offset]`),
or when writing/appending/unsetting to sub-dimensions (e.g. `$container[$offset1][$offset2] = $value`
the first offset `$container[$offset1]` will be accessed via a fetch operation).

The peculiar fetch-append operation happens when retrieving a reference of an append operation.
For example `$r = &$container[];`, or a more common use `$container[][$offset] = $value`.

In general, a nested operation will perform all the necessary fetch/read operations,
interpreting the returned value as a container, until it reaches the final dimension.

### Container types

We consider there to exist thirteen (13) different types of containers:

- `null`
- `false`
- `true`
- `bool`
- `int`
- `float`
- `resource`
- `string`
- `array`
- Userland objects that do *not* implement ``ArrayAccess``
- Userland objects that implement `ArrayAccess`
- Internal objects that override none of the following object handlers: ``read_dimension``, ``write_dimension``, ``has_dimension``, and ``unset_dimension``
- Internal objects that override at least one, but not all the following object handlers: `read_dimension`, `write_dimension`, `has_dimension`, or `unset_dimension`
- Internal objects that override all the following object handlers: `read_dimension`, `write_dimension`, `has_dimension`, and `unset_dimension`
- ``ArrayObject`` as its behaviour is rather peculiar

We consider `false` and `true` to be different container types,
as `false` supports auto-vivification.

### Offset types

Finally, we consider there to exist the standard eight (8) built-in types in PHP for offsets, namely:

- `null`
- `bool`
- `int`
- `float`
- `resource`
- `string`
- `array`
- `object`

Note: the behaviour of integer strings used as offsets for arrays being automatically converted to `int` is out of scope for this RFC.
Meaning the behaviour of the string `"15"` being cast to the integer `15` when used as an `array` offset will not change.


## Current behaviour

Considering the large possible combination of containers, offsets, and operations;
we will start by grouping related container types together,
and then detail the behaviour depending on the offset type or the operation, which ever is clearer.

### Invalid container types

This sections covers a large number of types when used as a container, as this usage is invalid.

#### "Scalar" types

For the purpose of this section,
``true``, `int`, `float`,
and `resource` are considered to be a "scalar" types,
as the engine treats those container types identically.

- For read operations, `null` is returned and the following warning is emitted: ```Warning: Trying to access array offset on TYPE```

- For write, read-write, appending, fetch, and fetch-append operations, the following error is thrown:```Cannot use a scalar value as an array```

- For the unset operation, the following error is thrown:```Cannot unset offset in a non-array variable```

- For existence operations, no warning is emitted and the behaviour is as if the offset did not exist.

#### Classes that do not implement ArrayAccess and Internal objects which do not implement any dimension object handler

For every single operation, regardless of the type of the offset, the following ``Error`` is thrown:
```
Cannot use object of type ClassName as array
```


### null type as container

PHP supports a feature called auto-vivification to `array` when writing to an offset when the container is of type ``null``.

Therefore, the behaviour depending on the operator is as follows:

- For read operations, `null` is returned, the container continues to be `null`, and the following warning is emitted: ```Warning: Trying to access array offset on null```

- For write, append, fetch, and fetch-append operations the container is converted to array. And thus behave like an `array`, meaning the behaviour depends on the offset type. Please see the `array` section for details.

- For read-write operations, the container is converted to array, before the read operation. And thus behave like an `array`, meaning the behaviour depends on the offset type. Please see the `array` section for details.

- For the unset operation, the container continues to be `null` and no warning or error is emitted/thrown.

- For existence operations, no warning is emitted and the behaviour is as if the offset did not exist.

### false as container

PHP also supports auto-vivification to `array` for `false` containers,
however this was
[deprecated in PHP 8.1](https://wiki.php.net/rfc/autovivification_false).

Therefore, the behaviour depending on the operator is as follows:
- For read operations, `null` is returned, the container continues to be `false`, and the following warning is emitted: ```Warning: Trying to access array offset on false```

- For write, append, fetch, and fetch-append operations the container is converted to array, Emitting the following deprecation notice:```Deprecated: Automatic conversion of false to array is deprecated``` And thus behave like an `array`, meaning the behaviour depends on the offset type. Please see the `array` section for details.

- For read-write operations, the container is converted to array, before the read operation,
  Emitting the following deprecation notice: ```Deprecated: Automatic conversion of false to array is deprecated``` And thus behave like an `array`, meaning the behaviour depends on the offset type.  Please see the `array` section for details.

- For the unset operation, the container continues to be `false` and the following deprecation notice is emitted: ```Deprecated: Automatic conversion of false to array is deprecated```

- For existence operations, no warning is emitted and the behaviour is as if the offset did not exist.



### Arrays

Arrays are the ubiquitous container type in PHP and support all the operations,
therefore the behaviour is only affected by the type of offsets used.

#### Valid offsets

Arrays in PHP accepts offsets of either type `int` or `string` and in those cases the behaviour is as expected.

One thing to note is that when attempting to read an undefined offset the following warning is emitted:

```
Warning: Undefined array key KEY_NAME
```


#### Offset types cast to int

The following offset types are cast to int silently:

- `false` is cast to 0
- `true` is cast to 1
- Non-fractional floating point numbers which fit in an `int` are cast to their integer value

Offsets of type `resource` are cast to int with the following warning:
```
Warning: Resource ID#%d used as offset, casting to integer (%d)
```

Offsets of type `float` that are fractional, non-finite,
or do not fit in an integer are cast to `int` with the following deprecation notice:
```
Deprecated: Implicit conversion from float %F to int loses precision
```


#### Offset types cast to string

- ``null`` is cast to an empty string

#### Invalid offsets

The following offset types are invalid offsets types for arrays:

- `array`
- `object`

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
 - Read-Write operations on a string offset will throw the following error: ```Cannot use assign-op operators with string offsets```

 - Unset operations on a string offset will throw the following error: ```Cannot unset string offsets```

 - The append and fetch-append operations will throw the following error: ```[] operator not supported for strings```

 - Fetch operations will throw different errors depending on the fetch operation, *after* the type of the offset has been checked:
   - For attempting to retrieve a reference to a string offset: ```Cannot create references to/from string offsets```
   - For attempting to use the string offset as a container: ```Cannot use string offset as an array```
   - For attempting to use the string offset as an object: ```Cannot use string offset as an object```
   - For attempting to use increment or decrement the string offset: ```Cannot increment/decrement string offsets```

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

Indeed, a negative offset can be outside the range of a valid string offsets.
Negative offsets start counting from the end of the string,
if the absolute value of the offset is greater than ``strlen($string)``
it implies that the negative offset points to a byte before the first byte of the string,
therefore being invalid, when attempting to perform a write operation in such cases
the following warning is emitted:
```
Warning: Illegal string offset %s
```

#### Offset types that warn about being cast to int

The offset types
- `null`
- `bool`
- `float`

have a simple behaviour.
They are cast to `int` and behave like an integer offset.

The following warning is emitted for all operations except existence check operations
(this includes read-write operations which emits the warning prior to the `Error` being thrown)
before being cast to `int`:
```
Warning: String offset cast occurred
```

However, floating point numbers that are fractional, non-finite, or do not fit in an integer;
emit the following deprecation notice when using an existence check with ``isset()`` or ``empty()``:
```
Deprecated: Implicit conversion from float %F to int loses precision
```

#### Invalid offsets

The following offset types are invalid string offsets types:

 - `array`
 - `object`
 - `resource`

For Read, Write, Existence checks via the null coalesce operator `??`,
and even Read-Write the following error is thrown:
```
Cannot access offset of type %s on string
```

For existence checks via ``isset()`` and ``empty()`` no warning is emitted and the behaviour is as if the offset did not exist.


#### String offsets

Using a string as an offset adds yet another layer of complexity as a string might be:
- Numeric integer
- Numeric float
- Leading numeric integer
- Leading numeric float
- Non-numeric

Although the concept of leading numeric strings has been mostly been removed with
the [Saner numeric strings RFC](https://wiki.php.net/rfc/saner-numeric-strings)
due to backwards compatibility concerns some part of the engine are still aware of them,
string offsets being one such case.

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

However, the behaviour of ``isset()`` and ``empty()`` is completely broken in this case.
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

One important thing to note is that internal objects can overload only *some*
of the handlers.
One such example is the DOM extension, that only overwrites the read and has handlers
for `DOMNodeMap` and `DOMNodeList`.
Other extensions overwrite the handler to immediately throw an error,
or customize the error message (e.g. `PDORow` for write and unset operations).
The `ResourceBundle` class overloads the `read_dimension` handler,
but not the `has_dimension` handler,
which leads to a situation where one can access offset but not check for their existence.

Moreover, it is *not required* for an internal object that overwrites those handlers
to implement `ArrayAccess`, this is the case for all non-SPL extension.
This is especially confusing for `SimpleXMLElement` as it actually overloads and supports all
the dimension handlers.

Let's now have a more in depth look at the individual object handlers,
and some of the pitfalls the current object handler API design causes.

#### The `has_dimension` handler

The `check_empty` parameter of the `has_dimension` is there to indicate to the handler if
the existence check is a call to `isset()` or `empty()` and the handler must implement the logic
for determining if the value is falsy or not.
This is error-prone, and indeed `PDORow` did not implement the logic for handling calls to `empty()`
properly. [1:https://github.com/php/php-src/pull/13512]

One other requirement of the `has_dimension` is to return `false` if the offset exists but the value
at this offset is `null`, this is to mimic the semantics of `isset()`.
However, this is error-prone (e.g. `PDORow` didn't implement this logic correctly)
and also prevents supporting objects in `array_key_exists()` as this function explicitly does *not*
check the value pointed to by the offset.

This requirement is explicitly violated in `SplObjectStorage` with a comment explaining that
because `SplObjectStorage::offsetExists()` is an alias of `SplObjectStorage::contains()`
the `has_dimension` handler returns `true` even if the value is `null`.

#### The `write_dimension` handler

The ``write_dimension`` handler is also responsible for the appending operation,
in which case the ``offset`` parameter is the `NULL` pointer.
Therefore, it is possible for an internal object to allowing writing to an offset,
but not appending to the object by throwing en exception when the ``offset`` pointer is null.
``SplFixedArray`` for example does this.

#### The `read_dimension` handler

The `type` parameter of the `read_dimension` indicates the type of the operation the read handler is called in,
and is provided by the VM at run time.
It may be one of `BP_VAR_R`, `BP_VAR_W`, `BP_VAR_RW`, `BP_VAR_IS`, or `BP_VAR_UNSET`.

Obviously, the `read_dimension` handler is called for read operations with the `type` being `BP_VAR_R` in that case.

However, the `read_dimension` handler is also called for existence checks via the null coalesce operator `??`,
in which case `BP_VAR_IS` is passed to the `type` parameter.

Finally, the `read_dimension` handler is also called for fetch and fetch-append operations.
In which case the `type` parameter might be `BP_VAR_W`, `BP_VAR_RW`, or `BP_VAR_UNSET`
depending on what the purpose of the fetch is.
(Note: retrieving a reference is a `BP_VAR_W` operation.)
For the fetch-append operation the `offset` parameter is the `NULL` pointer,
mimicking the behaviour of the `write_handler`.

This effectively means that the `read_dimension` handler must handle every possible `BP_VAR_*` type
and possibly not having an offset.

The complexity of these requirements for the `read_dimension` handler are generally not understood,
and was the source of a bug in `PDORow` which did a `NULL` pointer dereference for fetch-append operations.
[1:https://github.com/php/php-src/pull/13512]

The only extension that properly implements all this complexity is SimpleXML
and uses it to support auto-vivification of XML elements.

#### General handler requirements and pitfalls

For classes that are not final, all overridden dimension handlers must
forward calls to the userland methods if a child class implements `ArrayAccess`.
If not, the child class's `ArrayAccess` methods are never called.
Such bugs exist in ext/dom, and it is not clear how to fix them.

To help with this case, the `zend_class_arrayaccess_funcs` struct is populated with
the `zend_function *` pointers of the overloaded methods when `ArrayAccess` is implemented.
And the corresponding pointer on the `zend_class_entry` is set to point to this allocated struct.
However, as far as we can tell only SPL actually uses this.

One additional pitfall that is common to all dimension handlers is the need to call `ZVAL_DEREF()`
on the offset `zval*` so that when PHP references are used they work properly.
This requirement wasn't followed by `DOMNodeMap` and `DOMNodeList` [1:https://github.com/php/php-src/pull/13511],
`ResourceBundle` [1:https://github.com/php/php-src/pull/13503],
and `PDORow` [1:https://github.com/php/php-src/pull/13512].
Moreover, some extensions do dereference the offset, but only indirectly, and it is not know if
this was done on purpose or happens to work, for example `FFI\CData` dereferences them via the call to
`zval_get_long()`.
Meanwhile `SplObjectStorage` fallbacks to calling the PHP method implementation instead of using the C handler,
which will dereference the reference as the parameter is by-value.

### Userland classes that implement ArrayAccess

Userland classes can overload the dimension access operators by implementing the `ArrayAccess` interface.
The four interface methods roughly correspond to the four relevant dimension object handlers.

The interface methods are called in the following way for the different operations:

- Read: the `ArrayAccess::offsetGet($offset)` method is called with `$offset` being equal to the value between `[]`

- Write: the `ArrayAccess::offsetSet($offset, $value)` method is called with `$offset` being equal to the value between `[]` and `$value` being the value that is being assigned to the offset. 

- Read-Write: the `ArrayAccess::offsetGet($offset)` method is called with `$offset` being equal to the value between `[]`, the binary operation is then performed, and if the binary operation succeeds the `ArrayAccess::offsetSet($offset, $value)` method is called with `$value` being the result of the binary operation 

- Appending: the `ArrayAccess::offsetSet($offset, $value)` method is called with `$offset` being equal to `null` and `$value` being the value that is being appended to the container.

- Unsetting: the `ArrayAccess::offsetUnset($offset)` method is called with `$offset` being equal to the value between `[]`

- Existence checks via isset(): the `ArrayAccess::offsetExists($offset)` method is called with `$offset` being equal to the value between `[]`

- Existence checks via empty(): the `ArrayAccess::offsetExists($offset)` method is called with `$offset` being equal to the value between `[]` if `true` is returned, a call to `ArrayAccess::offsetGet($offset)` is made to check the value is falsy or not.

- Existence checks via the null coalesce operator `??`: the `ArrayAccess::offsetExists($offset)` method is called with `$offset` being equal to the value between `[]` if `true` is returned, a call to `ArrayAccess::offsetGet($offset)` is made to retrieve the value. (Note this is handled by the default `read_dimension` object handler instead of the `has_dimension` handler)

- Fetch: the `ArrayAccess::offsetGet($offset)` method is called with `$offset` being equal to the value between `[]`

- Fetch Append: the `ArrayAccess::offsetGet($offset)` method is called with `$offset` being equal to `null`

Because `ArrayAccess::offsetGet($offset)` is called for fetching operations, if it does not return an object or by-reference,
the following notice is emitted:
```text
Notice: Indirect modification of overloaded element of ClassName has no effect in %s on line %d
```

Of note is the behaviour with `isset()`.
Because the value at the offset is never checked via a call to `offsetGet()`,
a correct implementation of the `offsetExists($offset)` method that follows the general `isset()` semantics,
*must* return `false` if the backing value is `null`.
As such the following implementation of `ArrayAccess` is semantically *incorrect*:
```php
class A implements ArrayAccess {
    private array $a = [];
    
    public function offsetSet($offset, $value): void {
        var_dump(__METHOD__);
        $this->a[$offset] = $value;
    }
    public function offsetGet($offset): mixed {
        var_dump(__METHOD__);
        return $this->a[$offset];
    }
    public function offsetUnset($offset): void {
        var_dump(__METHOD__);
        unset($this->a[$offset]);
    }
    public function offsetExists($offset): bool {
        var_dump(__METHOD__);
        return array_key_exists($offset, $this->a);
    }
}
```

Indeed, the following call sequence would break the expectations of `isset()` by returning `true`:
```php
$a = new A();

$a[3] = null;
var_dump(isset($a[3]));
```

This behaviour is confusing to users and has been reported as a bug for
[`WeakMap`](https://github.com/php/php-src/issues/8437).

### ArrayObject

`ArrayObject` has some peculiar behaviour as it attempts to mimic the built-in `array`
type by implementing various interfaces and object handlers.

Moreover, it allows to use another object as the backing "array"
in which case offsets correspond to properties of the passed object.

This feature is currently implemented in such a way that it breaks
assumptions surrounding objects.
Indeed, `ArrayObject` will write to the property HashTable directly,
by-passing any write restrictions on the property.
This includes overwriting `readonly` properties that have been already set,
overwriting typed properties with values of incorrect types,
suppressing dynamic properties deprecation notices,
and ignoring any `__set()` or `__get()` magic methods.

`ArrayObject` has an `append()` method that can be called to append values to it.
However, counterintuitively, this method is **not** called when using the append
operations `$ArrayObject[] = $value`, as the method that is actually called is
`offsetSet(null, $value)`.
This gets even more confusing when subclassing `ArrayObject` and redefining `append()`
to modify the default appending behaviour.

Moreover, attempting to call `append()` when the backing array is another object,
correctly throws an `Error: Cannot append properties to objects, use ArrayObject::offsetSet() instead`,
but when using the appending operator this error does not get thrown.

Another problem is that `offsetSet()` cannot distinguish between using `null` as an explicit offset
or being provided by default for the appending operation,
it treats both of these cases as an appending operations.
This leads to an inconsistency as one can set a value to an offset of `null`,
but not be able to read it, as for read operations `null` gets converted to an empty string,
like for the built-in array type.

One final problem with `ArrayObject` is the implementation around `isset()`,
when using it without a backing object, it works as intended and like an array.
However, when using a backing object any offset that correspond to a declared property
is considered to exist, even if it is an uninitialized typed property.

The following code:
```php
class T {
    public int $p;
}

$o = new T();
$a = new ArrayObject();

$a = new ArrayObject($o);
var_dump(isset($a['p']));
var_dump($a['p']);
```

results in the following behaviour:
```text
bool(true)

Warning: Undefined array key "p" in %s on line %d
NULL
```
while keeping the typed property in an uninitialized state.

## Ideal semantics

In this section we present semantics for containers and how offsets
should behave for this sort of container, that are easy
to reason about and remember.

Valid container types are:
- `array`
- `string`
- `object` that implement an interface indicating it can be used as a container

### Arrays

The semantics of arrays are mostly unchanged,
except in regard to the handling of offset types.

Valid offset types for array are `int` and `string`,
all other offset types throw a `TypeError`;
regardless of the operation being performed.


### Strings

The semantics of strings are mostly unchanged,
except in regard to the handling of offset types.

The only valid offset type for strings is `int`,
all other offset types throw a `TypeError`;
regardless of the operation being performed.


### `null`

The semantics of `null` are mostly unchanged.
It continues to support auto-vivification to `array`, except for read, and read-write operations;
in which case a `TypeError` is thrown about invalid access of an offset on `null`.
Meaning that auto-vivification to `array` is supported for write, append, fetch, and fetch-append operations.

Moreover, it continues to short-cut nested dimension checks with existence check operations.

### Objects

Objects should be able to implement an interface for each corresponding operation they support:
- Read and existence checks
- Write
- Appending
- Unsetting
- Fetching
- Fetch appending

If an object is used in a container operation and does not implement the corresponding interface,
a `TypeError` is thrown.

Existence checks for `isset()`/`empty()` and the null coalesce operator `??` should follow the following algorithm:

- Call method to verify the offset exists:
  - If it does not exist: return `false` (`true` for `empty()`)
  - Otherwise: call method to get value of offset:
    - If the value is `null` (or falsy for `empty()`) return `false` (`true` for `empty()`)
    - Otherwise: return `true` (`false` for `empty()`)

The following algorithm is easily understood and means general assumptions about the existence
check method are valid.

### Invalid container types

This corresponds to all other types and objects that do not implement an interface
indicating it can be used as a container.

This should throw a `TypeError` for every single operation, regardless of the type of the offset.

Ideally, the error message is standardized to be consistent and descriptive for all types.

One possibility is `Cannot use value of type TYPE as an array`.


## Motivations

We think that the proposed ideal semantics would make it obvious and intuitive for what would happen
when using offsets and containers in PHP.

We will slightly expand on the motivation for certain changes.

### Throwing Errors for invalid container types for all operations

This should be self-explanatory, attempting to use a type which is not a container as a container is a programming error.

This is applicable even when checking for the existence of an offset.

### Throwing Errors for invalid offset types for all operations

Similarly, using invalid offset types on a container is a programming error,
regardless of checking for the existence of an offset or not.

Moreover, `array` offsets already behaves this way.

### Change requirements for the `has_dimension` handler

The current requirements are very confusing and unintuitive.

As show-cased the requirement to return `false` if the offset exist but is `null`
is largely misunderstood and affects userland by requiring them to propagate this behaviour to their implementation
of `offsetExists()`.
Handling this correctly adds implementation complexity as the `has_dimension` handler needs to effectively be able to perform read operations,
and if it doesn't it can lead to unintuitive semantics if the handler considers `null` to be set.
These semantics also preventing the widening of the `$array` parameter type of `array_key_exists()` to
accept objects that support accessing offsets, something that has been requested by userland. [1:https://externals.io/message/122435]

Needing to handle `empty()` suffers most of the same implementation pitfalls and unintuitive semantics if the handler considers non-falsy things empty.
Moreover, if we ever want to make `empty()` a simple function an object handler cannot influence on its behaviour.


## Migration path

To go from the current semantics and behaviour to the desired semantics we propose
the following changes for PHP 8.4, and PHP 9.0:

### Changes in PHP 8.4

#### Changes to objects

##### Add granular interfaces

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
    public function offsetSet(mixed $offset, mixed $value): void;
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
as this would allow `TypeErrors` to be thrown by the engine without needing
to manually handle the type of the offset and/or value.

However, `mixed` allows us to migrate to generic types if we ever get them.

Intersection and DNF types makes the addition and usage of more granular interfaces possible.

Those new interfaces and methods provide clearer semantics and behaviour that is known
to be supported or not by the class, while simplifying the implementation of said classes.

Cross-version compatible code can use DNF types to type their input arguments, e.g:
```php
function foo(ArrayAccess|(DimensionReadable&DimensionWritable)) {
    /* Do something useful */
}
```

##### Changes to internal objects

Currently, the dimension handlers have a default handler which makes it difficult to
know if an object supports certain dimension handlers.

Therefore, we move the handlers out of the `zend_object_handlers` structure and into the `zend_class_entry` structure.
We add new handlers which correspond to the above interfaces which are all defined in a new struct:
```c
typedef struct _zend_class_dimensions_functions {
	/* rv is a slot provided by the callee that is returned */
	zval *(*read_dimension)(zend_object *object, zval *offset, zval *rv);
	bool  (*has_dimension)(zend_object *object, zval *offset);
	zval *(*fetch_dimension)(zend_object *object, zval *offset, zval *rv);
	void  (*write_dimension)(zend_object *object, zval *offset, zval *value);
	void  (*append)(zend_object *object, zval *value);
	zval *(*fetch_append)(zend_object *object, zval *rv);
	void  (*unset_dimension)(zend_object *object, zval *offset);
} zend_class_dimensions_functions;
```

If the object does not support being used as a container then the pointer for the `zend_class_dimensions_functions`
should be the ``NULL`` pointer. Otherwise, it should be allocated and be populated with function pointers for
the operations that are supported, and the `NULL` pointer for operations that are not.

Moreover, the object should implement the relevant interfaces for the capabilities that it supports.
This is relatively straight forward for all bundled extensions except for ext/ffi as the `CData` class
is used to represent scalar data but also arrays and pointer types, which do overload the dimension handlers.

The new handlers are slightly different from the existing one,
as it is designed to reduce implementation complexity of the handlers.
The `has_dimension` handler does not know if it is being called with `empty()`,
as this is meaningless with the algorithm that is implemented.
Its only duty is to indicate if the offset exists or not, not check if the backed value is `null` or `falsy`.
Moreover, it is also called with the null coalesce operator.

This change means that the `read_dimension` doesn't need to know in what context it is called,
as it will only ever be called in a read context.
Because the fetch and fetch append handlers would be called during fetching operations instead of the read handler.

Another consequence of using the new algorithm is that some idiosyncratic code that produces side effects
in the `has_dimension` handler might not work as before,
this also applies to userland classes implementing `ArrayAccess`.
For example, the following code:
```php
class Test implements ArrayAccess {
    public function offsetExists($x): bool { $GLOBALS["name"] = 24; return true; }
    public function offsetGet($x): mixed { var_dump($x); return 42; }
    public function offsetSet($x, $y): void { }
    public function offsetUnset($x): void { }
}

$obj = new Test;
$name = "foo";
var_dump($obj[$name] ?? 12);
var_dump($name);
```

currently produces the following output:
```
string(3) "foo"
int(42)
int(24)
```

however, with the new algorithm, would produce this output:
```
int(24)
int(42)
int(24)
```

As the `offsetExists()` wasn't called before, but now is.

###### Removal of the `zend_class_arrayaccess_funcs` struct and CE pointer

As the `zend_class_arrayaccess_funcs` struct was only used by SPL,
and it cannot fulfill its role anymore with the new dimension handlers,
the struct is removed and alongside it the pointer to such a struct on the `zend_class_entry`.

##### Changes to `ArrayObject`

The introduction of the new interfaces and handlers allows us to fix part of the implementation of `ArrayObject`
to follow the usual semantics of `array` and not break assumptions around objects:

- Implement the new interfaces
- Call `append()` for the appending operation (following from the new `Appendable` interface)
- Fix `null` offset handling (following from the proper support of the appending operation)
- When using an object as a backing value:
  - Throw `Error` on appending
  - Emit dynamic properties warning when using an object as a backing value that does not allow dynamic properties
  - Throw `Error` on writing to `readonly` properties
  - Throw `Error` on writing a value of the wrong type to a typed property
- Continue to ignore any `__set()`/`__get()` magic methods

Most of these changes are implemented as [PR-12037](https://github.com/php/php-src/pull/12037).

##### Changes to `ArrayAccess`

Supporting `ArrayAccess` in a backwards compatibility way is slightly tricky.
It is effectively extending `DimensionReadable`, `DimensioWriteable`, and `DimensionUnsettable`,
but it also "supports" appending, fetching, and fetch-appending.

Our solution is to add legacy dimension handlers to classes that implement `ArrayAccess`
reproducing the current behaviour for appending, fetching and fetch-appending.
However, if one of the new interfaces is implemented for dedicated support to appending, fetching,
and fetch-appending, then the new behaviour is used.

##### Changes to `SplObjectStorage`

As mentioned previously, the current implementation of `SplObjectStorage::offsetExists()`
violates the expectations of `isset()`, however with the implementation of the new algorithm
this is fixed, which leads to a behavioural change.

Moreover, `SplObjectStorage` defines the following methods which are aliases to the dimension handler methods:

- `SplObjectStorage::contains()` for `SplObjectStorage::offsetExists()`
- `SplObjectStorage::detatch()` for `SplObjectStorage::offsetUnset()`
- `SplObjectStorage::attach()` for `SplObjectStorage::offsetSet()`

However, extending `SplObjectStorage` and overwriting one of the alias methods does _not_ modify
the behaviour of using the offset access operators.
As such we propose to deprecate the aliases in favour of the normal offset methods.

##### Changes to `MultipleIterator`

The implementation of `MultipleIterator` shares the same internal object handlers as `SplObjectStorage`.
This means it also supported the various offset access operators as a consequence.
As the dimension handlers would no longer be part of the object handlers,
this results in `MultipleIterator` not supporting them any longer.

As it does not implement `ArrayAccess` and there are no tests covering this behaviour,
it seems to us that this iterator was never designed to be accessed with the offset access operators.

As such we do not intend to formally implement any interfaces and support for using offset access operators
with `MultipleIterator` objects would be removed.

#### Changes to array offset handling

##### Disallow resources to be used as array offsets

Considering the phasing out of resources,
resources being generally considered equivalent as objects,
and a warning having been emitted for using resources as offset,
we propose to promote this warning to a TypeError in PHP 8.4.

This removes variations and a lot of complexity to the engine.

The `array_key_exists()` function, and any objects mimicking array offsets, is also affected and would have the `resource`
type removed from the union type for the `$key` parameter.

##### Emit warnings for invalid offset types on arrays

Emit the following warnings when using invalid offsets on an array,
this includes `null`, `bool`, and `float` types:

```
Warning: offset of type TYPE has been cast to (int|string)
```

#### Changes to string offset handling

##### Disallow leading numeric strings to be used as string offsets

Considering the prolonged existence of notice/warnings when using numeric strings,
and the fact `isset()/empty()` is completely broken with such offsets,
we propose to promote this warning to the usual `Cannot access offset of type %s on string` error.

##### Normalize the behaviour of invalid string offsets

This effectively means that non integer-numeric strings used as an offset for strings
with the null coalesce operator `??` would throw the following error:
```
Cannot access offset of type %s on string
```

##### Emit warning for checking existence of string offset with invalid offset types

Emit a warning when using invalid offsets on a string during existence check operations:
```
Cannot access offset of type TYPE on string in isset or empty
```

#### Emit warning on read-write operations on `null` container

Emit the same warning as a simple read operation when using `null` as a container:
```
Warning: Trying to access array offset on null
```

#### Emit warnings for checking existence of offsets on invalid container types

Emit a warning when using invalid offsets on an invalid container during existence check operations
as it is a programming error.

Note: this does *not* include `null` as a container, which will continue to short-cut existence checks.

#### Improved error messages

Part of this RFC will be to improve error messages and indicate if the
value cannot be used as an array:
```
Cannot use value of type TYPE as an array
```

And if the specific operation is not supported the error would resemble:

```text
Cannot OPERATION offset of type TYPE on value of type TYPE
```

### Changes in a future version of PHP 8

#### Internal objects must implement the relevant interfaces

This requirement would be checked in DEBUG builds of PHP.

The main reason for not making this a hard requirement with the other proposed changes for PHP 8.4
is that the `CData` class from the FFI extension is an opaque class that interfaces with different
C data types, such as scalars, C arrays, and pointers.

However, blindly adding the new dimension interfaces to indicate that offsets can always be accessed
would be a lie, as CData backing scalar data types can not be accessed in this manner.

To properly support this, it requires refactoring the `CData` class into a sealed interface and have
concrete class implementation for the different sorts of C data types,
e.g. `CScalar`, `CArray`, `CPointer`.

### Changes in PHP 9.0

Promote all warnings to `Error`

## Version

Next minor version, PHP 8.4, and next major version PHP 9.0.

## Vote

VOTING_SNIPPET

## Future scope

Ideas proposed in this section are not part of the RFC and may be something to do as a follow-up to this RFC.

- Phase out `ArrayAccess`, c.f. https://wiki.php.net/rfc/phase_out_serializable
- Deprecate `ArrayObject`

## References

Current behaviour has been mostly discovered and documented by adding behavioural tests in https://github.com/php/php-src/pull/12723

Behaviour for ArrayObject mostly comes out of attempting to fix various bugs in https://github.com/php/php-src/pull/12037
