<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
spl_autoload_register(
    function($class) {
        static $classes = array(
            ___CLASSLIST___
        );
        if (isset($classes[$class])) {
            require ___BASEDIR___$classes[$class];
        }
     }
);
// @codeCoverageIgnoreEnd
