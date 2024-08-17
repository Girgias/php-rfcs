# PHP RFC drafts and ideas

This is a repo where I keep ideas and RFC drafts for changes to PHP.

Discussions are *not* accepted on this repo and must be held on the PHP internals list.
The only PRs that will be accepted are typo and wording improvements,
or when I have explicitly requested suggestions.

Note: if an RFC is published it will follow the license of the PHP Wiki.

## Script to convert markdown to custom DokuWiki syntax

Use the `md-to-dokuwiki.php` script to generate DokuWiki text that *mostly* works without edits from a markdown file.

##  Future possible RFC ideas

### RFCs Actively being worked on

- Container/offset semantics overhaul

### Priority ideas

These are in no particular order, but they are RFCs that I want to work on sooner than later

- Comparison semantics overhaul
- PDOException, fix `code` property being a `string`
- Deprecate implicit conversions from `resource` to `string`
- Remove assumption about multiplication `*` being commutative
- Remove usage of SPL extensions in other internal extensions
- Warn when int to float loses precision + introduce SAFE_INTEGER constant like JS
- Change `eval()` to a function that can be disabled
- Make `void` an alias for `null`
- Object serialization overhaul

### Remainder of ideas

 - Meta RFC transform empty into a function 
 - Remove old DBA handlers
 - Deprecate bool to string implicit coercions
 - Deprecate `InfiniteIterator`
 - Deprecate implicit conversions to bool in function type juggling context
 - Deprecate control-flow structures in `finally` blocks
 - Stop requiring parameter names in PHP interface methods
 - Function types

## Suggested RFC ideas by other people

These are RFCs ideas that have been suggested during discussion offline or on the list

- Split `exit()` and `die()` into distinct functions where `die()` only take strings and `exit()` only takes integers.

## Accepted RFC

- Change `exit()` to function
