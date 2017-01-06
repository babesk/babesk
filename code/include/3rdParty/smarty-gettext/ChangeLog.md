## [1.5.0] - 2016-11-03

IMPROVEMENTS:
  - fix for `template_dir` is array without key of zero defined. [#14]

## [1.4.0] - 2016-06-13

IMPROVEMENTS:
  - add context support to `{t}` via [azatoth/php-pgettext]. [#3]
  - add php 7.0 to supported versions

## [1.3.0] - 2015-11-14

IMPROVEMENTS:
  - Smarty 3.1 compatibility fixes in `{locale}`. [#8], [#10]

## [1.2.0] - 2015-05-11

IMPROVEMENTS:

  - added new function `{locale path="" domain="" stack="push|pop"}` to push and pop gettext catalogs. [#7]

INCOMPATIBLE CHANGES:
  - `context` parameter in `{t}` is reserved for special purpose. [#3]

## [1.1.1] - 2014-11-12

IMPROVEMENTS:

  - documentation updates
  - use `error_log` as `STDERR` is not available in all `SAPI` variants
  - decrease size of composer package when installed with `prefer-dist`

BUG FIXES:
  - fix manual page syntax [#2]

## [1.1.0] - 2013-12-26

IMPROVEMENTS:

  - add line numbers support. adopted [patch](https://sourceforge.net/p/smarty-gettext/patches/3/) from old project
  - add domain parameter support. adopted [patch](https://sourceforge.net/p/smarty-gettext/patches/5/) from old project.

INCOMPATIBLE CHANGES:
  - `domain` parameter in `{t}` has special meaning now.

BUG FIXES:
  - `tsmarty2c.php` did not find plural text. [d0330f](https://github.com/smarty-gettext/smarty-gettext/commit/d0330f)

## [1.0.1] - 2013-11-21

New maintainer: Elan Ruusamäe

IMPROVEMENTS:

  - added manual page for `tsmarty2c.php` (from Debian)
  - `tsmarty2c.php` outputs now valid `.po` format (can be used to merge with `.pot`)
  - added composer repository. add to your `composer.json`: `"smarty-gettext/smarty-gettext"`
  - unit tests!
  - more accurate JavaScript escaping

BUG FIXES:

  - Do not show `.po` file headers on translated pages. (Debian bug: [#680754][1])
  - [PATCH] parse tags properly using {} in parameters ([Kalle Volkov][2])
  - Do not stripslashes(). (magic quotes is deprecated and stripslashes should not be done unconditionally)

## [1.0b1] - 2005-07-27 Sagi Bashari

* README:
	- Redone

* smarty-gettext.php:
	- Renamed file to block.t.php

* block.t.php:
	- Rename `smarty_translate()` to `smarty_block_t()`
	- Rename `strarg()` to `smarty_gettext_strarg()`
	- Better comments, new installation method
	- url escaping method

* tsmarty2c.php:
	- Use 'env php' as php bin path
	- Output file name along with ripped strings
	- Comments, wrapping

## [0.9.1] - 2004-04-30 Sagi Bashari

* README:
	- replace smarty_gettext with smarty-gettext
	- correct package name, project urls, add vrsion

* tsmarty2.c:
	- check if file extension exists before checking if is in array ([Florian Lanthaler][3])
	- correct package name, project urls, add version

* smarty_gettext:
	- rename to smarty-gettext
	- correct package name, project urls, add version

## [0.9] - 2004-03-01 Sagi Bashari

* tsmarty2c.php:
	- added support for directories (originally by [Uros Gruber][4])
	- fixed bug that prevented more than 1 block per line (reported by [Eneko Lacunza][5])
	- convert new line to `\n` in output string

* smarty_gettext.php:
	- run `nl2br()` when escaping html


  [1]: http://bugs.debian.org/680754
  [2]: mailto:kalle.volkov@hiirepadi.ee
  [3]: mailto:florian@phpbitch.net
  [4]: mailto:uros.gruber@vizija.si
  [5]: mailto:enlar@euskal.org
 [#2]: https://github.com/smarty-gettext/smarty-gettext/issues/2
 [#3]: https://github.com/smarty-gettext/smarty-gettext/issues/3
 [#7]: https://github.com/smarty-gettext/smarty-gettext/pull/7
 [#8]: https://github.com/smarty-gettext/smarty-gettext/issues/8
 [#10]: https://github.com/smarty-gettext/smarty-gettext/pull/10
 [#14]: https://github.com/smarty-gettext/smarty-gettext/pull/14
[azatoth/php-pgettext]: https://packagist.org/packages/azatoth/php-pgettext

[1.5.0]: https://github.com/smarty-gettext/smarty-gettext/compare/1.4.1...1.5.0
[1.4.0]: https://github.com/smarty-gettext/smarty-gettext/compare/1.3.0...1.4.0
[1.3.0]: https://github.com/smarty-gettext/smarty-gettext/compare/1.2.0...1.3.0
[1.2.0]: https://github.com/smarty-gettext/smarty-gettext/compare/1.1.1...1.2.0
[1.1.1]: https://github.com/smarty-gettext/smarty-gettext/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/smarty-gettext/smarty-gettext/compare/1.0.1...1.1.0
[1.0.1]: https://github.com/smarty-gettext/smarty-gettext/compare/1.0b1...1.0.1
[1.0b1]: https://github.com/smarty-gettext/smarty-gettext/compare/0.9.1...1.0b1
[0.9.1]: https://github.com/smarty-gettext/smarty-gettext/compare/0.9...0.9.1
[0.9]: https://github.com/smarty-gettext/smarty-gettext/commits/0.9
