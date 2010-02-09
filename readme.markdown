PHP Autoload
============

Template Variables
------------------

The generated code is based uppon templates provided by default in the templates subfolder. The template engine
allows for simply replacing of name based placeholders. For now, only a few default variables are defined but API hooks exist
to set custom variables.

Known variables are:
    ___CREATED___     Set to a timestamp of creation, format can be adjusted
    ___CLASSLIST___   The found list classes in form of a generated map
    ___BASEDIR___     If a Basedir is set, the value will get removed from the file path and get replaced by __DIR__

In CLI Mode only:

    ___PHAR___         The filename of the generated phar (see src/templates/phar.php.tpl)


Requirements
------------

PHP: 5.3.0
Extensions: tokenizer, phar

The PHP tokenizer needs to be enabled for this code to work. Due to the use of Namespaces and closures, PHP 5.3 is required for running phpab
by default. Because the code has template support, it is possible to alternativly generate autoload code for older versions of PHP 5.

To generate phar archives, the phar writing support needs to be enabled within php. Details can be found in the [php manual](http://php.net/manual/en/phar.configuration.php)


PHP AutoloadBuilderCLI
======================

The PHP AutoloadBuilder CLI ist a command line application to automate the process of generating an autoload include file.


Installation
------------

phpab should be installed using the [PEAR Installer](http://pear.php.net/). This installer is the backbone of PEAR, which provides a distribution
system for PHP packages, and is shipped with every release of PHP since version 4.3.0.

The PEAR channel (`pear.netpirates.net`) that is used to distribute phpab needs to be registered with the local PEAR environment.
Furthermore, a component that phpab depends upon is hosted on the eZ Components PEAR channel (`components.ez.no`).

    [theseer@rikka ~]$ sudo pear channel-discover pear.netpirates.net
    Adding Channel "pear.netpirates.net" succeeded
    Discovery of channel "pear.netpirates.net" succeeded

    [theseer@rikka ~]$ sudo pear channel-discover components.ez.no
    Adding Channel "components.ez.no" succeeded
    Discovery of channel "components.ez.no" succeeded

This has to be done only once. Now the PEAR Installer can be used to install packages from the netpirates channel:

    [theseer@rikka ~]$ sudo pear install theseer/Autoload
    downloading Autoload-1.1.0.tgz ...
    Starting to download Autoload-1.1.0.tgz (7,596 bytes)
    .....done: 7,596 bytes
    downloading DirectoryScanner-1.0.1.tgz ...
    Starting to download DirectoryScanner-1.0.1.tgz (3,400 bytes)
    ...done: 3,400 bytes
    downloading ConsoleTools-1.6.tgz ...
    Starting to download ConsoleTools-1.6.tgz (869,925 bytes)
    ...done: 869,925 bytes
    install ok: channel://pear.netpirates.net/DirectoryScanner-1.0.1
    install ok: channel://components.ez.no/ConsoleTools-1.6
    install ok: channel://pear.netpirates.net/Autoload-1.1.0

After the installation you can find the phpab source files inside your local PEAR directory; the path in fedora linux 
usually is `/usr/share/pear/theseer`.


Usage
-----

phpab [switches] <directory>

    -i, --include    File pattern to include (default: *.php)
    -e, --exclude    File pattern to exclude

    -b, --basedir    Basedir for filepaths
    -t, --template   Path to code template to use

    -o, --output     Output file for generated code (default: STDOUT)
    -p, --phar       Create a phar archive (requires -o )

	--format     Dateformat string for timestamp
	--linebreak  Linebreak style (CR, CR/LF or LF)
        --indent     String used for indenting (default: 3 spaces)

        --lint       Run lint on generated code and exit
        --lint-php   PHP binary to use for linting (default: /usr/bin/php or c:\php\php.exe)

    -h, --help       Prints this usage information
    -v, --version    Prints the version and exits


Usaage Samples
--------------

    [theseer@rikka ~]$ phpab -o src/autoload.inc.php src

    [theseer@rikka ~]$ phpab -p -o framework.phar framework/src

