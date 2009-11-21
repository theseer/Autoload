<?php

  require 'src/classfinder.php';
  require 'src/phpfilter.php';

  require '../scanner/src/directoryscanner.php';
  require '../scanner/src/filter.php';

  $scanner = new \TheSeer\Tools\DirectoryScanner;
  $scanner->addInclude('*.php');

  $finder = new \TheSeer\Tools\ClassFinder;

  $rc = $finder->parseDirectory($scanner('.'));
  var_dump($rc);
