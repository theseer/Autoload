<?php

  require __DIR__ . '/../autoload.php';
  require 'TheSeer/DirectoryScanner/autoload.php';

  $scanner = new \TheSeer\DirectoryScanner\DirectoryScanner;
  $scanner->addInclude('*.php');

  $finder = new \TheSeer\Autoload\ClassFinder;

  $rc = $finder->parseMulti($scanner('../src'));
  var_dump($rc, $finder->getClasses());
