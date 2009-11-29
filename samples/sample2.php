<?php

  require 'src/classfinder.php';
  require 'src/phpfilter.php';

  require '../scanner/src/directoryscanner.php';
  require '../scanner/src/includeexcludefilter.php';
  require '../scanner/src/filesonlyfilter.php';


  $scanner = new \TheSeer\Tools\DirectoryScanner;
  $scanner->addInclude('*.php');

  $finder = new \TheSeer\Tools\ClassFinder;

  $rc = $finder->parseMulti($scanner('.'));
  var_dump($rc);
