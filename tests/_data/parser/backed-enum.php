<?php declare(strict_types=1);

interface Sample {}

enum foo: string implements Sample {
    case A = "StaticStringA";
    case B = 'StaticStringB';
}
