<?php
/**
 * Copyright (c) 2009 Arne Blankerts <arne@blankerts.de>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *   * Redistributions of source code must retain the above copyright notice,
 *     this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright notice,
 *     this list of conditions and the following disclaimer in the documentation
 *     and/or other materials provided with the distribution.
 *
 *   * Neither the name of Arne Blankerts nor the names of contributors
 *     may be used to endorse or promote products derived from this software
 *     without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT  * NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER ORCONTRIBUTORS
 * BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Autoload
 * @author     Arne Blankerts <arne@blankerts.de>
 * @copyright  Arne Blankerts <arne@blankerts.de>, All rights reserved.
 * @license    BSD License
 */

namespace TheSeer\Autoload\Tests {

    use TheSeer\Autoload\ClassFinder;

    /**
     * Unit tests for ClassFinder class
     *
     * @author     Arne Blankerts <arne@blankerts.de>
     * @copyright  Arne Blankerts <arne@blankerts.de>, All rights reserved.
     */
    class ClassFinderTest extends \PHPUnit_Framework_TestCase {

        public function testNoClassDefined() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/noclass.php');
            $this->assertEquals(0,$rc);
        }

        public function testOneClass() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/class.php');
            $this->assertEquals(1,$rc);
            $this->assertArrayHasKey('demo', $finder->getClasses());
            $this->assertArrayHasKey('demo', $finder->getMerged());
        }

        public function testOneClassCaseSensitive() {
            $finder = new \TheSeer\Autoload\ClassFinder(false,false,true);
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/class.php');
            $this->assertEquals(1,$rc);
            $this->assertArrayHasKey('Demo', $finder->getMerged());
        }

        /**
         *
         * @expectedException  \TheSeer\Autoload\ClassFinderException
         * @expectedExceptionCode  \TheSeer\Autoload\ClassFinderException::ClassRedeclaration
         */
        public function testRedeclaringThrowsException() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $finder->parseFile(__DIR__.'/_data/classfinder/class.php');
            $finder->parseFile(__DIR__.'/_data/classfinder/redeclaration.php');
        }

        /**
         * @expectedException  \TheSeer\Autoload\ClassFinderException
         * @expectedExceptionCode  \TheSeer\Autoload\ClassFinderException::ParseError
         */
        public function testInvalidClassnameThrowsException() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $finder->parseFile(__DIR__.'/_data/classfinder/parseerror1.php');
        }

        /**
         * @expectedException  \TheSeer\Autoload\ClassFinderException
         * @expectedExceptionCode  \TheSeer\Autoload\ClassFinderException::ParseError
         */
        public function testInvalidClassnameWithExtendsThrowsException() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $finder->parseFile(__DIR__.'/_data/classfinder/parseerror2.php');
        }

        /**
         * @expectedException  \TheSeer\Autoload\ClassFinderException
         * @expectedExceptionCode  \TheSeer\Autoload\ClassFinderException::ParseError
         */
        public function testInvalidClassnameForExtendsThrowsException() {
            $finder = new \TheSeer\Autoload\ClassFinder(true);
            $finder->parseFile(__DIR__.'/_data/classfinder/parseerror3.php');
        }

        /**
         * @expectedException  \TheSeer\Autoload\ClassFinderException
         * @expectedExceptionCode  \TheSeer\Autoload\ClassFinderException::ParseError
         */
        public function testInvalidClassnameForImplementsThrowsException() {
            $finder = new \TheSeer\Autoload\ClassFinder(true);
            $finder->parseFile(__DIR__.'/_data/classfinder/parseerror4.php');
        }

        /**
         * @expectedException  \TheSeer\Autoload\ClassFinderException
         * @expectedExceptionCode  \TheSeer\Autoload\ClassFinderException::ParseError
         */
        public function testSyntacticallyInvalidClassnameThrowsException() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $finder->parseFile(__DIR__.'/_data/classfinder/invalid1.php');
        }

        /**
         * @expectedException  \TheSeer\Autoload\ClassFinderException
         * @expectedExceptionCode  \TheSeer\Autoload\ClassFinderException::ParseError
         */
        public function testInvalidTokenInClassnameThrowsException() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $finder->parseFile(__DIR__.'/_data/classfinder/invalid2.php');
        }

        /**
         * @expectedException  \TheSeer\Autoload\ClassFinderException
         * @expectedExceptionCode  \TheSeer\Autoload\ClassFinderException::ParseError
         */
        public function testInvalidTokenInClassnameWithinNamespaceThrowsException() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $finder->parseFile(__DIR__.'/_data/classfinder/invalid3.php');
        }

        public function testRedeclaringClassInSameFileDoesNotThrowExceptionInTolerantMode()
        {
            $finder = new \TheSeer\Autoload\ClassFinder(false, true);
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/redeclaration.php');
            $this->assertEquals(1, $rc);
            $classes = $finder->getMerged();
            $this->assertArrayHasKey('demo', $classes);
        }

        public function testFullPathToClass() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/class.php');
            $demo = $finder->getMerged();
            $this->assertEquals(__DIR__.'/_data/classfinder/class.php', $demo['demo']);
        }

        public function testMultipleClasses() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/multiclass.php');
            $this->assertEquals(3,$rc);
            $classes = $finder->getMerged();
            $this->assertArrayHasKey('demo1', $classes);
            $this->assertArrayHasKey('demo2', $classes);
            $this->assertArrayHasKey('demo3', $classes);
        }

        public function testExtends() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/extends.php');
            $this->assertEquals(2,$rc);
            $classes = $finder->getMerged();
            $this->assertArrayHasKey('demo1', $classes);
            $this->assertArrayHasKey('demo2', $classes);
        }

        public function testExtendsWithDependency() {
            $finder = new \TheSeer\Autoload\ClassFinder(true);
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/extends.php');
            $dep = $finder->getDependencies();
            $this->assertArrayHasKey('demo2', $dep);
            $this->assertEquals(array('demo1'), $dep['demo2']);
        }

        public function testInterface() {
            $finder = new \TheSeer\Autoload\ClassFinder();
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/interface.php');
            $this->assertEquals(1,$rc);
            $this->assertArrayHasKey('demo', $finder->getInterfaces());
            $this->assertArrayHasKey('demo', $finder->getMerged());
        }

        public function testInterfaceExtendsWithDependency() {
            $finder = new \TheSeer\Autoload\ClassFinder(true);
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/interfaceextends1.php');
            $dep = $finder->getDependencies();
            $this->assertArrayHasKey('demo2', $dep);
            $this->assertEquals(array('demo1'), $dep['demo2']);
        }

        public function testInterfaceExtendsWithDependencyAndNamespaceChange() {
            $finder = new \TheSeer\Autoload\ClassFinder(true);
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/interfaceextends2.php');
            $dep = $finder->getDependencies();
            $this->assertArrayHasKey('a\\\\demo2', $dep);
            $this->assertEquals(array('a\\\\demo1','iterator'), $dep['a\\\\demo2']);
        }

        public function testSingleImplements() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/implements1.php');
            $this->assertEquals(2,$rc);
            $classes = $finder->getMerged();
            $this->assertArrayHasKey('demo1', $classes);
            $this->assertArrayHasKey('demo2', $classes);
        }

        public function testMultiImplements() {
            $finder = new \TheSeer\Autoload\ClassFinder(true);
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/implements2.php');
            $this->assertEquals(3,$rc);
            $classes = $finder->getMerged();
            $this->assertArrayHasKey('demo1', $classes);
            $this->assertArrayHasKey('demo2', $classes);
            $this->assertArrayHasKey('demo3', $classes);
        }

        public function testMultiImplementsDepdencies() {
            $finder = new \TheSeer\Autoload\ClassFinder(true);
            $finder->parseFile(__DIR__.'/_data/classfinder/implements2.php');
            $dep = $finder->getDependencies();
            $expect = array('demo1','demo2');
            $this->assertArrayHasKey('demo3', $dep);
            $this->assertEquals($expect, $dep['demo3']);
        }

        public function testMultiImplementsDepdenciesWithNamespace() {
            $finder = new \TheSeer\Autoload\ClassFinder(true);
            $finder->parseFile(__DIR__.'/_data/classfinder/implements3.php');
            $dep = $finder->getDependencies();
            $expect = array('a\\\\demo1','b\\\\demo2');
            $this->assertArrayHasKey('b\\\\demo3', $dep);
            $this->assertEquals($expect, $dep['b\\\\demo3']);
        }

        public function testImplementsExtends() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/implementsextends.php');
            $this->assertEquals(3,$rc);
            $classes = $finder->getMerged();
            $this->assertArrayHasKey('test', $classes);
            $this->assertArrayHasKey('demo1', $classes);
            $this->assertArrayHasKey('demo2', $classes);
        }

        public function testNamespaceBracketSyntax() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/namespace1.php');
            $this->assertEquals(1,$rc);
            $this->assertArrayHasKey('demo\\\\demo1', $finder->getMerged());
        }

        public function testNamespaceBracketSyntaxMultiLevel() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/namespace2.php');
            $this->assertEquals(1,$rc);
            $this->assertArrayHasKey('demo\\\\level2\\\\demo1', $finder->getMerged());
        }

        public function testNamespaceSemicolonSyntax() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/namespace3.php');
            $this->assertEquals(1,$rc);
            $this->assertArrayHasKey('demo\\\\demo1', $finder->getMerged());
        }

        public function testNamespaceSemicolonSyntaxMultiLevel() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/namespace4.php');
            $this->assertEquals(1,$rc);
            $this->assertArrayHasKey('demo\\\\level2\\\\demo1', $finder->getMerged());
        }

        public function testNamespaceBracketCounting() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/namespace5.php');
            $this->assertEquals(1,$rc);
            $this->assertArrayHasKey('demo\\\\level2\\\\demo1', $finder->getMerged());
        }

        public function testNamespaceSemicolonSyntaxMultiNS() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/namespace6.php');
            $this->assertEquals(2,$rc);
            $classes = $finder->getMerged();
            $this->assertArrayHasKey('demo\\\\level2\\\\demo1', $classes);
            $this->assertArrayHasKey('demo\\\\level2\\\\level3\\\\demo2', $classes);
        }

        public function testNamespaceBracketSyntaxMultiNS() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/namespace7.php');
            $this->assertEquals(2,$rc);
            $classes = $finder->getMerged();
            $this->assertArrayHasKey('demo\\\\level2\\\\demo1', $classes);
            $this->assertArrayHasKey('demo\\\\level2\\\\level3\\\\demo2', $classes);
        }

        public function testNamespaceParsingIgnoresConstantAccessUseOfNamespaceKeyword() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/namespaceconstant.php');
            $this->assertEquals(1,$rc);
            $classes = $finder->getMerged();
            $this->assertArrayHasKey('demo\\\\level2\\\\demo1', $classes);
        }

        public function testEmptyNamespaceNameParsingWorks() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/namespace8.php');
            $this->assertEquals(1,$rc);
            $classes = $finder->getMerged();
            $this->assertArrayHasKey('demo', $classes);
        }

        public function testBracketParsingBugTest1() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/brackettest1.php');
            $this->assertEquals(2,$rc);
            $classes = $finder->getMerged();
            $this->assertArrayHasKey('x\\\\foo', $classes);
            $this->assertArrayHasKey('x\\\\baz', $classes);
        }

        public function testBracketParsingBugTest2() {
            $finder = new \TheSeer\Autoload\ClassFinder;
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/brackettest2.php');
            $this->assertEquals(2,$rc);
            $classes = $finder->getMerged();
            $this->assertArrayHasKey('x\\\\foo', $classes);
            $this->assertArrayHasKey('x\\\\baz', $classes);
        }

        /**
         *
         * @covers \TheSeer\Autoload\ClassFinder::parseMulti
         */
        public function testParseMultipleFiles() {
            $list = array(
            new \SplFileObject(__DIR__.'/_data/classfinder/noclass.php'),
            new \SplFileObject(__DIR__.'/_data/classfinder/extends.php'),
            new \SplFileObject(__DIR__.'/_data/classfinder/namespace1.php')
            );
            $finder = new \TheSeer\Autoload\ClassFinder;
            $rc = $finder->parseMulti(new \ArrayIterator($list), false);
            $this->assertEquals(3,$rc);
            $classes = $finder->getMerged();
            $this->assertArrayHasKey('demo1', $classes);
            $this->assertArrayHasKey('demo2', $classes);
            $this->assertArrayHasKey('demo\\\\demo1', $classes);
        }

        /**
         *
         * @expectedException \TheSeer\Autoload\ClassFinderException
         */
        public function testDependenciesDisabledThrowsException() {
            $finder = new \TheSeer\Autoload\ClassFinder(false);
            $dep = $finder->getDependencies();
        }

        /**
         * @covers \TheSeer\Autoload\ClassFInder::parseFile
         * @covers \TheSeer\Autoload\ClassFinder::getDependencies
         */
        public function testDependenciesFound() {
            $finder = new \TheSeer\Autoload\ClassFinder(true);
            $rc = $finder->parseFile(__DIR__.'/_data/dependency/file1.php');

            $dep = $finder->getDependencies();
            $expect = array('test\\\\demo1','test\\\\demo2');
            $this->assertArrayHasKey('foo\\\\demo3', $dep);
            $this->assertEquals($expect, $dep['foo\\\\demo3']);
        }

        public function testParseTraitWorks() {
            $finder = new \TheSeer\Autoload\ClassFinder(true);
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/trait0.php');

            $this->assertArrayHasKey('test', $finder->getTraits());
            $this->assertArrayHasKey('test', $finder->getMerged());
        }

        public function testParseUseTraitWorks() {
            $finder = new \TheSeer\Autoload\ClassFinder(true);
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/trait1.php');

            $classes = $finder->getMerged();
            $dep = $finder->getDependencies();

            $this->assertArrayHasKey('test', $classes);
            $this->assertArrayHasKey('bar', $classes);

            $expect = array('test');

            $this->assertArrayHasKey('bar', $dep);
            $this->assertEquals($expect, $dep['bar']);
        }

        public function testParseUseTraitWorksWhenDependencyIsDisabled() {
            $finder = new \TheSeer\Autoload\ClassFinder();
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/trait1.php');

            $classes = $finder->getMerged();
            $this->assertArrayHasKey('test', $classes);
            $this->assertArrayHasKey('bar', $classes);

        }

        public function testParseUseMultipleTraitWorks() {
            $finder = new \TheSeer\Autoload\ClassFinder(true);
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/trait2.php');

            $classes = $finder->getMerged();
            $dep = $finder->getDependencies();

            $this->assertArrayHasKey('test', $classes);
            $this->assertArrayHasKey('trait1', $classes);
            $this->assertArrayHasKey('trait2', $classes);

            $expect = array('trait1', 'trait2');

            $this->assertArrayHasKey('test', $dep);
            $this->assertEquals($expect, $dep['test']);
        }

        public function testParseUseTraitWorksEvenWithUseStatementInMethodForClosure() {
            $finder = new \TheSeer\Autoload\ClassFinder(true);
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/trait3.php');

            $classes = $finder->getMerged();
            $dep = $finder->getDependencies();

            $this->assertArrayHasKey('test', $classes);
            $this->assertArrayHasKey('trait1', $classes);

            $expect = array('trait1');

            $this->assertArrayHasKey('test', $dep);
            $this->assertEquals($expect, $dep['test']);
        }

        public function testParseUseTraitsWithOverwriteSkipsBracketContent() {
            $finder = new \TheSeer\Autoload\ClassFinder(true);
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/trait4.php');

            $classes = $finder->getMerged();
            $dep = $finder->getDependencies();

            $this->assertArrayHasKey('test', $classes);
            $this->assertArrayHasKey('trait1', $classes);

            $expect = array('trait1', 'trait2');

            $this->assertArrayHasKey('test', $dep);
            $this->assertEquals($expect, $dep['test']);
        }

        public function testNamespaceImportViaUse() {
            $finder = new \TheSeer\Autoload\ClassFinder(true);
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/use1.php');

            $classes = $finder->getMerged();
            $dep = $finder->getDependencies();

            $this->assertArrayHasKey('demo\\\\a\\\\demo1', $classes);
            $this->assertArrayHasKey('demo\\\\b\\\\demo2', $classes);

            $this->assertArrayHasKey('demo\\\\b\\\\demo2', $dep);
            $expect = array('demo\\\\a\\\\demo1');
            $this->assertEquals($expect, $dep['demo\\\\b\\\\demo2']);
        }

        public function testNamespaceMultiImportViaUse() {
            $finder = new \TheSeer\Autoload\ClassFinder(true);
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/use2.php');

            $classes = $finder->getMerged();
            $dep = $finder->getDependencies();

            $this->assertArrayHasKey('demo\\\\a\\\\demo1', $classes);
            $this->assertArrayHasKey('demo\\\\b\\\\demo2', $classes);
            $this->assertArrayHasKey('demo\\\\c\\\\demo3', $classes);

            $this->assertArrayHasKey('demo\\\\c\\\\demo3', $dep);
            $expect = array('demo\\\\a\\\\demo1');
            $this->assertEquals($expect, $dep['demo\\\\c\\\\demo3']);
        }

        public function testNamespaceImportWithAlias() {
            $finder = new \TheSeer\Autoload\ClassFinder(true);
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/use3.php');

            $classes = $finder->getMerged();
            $dep = $finder->getDependencies();

            $this->assertArrayHasKey('demo\\\\a\\\\demo1', $classes);
            $this->assertArrayHasKey('demo\\\\b\\\\demo2', $classes);

            $this->assertArrayHasKey('demo\\\\b\\\\demo2', $dep);
            $expect = array('demo\\\\a\\\\demo1');
            $this->assertEquals($expect, $dep['demo\\\\b\\\\demo2']);
        }

        public function testNamespaceImportWithRelativeAlias() {
            $finder = new \TheSeer\Autoload\ClassFinder(true);
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/use4.php');

            $classes = $finder->getMerged();
            $dep = $finder->getDependencies();

            $this->assertArrayHasKey('demo\\\\a\\\\demo1', $classes);
            $this->assertArrayHasKey('demo\\\\b\\\\demo2', $classes);

            $this->assertArrayHasKey('demo\\\\b\\\\demo2', $dep);
            $expect = array('demo\\\\a\\\\demo1');
            $this->assertEquals($expect, $dep['demo\\\\b\\\\demo2']);
        }

        public function testAliasViaUseGetsIgnoredIfNotNeeded() {
            $finder = new \TheSeer\Autoload\ClassFinder(true);
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/use5.php');

            $classes = $finder->getMerged();
            $dep = $finder->getDependencies();

            $this->assertArrayHasKey('demo', $classes);
            $this->assertArrayHasKey('demo', $dep);
            $this->assertEquals(array(), $dep['demo']);
        }

        public function testUseInClosurewithinAClassGetsIgnored() {
            $finder = new \TheSeer\Autoload\ClassFinder(true);
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/use6.php');

            $classes = $finder->getMerged();
            $dep = $finder->getDependencies();

            $this->assertArrayHasKey('demo\\\\a\\\\demo2', $classes);
            $this->assertArrayHasKey('demo\\\\a\\\\demo2', $dep);
            $this->assertEquals(array(), $dep['demo\\\\a\\\\demo2']);
        }

        public function testGlobalUseInClosureGetsIgnored() {
            $finder = new \TheSeer\Autoload\ClassFinder(true);
            $rc = $finder->parseFile(__DIR__.'/_data/classfinder/use7.php');

            $classes = $finder->getMerged();
            $dep = $finder->getDependencies();

            $this->assertArrayHasKey('demo\\\\a\\\\demo2', $classes);
            $this->assertArrayHasKey('demo\\\\a\\\\demo2', $dep);
            $this->assertEquals(array(), $dep['demo\\\\a\\\\demo2']);
        }

    }

}