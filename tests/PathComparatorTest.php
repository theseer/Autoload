<?php

namespace TheSeer\Autoload\Tests {

    use TheSeer\Autoload\PathComparator;

    class PathComparatorTest extends \PHPUnit_Framework_TestCase {

        /**
         * @dataProvider directoriesProvider
         */
        public function testComparatorYieldsCorrectCommonBase(array $directories, $common) {
            $comparator = new PathComparator($directories);
            $this->assertEquals($common, $comparator->getCommonBase());
        }

        public function directoriesProvider() {
            return [
                'empty' => [
                    [], '/'
                ],
                'single' => [
                    [__DIR__], __DIR__
                ],
                'two' => [
                    [__DIR__, dirname(__DIR__)], dirname(__DIR__)
                ],
                'parents' => [
                    [__DIR__ . '/../src', __DIR__ . '/../tests/_data'], dirname(__DIR__)
                ],
                'with0' => [
                    [$a=__DIR__.'/_data/parser/trait0.php'], $a
                ],
                'dirwithprefix' => [
                    [__DIR__.'/_data/parser/trait0.php', __DIR__.'/_data/parser/trait1.php'], __DIR__.'/_data/parser'
                ],
                'dirwithoutprefix' => [
                    [__DIR__, '/usr'], '/'
                ]
            ];
        }
    }

}
