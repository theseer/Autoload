<?php

  require '../src/classfinder.php';

  $finder = new \TheSeer\Autoload\ClassFinder;

  $rc = $finder->parseFile('src/classfinder.php');
  var_dump($rc);
