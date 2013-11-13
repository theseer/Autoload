# PHP Autoload Builder

The PHP AutoloadBuilder CLI tool **phpab** is a command line application to automate the process of generating
an autoload require file with the option of creating static require lists as well as phar archives.

## Features

* scan multiple directories recursively in one run, optionally follow symlinks
* Template based autoload code
* Custom variables for templates
* Compatibility mode for PHP 5.2 compliant autoloader
* Case sensitive as well as case insensitive classname mapping
* Phar generation, with or without compression and openssl key signing
* Static require list generation
* Linting of generated code

## Requirements

* PHP 5.3+
* Tokenizer (ext/tokenizer)
* For PHAR generation support:
    + ext/phar (write enabled: phar.readonly = Off)
    + ext/gzip (optional)
    + ext/bzip2 (optional)
    + ext/openssl (optional, for phar signing only)

## Installation

### Using PEAR

**phpab** can be installed using the [PEAR Installer](http://pear.php.net/manual/en/guide.users.commandline.cli.php).
This installer is the backbone of PEAR, which provides a distribution system for PHP packages, and is shipped
with every release of PHP since version 4.3.0.

The PEAR channel (`pear.netpirates.net`) that is used to distribute **phpab** needs to be registered with the
local PEAR environment. This can be done automatically if PEAR is configured to auto-discover channels:

```
[theseer@rikka ~]$ sudo pear config-set auto_discover 1
```

You are now ready to install the PHP Autoload Builder (phpab):

```
[theseer@rikka ~]$ sudo pear install pear.netpirates.net/Autoload
```

This should install phpab along with its dependencies, once completed, you can verify the success as follows:

```
[theseer@rikka ~]$ phpab -v
phpab 1.12.0 - Copyright (C) 2009 - 2013 by Arne Blankerts
```

### Executable PHAR

Alternativly **phpab** can be downloaded as a fully self contained PHAR archive:

* [Version 1.12.0](http://phpab.net/phpab-1.12.0.phar) - 170kb
* [Version 1.11.0](http://phpab.net/phpab-1.11.0.phar) - 170kb
* [Version 1.10.3](http://phpab.net/phpab-1.10.3.phar) - 170kb
* [Version 1.10.2](http://phpab.net/phpab-1.10.2.phar) - 169kb
* [Version 1.10.1](http://phpab.net/phpab-1.10.1.phar) - 185kb

_Please note:_
On Linux/Unix based system the phar needs to be marked executable for direct execution:
```
[theseer@rikka ~]$ chmod +x phpab*.phar
```


## Other Downloads

* [Latest development snapshot](https://github.com/theseer/Autoload/archive/master.zip)</a> (ZIP Archive)
* [Releases (Source)](https://github.com/theseer/Autoload/tags)
* [Pear Packages](http://pear.netpirates.net/)

## Usage
```
phpab [switches] <directory> [... <directoryN>]

    -i, --include    File pattern to include (default: *.php)
    -e, --exclude    File pattern to exclude

    -b, --basedir    Basedir for filepaths
    -t, --template   Path to code template to use

    -o, --output     Output file for generated code (default: STDOUT)
    -p, --phar       Create a phar archive (requires -o )
        --bzip2      Compress phar archive using bzip2 (bzip2 required)
        --gz         Compress phar archive using gzip (gzip required)
        --key        OpenSSL key file to use for signing phar archive (openssl required)

    -c, --compat     Generate PHP 5.2 compatible code
    -s, --static     Generate a static require file

    -n, --nolower    Do not lowercase classnames for case insensitivity

        --follow     Enables following symbolic links (not compatible with phar mode)
        --format     Dateformat string for timestamp
        --linebreak  Linebreak style (CR, CR/LF or LF)
        --indent     String used for indenting (default: 3 spaces)

        --tolerant   Ignore Class Redeclarations in the same file
        --once       Use require_once instead of require when creating a static require file

        --all        Include all files in given directory when creating a phar

        --trusting   Do not check mimetype of files prior to parsing (default)
        --paranoid   Do check mimetype of files prior to parsing

        --var name=foo  Assign value 'foo' to variable 'name' to be used in (custom) templates

        --lint       Run lint on generated code and exit
        --lint-php   PHP binary to use for linting (default: /usr/bin/php or c:\php\php.exe)

    -h, --help       Prints this usage information
    -v, --version    Prints the version and exits
```
### Usage Examples

    [theseer@rikka ~]$ phpab -o src/autoload.inc.php src

    [theseer@rikka ~]$ phpab -c -o src/autoload.inc.php src

    [theseer@rikka ~]$ phpab -o src/core/autoload.inc.php -b src src

    [theseer@rikka ~]$ phpab -p -o framework.phar framework/src

    [theseer@rikka ~]$ phpab -p -o framework.phar --bzip2 --key sign.key framework/src

    [theseer@rikka ~]$ phpab -b . --tolerant -o zf1_autoload.php -e '*/Test/*' Zend


### Automation

When using *phpab* it is necessary to recreate the autoload file every time a new class is created.
This usually also happens after pulling from a repo or when switchting branches.
Using a git `post-checkout` hook placed in `.git/hooks/post-update` this can be automated for most cases.

####Basic Sample:

```bash
#!/bin/bash
phpab -c -o src/autoload.inc.php src
```

####Sample using an `ant build.xml` file.

```bash
#!/bin/bash
if [ -f build.xml ]; then
    ant -p | grep phpab > /dev/null

    if [ $? -eq 0 ]; then
        ant phpab > /dev/null &
    fi
fi
```


## Template Variables

The generated code is based uppon templates provided by default in the templates subfolder. The template engine
allows for simply replacing of name based placeholders. For now, only a few default variables are defined
but API hooks / CLI parameters exist to set custom variables.

Known variables are:
* ```___CREATED___```     Set to a timestamp of creation, format can be adjusted
* ```___CLASSLIST___```   The found list classes in form of a generated map
* ```___BASEDIR___```     If a Basedir is set, the value will get removed from the file path and get replaced by __DIR__

Used in PHAR Mode only:
* ```___PHAR___```         The filename of the generated phar (see src/templates/phar.php.tpl)

Custom variables as defined by passing --var name=value via cli are accessed by pre- and appending ___ to it:
* ```___name___```         Going to be replaced by the value provided via cli param
