# PHP RFC: Transform void into an alias for null

- Version: 0.1
- Date: 2025-MM-DD
- Author: Gina Peter Banyard <girgias@php.net>
- Status: Under Discussion
- Target Version: PHP 8.5
- Implementation: TBD
- First Published at: https://wiki.php.net/rfc/void-as-null

## Introduction

The `void` type was introduced in PHP 7.1 with the
[Void return type](https://wiki.php.net/rfc/void_return_type)
RFC.
The motivation for introducing this type was to "specify that no value should be returned"
for documentation and error-checking purposes.

Although in PHP all functions return a value, the implicit default being `null`.
The rationale for using `void` instead of `null` is laid out in the
["Why call it void and not null?"](https://wiki.php.net/rfc/void_return_type#why_call_it_void_and_not_null)
subsection of the RFC.

> Some have suggested that the return type be named `null` instead,
> since (as previously mentioned) PHP implicitly produces `null` as the result value
> for functions which don't explicitly return something,
> so `void` would be almost the same as just enforcing that a function returns `null`.
> Plus, `void` might suggest a function that can't be used in an expression,
> whereas `null` wouldn't.
> Also, `void` would be a new “type” in a sense, whereas `null` is preexisting.
> 
> The main reason to choose `void` over `null` is that it is the customary name to use for such a return type.
> We already use `void` rather than `null` when documenting functions in PHP [...]
> In addition, Hack, a PHP-derived and -compatible language [...] also uses `void`.
> [...]
> Since `void` seems to be the most popular choice for such a return type,
> both in PHP and elsewhere, why should we name it something different?
> There's no precedent for it and the name doesn't seem to have been an issue until now.
> 
> The other reason is that `void` more clearly conveys that the function is supposed to not return a value,
> rather than return `null` specifically.

Part of the proposal introducing `void` lays out the variance rules applying to `void`:

> A `void` return type cannot be changed during inheritance.
> You can see this as either because return types are invariant,
> or because they are covariant and nothing is a subclass of `void`.

These variance rules were accurate at the time of the RFC.
However, since PHP 7.1 various changes have been made to PHP's type system:
 - Return type can now be [covariant](https://wiki.php.net/rfc/covariant-returns-and-contravariant-parameters)
 - Introduction of the `mixed` [top type](https://wiki.php.net/rfc/mixed_type_v2)
 - Introduction of the `never` [bottom type](https://wiki.php.net/rfc/noreturn_type)
 - Introduction of the `null` type, first via [union types](https://wiki.php.net/rfc/union_types_v2), and then [standalone](https://wiki.php.net/rfc/null-false-standalone-types) usage

Thus, the variance semantics of `void` in PHP 8.4 are slightly different from those previously mentioned:
- `never` is a subtype of `void`
- `never` is a subtype of `mixed`
- `void` *is not* a subtype of a function with a `mixed` return type
- `void` *is* a subtype of a function without any return type
- `mixed` *is* a subtype of a function without any return type

This means the following class hierarchy is valid:
```php
<?php

class P {
    public function foo() {}
}

class C1 extends P {
    public function foo(): void {}
}

class CC1 extends C1 {
    public function foo(): never {}
}

class C2 extends P {
    public function foo(): mixed {}
}

class CC2 extends C2 {
    public function foo(): never {}
}

?>
```

This "split" type hierarchy where `void` is its own independent branch from the rest of the usual type system is rather nonsensical. 
Moreover, this means that a function without a defined return type is not isomorphic to one which defines a return type of `mixed`,
something which is not the case for parameter types as `void` is not a valid parameter type.
This is a needless complication of PHP's type system.

Future Scope:
- Functions/methods without return types now have a mixed type
- Function/methods parameters without an explicit type are now mixed
- ReflectionFunctionAbstract and ReflectionParameter hasReturnType/hasType would always be true and getReturnType/getType would never return null


## Proposal

Transform `void` into a compile-time alias for `null`.

## Backward Incompatible Changes

None.

Internal extensions that specify arg infos will have `void` types automatically normalized to `null`
via the `zend_normalize_internal_type()` engine API.

TODO phrase this better
All existing compile time errors and deprecation with the exception of `return null;` continue to be emitted.

## Version

Next minor version, PHP 8.5.

## Vote

VOTING_SNIPPET

## Future scope


## References

