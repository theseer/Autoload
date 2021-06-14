<?php
namespace TheSeer\Autoload;

use PHPUnit\Framework\TestCase;

class ComposerIteratorTest extends TestCase {

    public function testRecursionIsHandledProperly() {
        $iterator = new ComposerIterator(new \SplFileInfo(__DIR__ . '/_data/recursion/composer.json'));
        $expected = array(
            __DIR__ . '/_data/recursion/vendor/foo/bar',
            __DIR__ . '/_data/recursion/vendor/bar/foo'
        );
        foreach($iterator as $pos => $entry) {
            $this->assertEquals($expected[$pos], $entry);
        }
    }

    public function testPSR14ArrayIsSupported() {
        $iterator = new ComposerIterator(new \SplFileInfo(__DIR__ . '/_data/composer-array-issue-98/composer.json'));
        $expected = array(
            __DIR__ . '/_data/composer-array-issue-98/../src',
            __DIR__ . '/_data/composer-array-issue-98/modules',
            __DIR__ . '/_data/composer-array-issue-98/src'
        );
        foreach($iterator as $pos => $entry) {
            $this->assertEquals($expected[$pos], $entry);
        }
    }

}
