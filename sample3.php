<?php

  require 'src/classfinder.php';
  require 'src/phpfilter.php';
  require 'src/autoloadbuilder.php';

  require '../scanner/src/directoryscanner.php';
  require '../scanner/src/filter.php';

  $scanner = new \TheSeer\Tools\DirectoryScanner;
  $scanner->addInclude('*.php');

  $finder = new \TheSeer\Tools\ClassFinder;

  $found = $finder->parseDirectory($scanner('.'));

  $ab = new \TheSeer\Tools\AutoloadBuilder($found);
  $ab->omitClosingTag(false);
  echo $ab->render();
