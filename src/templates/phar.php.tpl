<?php
spl_autoload_register(
   function($class) {
      static $classes = array(
         ___CLASSLIST___
      );
      $cn = strtolower($class);
      if (isset($classes[$cn])) {
         require 'phar://___PHAR___' . $classes[$cn];
      }
   }
);
__HALT_COMPILER();
