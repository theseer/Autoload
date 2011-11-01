<?php
spl_autoload_register(
   function($class) {
      static $classes = null;
      if ($classes === null) {
         $classes = array(
            ___CLASSLIST___
         );
      }
      if (isset($classes[$class])) {
         require 'phar://___PHAR___' . $classes[$class];
      }
   }
);
__HALT_COMPILER();
