# PHP RFC: Change Directory class to behave like a resource object

- Version: 0.1
- Date: 2024-09-14
- Author: Gina Peter Banyard <girgias@php.net>
- Status: Under Discussion
- Target Version: PHP 8.5
- Implementation: https://github.com/php/php-src/pull/15886
- First Published at: https://wiki.php.net/rfc/directory-opaque-object

## Introduction

The `Directory` class is probably the first instance of what we now call a "resource object"
(and in its stricter sense an "opaque object").
Resource/Opaque objects are usually the result of converting resources to objects,
which in general implies, being `final`, being not serializable,
not constructible via `new`, cannot be cast, and to not implement any methods.
However, as this class has existed since PHP 4 none of these things are formally implemented.

Valid instances of this class are created by calling the `dir()` function.
But one can create a broken instance by just using `new Directory()`,
which is visible if one tries to call one of its methods.

As it seems likely that we will repurpose this class when converting directory resources to objects;
we think it makes sense to already convert this class to behave like a resource object.

## Proposal

We propose to make the following changes to the `Directory` class:

- Make it `final`
- Throw an `Error` when doing `new Directory()`
- Prevent cloning instances of `Directory`
- Ban serialization of it via the `@not-serializable` doc comment on the class stub
- Ban creating dynamic properties on an instance of `Directory` via the `@strict-properties` doc comment on the class stub

## Rationales
### Preventing initialization via new

The stream layer of PHP emits warnings and may result in uninitialized streams.
Constructors must always either throw an exception, or create a valid object.
As these semantics are not straightforward to implement when creating streams we continue to rely on `dir()`
to create instances of this class as it does not have the above constraints.

### Making the class final

As this class is a wrapper around an internal stream resource,
and cannot be properly initialized without it being returned by `dir()`.
Extending it doesn't make any sense.

### Preventing cloning

As this class is a wrapper around an internal stream resource,
and there is no capability to duplicate streams, there is no reasonable way to implement cloning.

### Preventing serialization

Trying to serialize (and unserialize) the state of a given file system doesn't make any sense.

### Preventing the creation of dynamic properties

Creating a dynamic property on an instance of this class points to a definite bug.

## Backward Incompatible Changes

It will no longer be possible:

- to extend the `Directory` class
- clone, serialize, or create dynamic properties on an instance of `Directory`
- instantiate `Directory` directly via the `new` keyword

## Version

Next minor version, i.e. PHP 8.5.

## Vote

VOTING_SNIPPET

## Future scope

- Add support to initialize the class via `new`
- Add support for cloning

## References

