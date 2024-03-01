# PHP RFC: Overhaul object serialization

- Version: 0.1
- Date: 2024-03-01
- Author: Gina Peter Banyard <girgias@php.net>
- Status: Under Discussion
- Target Version: PHP 8.4
- Implementation: TBD
- First Published at: <http://wiki.php.net/rfc/object-serialization-overhaul>

## Introduction

Objects in PHP are serializable by default using the built-in serialization format of `serialize()`.
However, the default behaviour might not be ideal for all objects,
and PHP has added a plethora of options to hook into the serialization and deserialization of objects.

The initial way to hook into the serialization of objects were the `__sleep()` and `__wakeup()`
magic methods that were introduced at the same time as objects in PHP 4.

A second way to hook into the serialization of object was introduced in PHP 5.1 via the `Serializable` interface,
it was introduced to circumvent some short-comings of `__sleep()` and `__wakeup()`.
However, this way of hooking into object serialization was deprecated in
[PHP 8.1](https://wiki.php.net/rfc/phase_out_serializable)
after the introduction of a
[third way to hook into object serialization in PHP 7.4](https://wiki.php.net/rfc/custom_object_serialization)
via the `__serialize()` and `__unserialize()` magic methods, to address further short-comings.

One final issue is that some objects should not be serialized,
something userland has enforced by implementing the various hooks to make them throw exceptions.
To enforce this invariant, the engine has a dedicated `zend_class_entry` (CE for short) flag named `ZEND_ACC_NOT_SERIALIZABLE`
which prevents (de)serialization of objects of classes which have this flag by having
`(un)serialize()` throw an exception when encountering such objects.

There was a proposal recently to expose this flag by introducing the
[`#[NotSerializable]` attribute](https://wiki.php.net/rfc/not_serializable)
which gathered mixed reception.
One issue with this CE flag is that once it is set, there is no way to remove it.
This was an issue with DOM (TODO LINK AND EXPLAIN) for which the solution was to remove the CE flag,
and implement throwing `__sleep()` and `__wakeup()` methods, as those could be overridden.

One proposed alternative to the `#[NotSerializable]` attribute,
is to add a "marker" interface named `NotSerializable`,
and have any serialization tool do an `instanceof` check to see if the class is not serializable.
This alternative is nonsensical.
Interfaces, and types in general, showcase _capabilities_ not _limitations_ bestowed upon values of said type.
To have such a marker type be useful on the type system level would require the introduction of refined types
(either via a proposed syntax `type SerializableObject = {v:object | !(v instanceof NotSerializable)`
TypeScript [1:https://arxiv.org/abs/1604.02480],
Haskell [1:https://popl18.sigplan.org/details/PLMW-POPL-2018/6/Liquid-Haskell-Refinement-Types-for-Haskell];
or using attributes as prototyped in Rust [1:https://dl.acm.org/doi/10.1145/3591283]),
dependent types (as they exist in Lean, Coq, or Agda),
or a Not/Complement type which does not exist in any other language
as far as we can tell and is only a proposal for Python [1:https://github.com/python/typing/issues/801] 
This is a strong ask considering such types are not yet supported in any PHP static analysis tool,
and are unlikely to be added to PHP natively in the foreseeable future.

Moreover, in an ideal world, classes that declare a typed property whose type is not serializable
should also not be serializable unless they overwrite the default serialization behaviour.

Considering all of the above, a comprehensive proposal is needed to improve the current situation around object serialization.

## Proposal

We propose a set of three changes to improve the current situation around object serialization.

### Deprecation of the `__sleep()` and `__wakeup()` magic methods

`__serialize()` and `__unserialize()` completely subsume the functionality
of `__sleep()` and `__wakeup()` and is more capable.

To reduce the confusion about how one hooks into the serialization mechanism for objects
we propose to deprecate `__sleep()` and `__wakeup()` in favour of `__serialize()` and `__unserialize()` respectively.

### Add the `#[NotSerializable]` attribute

As proposed in the [`#[NotSerializable]` attribute](https://wiki.php.net/rfc/not_serializable) RFC.
With the change that implementing `__serialize()` and `__unserialize()` on a child class removes the CE flag.
This is achieved via the next change.

### Drop methods from `Serializable` interface and auto-implement it on capable objects

This partially reverts the
[Phasing out Serializable](https://wiki.php.net/rfc/phase_out_serializable) RFC,
and takes inspiration from the [Stringable](https://wiki.php.net/rfc/stringable) RFC.

Any object that does **not** hook into the serialization mechanism,
or objects that define and implement *both* `__serialize()` and `__unserialize()`
would auto-implement the `Serializable` interface.

TODO: Describe behaviour when manually implementing Serializable

Having the interface allows us to remove the CE flag via the `interface_gets_implemented`
engine hook.

TODO Check this actually works!!!

TODO Make it implement default `__serialize()` and `__unserialize()` for naked objects

## Backward Incompatible Changes

None outside the deprecation of `__sleep()` and `__wakeup()`

## Version

Next minor version, PHP 8.4.

## Vote

VOTING_SNIPPET

## Future scope

This proposal paves a way for objects to become non-serializable by default in the future,
however we have no interest in doing so.

## References

