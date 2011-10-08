<?php

require '../src/classfinder.php';
require '../src/dependencysorter.php';

$finder = new \TheSeer\Autoload\ClassFinder(true);
$finder->parseFile('../tests/_data/dependency/file2.php');
$finder->parseFile('../tests/_data/dependency/file1.php');

$found = $finder->getClasses();


$x = new \TheSeer\Autoload\classDependencySorter($found, $finder->getDependencies());
$r = $x->process();
var_dump($found, $finder->getDependencies(), $r);
