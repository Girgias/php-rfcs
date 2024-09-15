# PHP RFC: Change Directory class to behave like an opaque object 

- Version: 0.1
- Date: 2024-09-14
- Author: Gina Peter Banyard <girgias@php.net>
- Status: Under Discussion
- Target Version: PHP 8.5
- Implementation: https://github.com/php/php-src/pull/15886
- First Published at: https://wiki.php.net/rfc/directory-opaque-object

## Introduction

The `Directory` class is probably the first instance of what we now call an "opaque object".
Opaque objects are normally the result of converting resources to objects,
which in general implies, being `final`, being not serializable,
not constructible via `new`, cannot be cast, and to not implement any methods.
However, as this class has existed since PHP 4 none of these things are formally implemented.

Valid instances of this class are created by calling the `dir()` function.
But one can create a broken instance by just using `new Directory()`,
which is visible if one tries to call one of its methods.

As it seems likely that we will repurpose this class when converting directory resources to objects;
we think it makes sense to already convert this class to behave like an opaque object.

## Proposal

We propose to make the following changes to the `Directory` class:

- Make it `final`
- Throw an `Error` when doing `new Directory()`
- Prevent cloning instances of `Directory`
- Ban serialization of it via the `@not-serializable` doc comment on the class stub
- Ban creating dynamic properties on an instance of `Directory` via the `@strict-properties` doc comment on the class stub

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


## References

