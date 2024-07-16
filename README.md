# PHP RFC drafts and ideas

This is a repo where I keep ideas and RFC drafts for changes to PHP.

Discussions are *not* accepted on this repo and must be held on the PHP internals list.
The only PRs that will be accepted are typo and wording improvements,
or when I have explicitly requested suggestions.

Note: if an RFC is published it will follow the license of the PHP Wiki.

## Script to convert markdown to custom DokuWiki syntax

Use the `md-to-dokuwiki.php` script to generate DokuWiki text that *mostly* works without edits from a markdown file.

##  Future possible RFC ideas

 - Meta RFC transform empty into a function 
 - Remove old DBA handlers
 - Deprecate bool to string implicit coercions
 - Change `exit()` to function
 - Warn when int to float loses precision + introduce SAFE_INTEGER constant like JS
 - Deprecate `InfiniteIterator`
 - Change `eval()` to a function that can be disabled
 - Object serialization overhaul
 - Deprecate implicit conversions to bool in function type juggling context
