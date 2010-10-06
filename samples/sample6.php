<?php

require '../src/classfinder.php';
require '../src/dependencysorter.php';

$finder = new \TheSeer\Tools\ClassFinder;
$found = $finder->parseFile('../tests/_data/dependency/file2.php');
$found = array_merge($found, $finder->parseFile('../tests/_data/dependency/file1.php'));


$x = new \TheSeer\Tools\classDependecySorter($found, $finder->getDependencies());
$r = $x->process();
var_dump($found, $finder->getDependencies(), $r);
