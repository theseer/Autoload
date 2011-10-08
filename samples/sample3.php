<?php

  require '../src/classfinder.php';
  require '../src/phpfilter.php';
  require '../src/autoloadbuilder.php';

  require '../../scanner/src/directoryscanner.php';
  require '../../scanner/src/includeexcludefilter.php';
  require '../../scanner/src/filesonlyfilter.php';

  $scanner = new \TheSeer\Autoload\DirectoryScanner;
  $scanner->addInclude('*.php');

  $finder = new \TheSeer\Autoload\ClassFinder;

  $found = $finder->parseMulti($scanner('../'));

  $ab = new \TheSeer\Autoload\AutoloadBuilder($found);

  echo $ab->render();
