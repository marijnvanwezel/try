try - Simple CLI tool to try Composer packages
==============================================

**try** makes it super easy to try new Composer packages through the command-line. It was inspired by [timofurrer/try](https://github.com/timofurrer/try).

![](/docs/asciinema.gif)

Installation
============

Installation should be done through Composer:

```
composer global require marijnvanwezel/try
export PATH=$PATH:~/.config/composer/vendor/bin
```

This installs **try** as a system-wide binary.

Usage examples
==============

**Try single Composer package:**

```
try nikic/php-parser
try webmozart/assert
```

**Try multiple Composer packages in the same session:**

```
try nikic/php-parser webmozart/assert
```

**Try a specific version of a package:**

```
try webmozart/assert:1.10.0
```
