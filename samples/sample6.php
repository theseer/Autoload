<?php

require '../src/classfinder.php';
require '../src/dependencysorter.php';

$finder = new \TheSeer\Tools\ClassFinder(true);
$finder->parseFile('../tests/_data/dependency/file2.php');
$finder->parseFile('../tests/_data/dependency/file1.php');

$found = $finder->getClasses();


$x = new \TheSeer\Tools\classDependencySorter($found, $finder->getDependencies());
$r = $x->process();
var_dump($found, $finder->getDependencies(), $r);
