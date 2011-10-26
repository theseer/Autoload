<?php

  require __DIR__ . '/../autoload.php';
  require 'TheSeer/DirectoryScanner/autoload.php';

  $scanner = new \TheSeer\DirectoryScanner\DirectoryScanner;
  $scanner->addInclude('*.php');

  $finder = new \TheSeer\Autoload\ClassFinder;

  $found = $finder->parseMulti($scanner('../src'));

  $ab = new \TheSeer\Autoload\AutoloadBuilder($finder->getClasses());
  $ab->setIndent("\t");
  $ab->setLineBreak("\r\n");
  echo $ab->render();
