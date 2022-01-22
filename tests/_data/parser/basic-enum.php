<?php declare(strict_types=1);
namespace test;

enum foo {
    case x;
}

interface barInterface {}

enum bazEnum implements barInterface {
    case y;
}

enum barEnum : int {
    case x = 1;
}
