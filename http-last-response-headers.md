# PHP RFC: Add http_(get|clear)_last_request_headers() function

- Version: 0.1
- Date: 2024-01-03
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

All other features using this operating principle,
such as [``$php_errormsg``](https://www.php.net/manual/en/reserved.variables.phperrormsg.php),
have been removed because creating a variable in the local scope is a terrible way of returning additional information.
This variable itself was initially slated [to be deprecated in PHP 8.1](https://wiki.php.net/rfc/deprecations_php_8_1#predefined_variable_http_response_header),
but due to a lack of convenient alternatives it was removed from the proposal at that time.

This variable is created and populated even if the HTTP request fails,
a behaviour that requires dropping down to the stream layer, providing an additional
stream context to ignore errors.
Subsequently the user can manually parse the response header and detect if the HTTP request failed or not.
This is impractical and a better interface would be simply checking if the return value of the initial call was ``false``.

As a replacement, we propose adding functions similar to ``error_get_last()``/``error_clear_last()`` which replaced
[``$php_errormsg``](https://www.php.net/manual/en/reserved.variables.phperrormsg.php).

## Proposal

Add the following two functions:
 - ``function http_get_last_response_header(): ?array``
 - ``function http_clear_last_response_header(): void``

``http_get_last_response_header()`` would provide the same information as
[``$http_response_header``](https://www.php.net/manual/en/reserved.variables.httpresponseheader.php)
if a request via the HTTP wrapper is made, and also return the headers as an array even when the request fails.
If no request via the HTTP wrapper is made, or the last headers have been cleared by calling
``http_clear_last_response_header()``, then ``null`` is returned.

## Backward Incompatible Changes

As this RFC does not yet propose deprecating
[``$http_response_header``](https://www.php.net/manual/en/reserved.variables.httpresponseheader.php)
there are no backward incompatible changes.

Considering the possible simplification to the engine and the optimizer by removing
this last usage of the capability to create a variable in this way, we have conducted a usage analysis of the feature.
We found only 65 usages in 30 projects across a sample of over 900 projects
(composer packages, standalone open source projects, and private codebases) which were analyzed with [Exakat](https://www.exakat.io). [1]
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

Considering the sparse usage of this feature, the possibility to write cross version compatible code,
and the possible engine and optimizer simplifications,
it seems reasonable to slate this feature for removal even without a deprecation notice if the need arises.

## Version

Next minor version, PHP 8.4.

## Vote

VOTING_SNIPPET

## References

[1] https://gist.github.com/exakat/454e503458e231dc0695837ad2561540
