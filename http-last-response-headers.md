# PHP RFC: Add http_get_last_request_headers() function

- Version: 0.1
- Date: 2023-12-22
- Author: Gina Peter Banyard <girgias@php.net>
- Status: Under Discussion
- Target Version: PHP 8.4
- Implementation: [https://github.com/php/php-src/pull/12500](https://github.com/php/php-src/pull/12500)
- First Published at: [http://wiki.php.net/rfc/http-last-response-headers](http://wiki.php.net/rfc/http-last-response-headers)

## Introduction

The [``$http_response_header``](https://www.php.net/manual/en/reserved.variables.httpresponseheader.php)
variable is magically created in the local scope whenever an HTTP request is performed through PHP's stream layer,
i.e. when using the [HTTP wrapper](https://www.php.net/manual/en/wrappers.http.php).
One such usage is using ``file_get_content()`` to retrieve the content of a URL.


The [``$http_response_header``](https://www.php.net/manual/en/reserved.variables.httpresponseheader.php)
variable will contain all the HTTP headers that were encountered during the request performed via the HTTP wrapper.

Because creating a variable in the local scope is a terrible way of returning additional information,
we have removed all other features using this operating principle,
such as [``$php_errormsg``](https://www.php.net/manual/en/reserved.variables.phperrormsg.php).
This variable was initially slated [to be deprecated in PHP 8.1](https://wiki.php.net/rfc/deprecations_php_8_1#predefined_variable_http_response_header),
but due to the lack of convenient alternatives it was removed from the proposal.

Indeed, this variable is created and populated even if the HTTP request fails.
Something that is only possible to achieve when dropping down to the stream layer when providing an additional
stream context which ignores errors.
Which requires manually parsing and detecting if the HTTP request failed or not instead of just checking if the return value is ``false``.

Thus, we propose adding functions similar to ``error_get_last()``/``error_clear_last()`` which replaced
[``$php_errormsg``](https://www.php.net/manual/en/reserved.variables.phperrormsg.php).

## Proposal

Add the following two functions:
 - ``function http_get_last_response_header(): ?array``
 - ``function http_clear_last_response_header(): void``

``http_get_last_response_header()`` would behave like
[``$http_response_header``](https://www.php.net/manual/en/reserved.variables.httpresponseheader.php)
if a request via the HTTP wrapper is made and return the headers as an array even when the request fails.
If no request via the HTTP wrapper is made, or the previous headers have been cleared by using
``http_clear_last_response_header()``, then ``null`` is returned.

## Backward Incompatible Changes

As this RFC does not yet propose deprecating
[``$http_response_header``](https://www.php.net/manual/en/reserved.variables.httpresponseheader.php)
there are no backward incompatible changes.

However, considering the possible engine and optimizer simplifications by removing
the last usage of the engine creating a variable, we have conducted a usage analysis of this feature.
We have found 65 usages in 30 projects across over 900 projects
(composer packages, standalone open source projects, and private codebases) that were analyzed with [Exakat](https://www.exakat.io). [1]
Most of those usages stem from packages like Guzzle or OAuth client libraries.

It is impossible to polyfill this feature, however it is possible to write cross version compatible code
in the following way:
```php
$content = file_get_contents('http://example.com/');
if (function_exists('http_get_last_response_headers')) {
    $http_response_header = http_get_last_response_headers();
}
// Use $http_response_header as before
```

Considering the low usage of this feature, the possibility to write cross version compatible code,
and the possible engine and optimizer simplifications,
it seems reasonable to be able to remove this feature even without a deprecation notice if the need arises.

## Version

Next minor version, PHP 8.4.

## Vote

VOTING_SNIPPET

## References

[1] https://gist.github.com/exakat/454e503458e231dc0695837ad2561540
