# PHP RFC: Make the GMP class final

- Version: 0.1
- Date: 2024-06-29
- Author: Gina Peter Banyard <girgias@php.net>
- Status: Under Discussion
- Target Version: PHP 8.4
- Implementation: TBD
- First Published at: <https://wiki.php.net/rfc/gmp-final>

## Introduction

The `GMP` class was introduced as part of the
[Internal operator overloading and GMP improvements](https://wiki.php.net/rfc/operator_overloading_gmp)
RFC.

This RFC also converted the GMP extension to use an object instead of a resource.
One of the first proposals of its kind.
Since then numerous resources have been converted to what we call opaque objects. [1][2][3]
And the finalization of the process was codified in the
[Resource to object conversion](https://wiki.php.net/rfc/resource_to_object_conversion)
RFC which was accepted in late 2023.

One element of the resource to object migration is to make the object opaque,
which in general implied, being `final`, being not serializable,
not constructible via `new`, cannot be cast, and to not implement any methods.

The `GMP` class is a notable exception to many of the above restrictions.
It is a non-final class, that can be instantiated via `new`, and is serializable.
The serialization aspect was intended as part of the initial RFC,
but the other aspects were not.
Indeed, the `GMP` class did not have a proper constructor to initialize the object until
PHP 8.2.4 after this fact was discovered and discussed on the mailing list in late December 2022. [4]

As such, we deem the fact that `GMP` was not made `final` an oversight and want to correct this.

## Proposal

Make the `GMP` class final.

## Backward Incompatible Changes

Any userland class that extends `GMP` would now throw a compile time error.

From an analysis of private and publicly available code bases with [Exakat](https://www.exakat.io/en/)
no one has ever extended this class, as such the BC Break is mostly theoretical.

## Further Motivation

In the discussion of the new `BCMath\Number` class introduced via the
[Support object type in BCMath](https://wiki.php.net/rfc/support_object_type_in_bcmath)
RFC, it was *explicitly* discussed if the class should be `final` or not.
And the consensus was that it should be `final` due to concerns about overloading the methods.

However, people seemingly want to "enhance" `GMP` to achieve userland operator overloading
by hooking into it's logic when extending it.
This is in total contradiction with the above RFC discussion which happened a few months ago.
This resembles more of a "hack" than anything else and support for a proper form of operator overloading [5][6]
should be revisited instead.
Another alternative would be to create a dedicated PHP extension with an object that overloads the `do_operator` object handler.

Therefore, we think it is crucial to close this avenue for discussion once and for all.

## Version

Next minor version, which is PHP 8.4.

## Vote

VOTING_SNIPPET

## References

[1] "PHP RFC: Migration Hash Context from Resource to Object": https://wiki.php.net/rfc/hash-context.as-resource

[2] "Convert ext/xml to use an object instead of resource" PHP Internals Mailing List thread: https://externals.io/message/104361

[3] "Resource to opaque object migration" php/php-tasks issue: https://github.com/php/php-tasks/issues/6

[4] "What to do with opaque GMP objects which allows instantiation" PHP Internals Mailing List thread: https://externals.io/message/119216

[5] "PHP RFC: Userspace operator overloading": https://wiki.php.net/rfc/userspace_operator_overloading

[6] "PHP RFC: User Defined Operator Overloads": https://wiki.php.net/rfc/user_defined_operator_overloads
