<?php declare(strict_types=1);
namespace TheSeer\Autoload;

use PHPUnit\Framework\TestCase;

class ComposerIteratorTest extends TestCase {

    public function testRecursionIsHandledProperly() {
        $iterator = new ComposerIterator(new \SplFileInfo(__DIR__ . '/_data/recursion/composer.json'));
        $expected = [
            __DIR__ . '/_data/recursion/vendor/foo/bar',
            __DIR__ . '/_data/recursion/vendor/bar/foo'
        ];
        foreach($iterator as $pos => $entry) {
            $this->assertEquals($expected[$pos], $entry);
        }
    }

}
