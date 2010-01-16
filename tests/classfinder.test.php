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

namespace TheSeer\Tools\Tests {

   use TheSeer\Tools\ClassFinder;

   /**
    * Unit tests for ClassFinder class
    *
    * @author     Arne Blankerts <arne@blankerts.de>
    * @copyright  Arne Blankerts <arne@blankerts.de>, All rights reserved.
    */
   class ClassFinderTest extends \PHPUnit_Framework_TestCase {

      public function testNoClassDefined() {
        $finder = new \TheSeer\Tools\ClassFinder;
        $rc = $finder->parseFile(__DIR__.'/_data/classfinder/noclass.php');
        $this->assertTrue(empty($rc));
      }

      public function testOneClass() {
        $finder = new \TheSeer\Tools\ClassFinder;
        $rc = $finder->parseFile(__DIR__.'/_data/classfinder/class.php');
        $this->assertEquals(1,count($rc));
        $this->assertArrayHasKey('demo', $rc);
      }

      public function testFullPathToClass() {
        $finder = new \TheSeer\Tools\ClassFinder;
        $rc = $finder->parseFile(__DIR__.'/_data/classfinder/class.php');
        $this->assertEquals(__DIR__.'/_data/classfinder/class.php', $rc['demo']);
      }

      public function testMultipleClasses() {
        $finder = new \TheSeer\Tools\ClassFinder;
        $rc = $finder->parseFile(__DIR__.'/_data/classfinder/multiclass.php');
        $this->assertEquals(3,count($rc));
        $this->assertArrayHasKey('demo1', $rc);
        $this->assertArrayHasKey('demo2', $rc);
        $this->assertArrayHasKey('demo3', $rc);
      }

      public function testExtends() {
        $finder = new \TheSeer\Tools\ClassFinder;
        $rc = $finder->parseFile(__DIR__.'/_data/classfinder/extends.php');
        $this->assertEquals(2,count($rc));
        $this->assertArrayHasKey('demo1', $rc);
        $this->assertArrayHasKey('demo2', $rc);
      }

      public function testInterface() {
        $finder = new \TheSeer\Tools\ClassFinder;
        $rc = $finder->parseFile(__DIR__.'/_data/classfinder/interface.php');
        $this->assertEquals(1,count($rc));
        $this->assertArrayHasKey('demo', $rc);
      }

      public function testImplements() {
        $finder = new \TheSeer\Tools\ClassFinder;
        $rc = $finder->parseFile(__DIR__.'/_data/classfinder/implements.php');
        $this->assertEquals(2,count($rc));
        $this->assertArrayHasKey('demo1', $rc);
        $this->assertArrayHasKey('demo2', $rc);
      }

      public function testImplementsExtends() {
        $finder = new \TheSeer\Tools\ClassFinder;
        $rc = $finder->parseFile(__DIR__.'/_data/classfinder/implementsextends.php');
        $this->assertEquals(3,count($rc));
        $this->assertArrayHasKey('test', $rc);
        $this->assertArrayHasKey('demo1', $rc);
        $this->assertArrayHasKey('demo2', $rc);
      }

      public function testNamespaceBracketSyntax() {
        $finder = new \TheSeer\Tools\ClassFinder;
        $rc = $finder->parseFile(__DIR__.'/_data/classfinder/namespace1.php');
        $this->assertEquals(1,count($rc));
        $this->assertArrayHasKey('demo\\\\demo1', $rc);
      }

      public function testNamespaceBracketSyntaxMultiLevel() {
        $finder = new \TheSeer\Tools\ClassFinder;
        $rc = $finder->parseFile(__DIR__.'/_data/classfinder/namespace2.php');
        $this->assertEquals(1,count($rc));
        $this->assertArrayHasKey('demo\\\\level2\\\\demo1', $rc);
      }

      public function testNamespaceSemicolonSyntax() {
        $finder = new \TheSeer\Tools\ClassFinder;
        $rc = $finder->parseFile(__DIR__.'/_data/classfinder/namespace3.php');
        $this->assertEquals(1,count($rc));
        $this->assertArrayHasKey('demo\\\\demo1', $rc);
      }

      public function testNamespaceSemicolonSyntaxMultiLevel() {
        $finder = new \TheSeer\Tools\ClassFinder;
        $rc = $finder->parseFile(__DIR__.'/_data/classfinder/namespace4.php');
        $this->assertEquals(1,count($rc));
        $this->assertArrayHasKey('demo\\\\level2\\\\demo1', $rc);
      }

      public function testNamespaceBracketCounting() {
        $finder = new \TheSeer\Tools\ClassFinder;
        $rc = $finder->parseFile(__DIR__.'/_data/classfinder/namespace5.php');
        $this->assertEquals(1,count($rc));
        $this->assertArrayHasKey('demo\\\\level2\\\\demo1', $rc);
      }

      public function testNamespaceSemicolonSyntaxMultiNS() {
        $finder = new \TheSeer\Tools\ClassFinder;
        $rc = $finder->parseFile(__DIR__.'/_data/classfinder/namespace6.php');
        $this->assertEquals(2,count($rc));
        $this->assertArrayHasKey('demo\\\\level2\\\\demo1', $rc);
        $this->assertArrayHasKey('demo\\\\level2\\\\level3\\\\demo2', $rc);
      }

      public function testNamespaceBracketSyntaxMultiNS() {
        $finder = new \TheSeer\Tools\ClassFinder;
        $rc = $finder->parseFile(__DIR__.'/_data/classfinder/namespace7.php');
        $this->assertEquals(2,count($rc));
        $this->assertArrayHasKey('demo\\\\level2\\\\demo1', $rc);
        $this->assertArrayHasKey('demo\\\\level2\\\\level3\\\\demo2', $rc);
      }

      public function testBracketParsingBugTest1() {
        $finder = new \TheSeer\Tools\ClassFinder;
        $rc = $finder->parseFile(__DIR__.'/_data/classfinder/brackettest1.php');
        $this->assertEquals(2,count($rc));
        $this->assertArrayHasKey('x\\\\foo', $rc);
        $this->assertArrayHasKey('x\\\\baz', $rc);
      }

      public function testBracketParsingBugTest2() {
        $finder = new \TheSeer\Tools\ClassFinder;
        $rc = $finder->parseFile(__DIR__.'/_data/classfinder/brackettest2.php');
        $this->assertEquals(2,count($rc));
        $this->assertArrayHasKey('x\\\\foo', $rc);
        $this->assertArrayHasKey('x\\\\baz', $rc);
      }

      /**
       *
       * @covers \TheSeer\Tools\ClassFinder::parseMulti
       */
      public function testParseMultipleFiles() {
         $list = array(
            new \SplFileObject(__DIR__.'/_data/classfinder/noclass.php'),
            new \SplFileObject(__DIR__.'/_data/classfinder/extends.php'),
            new \SplFileObject(__DIR__.'/_data/classfinder/namespace1.php')
         );
        $finder = new \TheSeer\Tools\ClassFinder;
        $rc = $finder->parseMulti(new \ArrayIterator($list));
        $this->assertEquals(3,count($rc));
        $this->assertArrayHasKey('demo1', $rc);
        $this->assertArrayHasKey('demo2', $rc);
        $this->assertArrayHasKey('demo\\\\demo1', $rc);
      }

   }

}