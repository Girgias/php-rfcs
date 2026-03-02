# PHP Policy RFC: Exempt input type and value validation from BC Break policy

- Version: 0.1
- Date: 2026-02-18
- Author: Gina Peter Banyard <girgias@php.net>
- Status: Under Discussion
- First Published at: https://wiki.php.net/rfc/policy-exempt-type-value-error-bc-policy

## Introduction

PHP's release cycle does not only affect the release of the core language,
but also all extensions that are "bundled".
Bundled extensions are all the extensions that live in the php-src monorepo.
This includes many extensions that are not mandatory such as ext/snmp or ext/curl.

Many of these bundled extensions also haven't been given much love and maintenance in prior years.
As such, many functions/methods from those extensions don't properly validate their inputs, and sometimes even types.

It was customary to add this validation as part of the clean-up passes made in order to bring up PHP's entire standard library to an acceptable standard.
This was also supported by our current policy:

> Fixing clearly incorrect or unintended behavior, even if it changes the output or side effects of a function, is not automatically considered a BC break.
> This applies to behavior that was buggy, undocumented, or inconsistent with expectations or similar functionality.
> The potential impact of such a fix SHOULD be evaluated, and based on that, the change MAY be treated as a BC break if it is likely to affect real-world code in significant or disruptive ways.

However, recently there has been an increased amount of unconditional requests for an RFC for adding the validation,
even if it is unlikely to “affect real world in significant ways”.
This adversely impacts day-to-day maintenance of the php-src monorepo,
as clean-up passes now take multiple weeks and adds unnecessary friction for new contributions.

On the contrary, not adding the validation is making silent BC breaks more likely.
An example demonstrating this is a function that has a parameter using "int-based enum constants" and/or bitmasks.
Indeed, if a user provides a non-existent value, such as `8`,
when there only exists a few constants for predefined values
(e.g `1`, `2`, `4` (typical for bitmasks))
then adding a *new* constant with value `8` (e.g. the underlying librarie provides a new bitmask flag) will silently change the behaviour.
This is *explicitly* recommended *against* in our BC policy:

> Backward compatibility breaks in minor versions SHOULD NOT result in silent behavioral differences.
> Instead any breaking change SHOULD be "obvious" when executing the program.

We therefore consider it necessary to explicitly include this in the policy.

Finally, we note that the lack of input validation has resulted in security vulnerabilities multiple times.
[1:https://github.com/php/php-src/security/advisories/GHSA-www2-q4fc-65wf]
[1:https://github.com/php/php-src/security/advisories/GHSA-3cr5-j632-f35r]
[1:https://github.com/php/php-src/security/advisories/GHSA-h96m-rvf9-jgm2]
Whose fixes were the addition of `Error`s being thrown in stable releases,
this further indicates that these sort of behavioural changes should be tackled when discovered rather than delayed until they cause security vulnerabilities.

## Proposal

Accept the policy in https://github.com/php/policies/pull/27

Exempt the wording of error messages and the type and value validation of input values to function/methods/properties from the BC Break policy. 

This includes, but is not limited to:
 - Rephrasing diagnostic (such as `E_WARNING`s) messages
 - Rephrasing messages of `Exception`, `Error`, and derived classes
 - Adding `TypeError`s to check inputs are:
   - Of the correct type
   - Array values are of the correct type
 - Adding `ValueError`s to check inputs are:
   - Within a range
   - Not empty
   - Do not contain null bytes
   - Are only composed of valid bitmask flags
   - One of the expected enum like integer constants
   - Array keys are all strings
   - Array keys are all integers
 - Promoting diagnostics (such as `E_WARNING`) to `TypeError`s
 - Promoting diagnostics (such as `E_WARNING`) to `ValueError`s, only if the trigger for the diagnostic is easily preventable 

Note that this list is non-exhaustive.

## Open Issues

None.

## Future Scope

None.

## Voting Choices

VOTING_SNIPPET

## Patches and Tests

  * https://github.com/php/policies/pull/27

## Implementation

  * TBD

## Rejected Features

None.

# Changelog

## References

  * Discussion thread: TBD
  * Voting thread: TBD
