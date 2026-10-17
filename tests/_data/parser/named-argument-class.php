<?php

class Foo
{
    public function __construct(public string $class) {}
}

new Foo(class: "bar");


function foo(string $class) {}

foo(class: "bar");

?>
