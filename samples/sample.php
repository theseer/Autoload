<?php

  require __DIR__ . '/../src/classfinder.php';

  $finder = new \TheSeer\Autoload\ClassFinder;

  $rc = $finder->parseFile(__DIR__ . '/../src/classfinder.php');
  var_dump($rc, $finder->getClasses());
