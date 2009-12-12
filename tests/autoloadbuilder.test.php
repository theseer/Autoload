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
   use TheSeer\Tools\AutoloadBuilder;

   /**
    * Unit tests for PHPFilter iterator class
    *
    * @author     Arne Blankerts <arne@blankerts.de>
    * @copyright  Arne Blankerts <arne@blankerts.de>, All rights reserved.
    */
   class AutoloadBuilderTest extends \PHPUnit_Framework_TestCase {

      protected $classlist;

      public function setUp() {
         $this->classlist = array();
         $this->classlist['demo'] = __DIR__ . '/_data/classfinder/class.php';
      }

      /**
       *
       * @covers \TheSeer\Tools\AutoloadBuilder::__construct
       * @covers \TheSeer\Tools\AutoloadBuilder::render
       */
      public function testDefaultRendering() {
         $ab = new \TheSeer\Tools\AutoloadBuilder($this->classlist);
         $expected = "      static \$classes = array(\n         'demo' => '".__DIR__."/_data/classfinder/class.php'\n      );";
         $this->assertContains($expected, $ab->render());
      }

      /**
       *
       * @covers \TheSeer\Tools\AutoloadBuilder::save
       */
      public function testSaveFile() {
         $ab = new \TheSeer\Tools\AutoloadBuilder($this->classlist);
         $ab->save('/tmp/test.php');
         $this->assertFileExists('/tmp/test.php');
         unlink('/tmp/test.php');
      }

      /**
       *
       * @covers \TheSeer\Tools\AutoloadBuilder::setLinebreak
       * @covers \TheSeer\Tools\AutoloadBuilder::render
       */
      public function testWindowsLFRendering() {
         $ab = new \TheSeer\Tools\AutoloadBuilder($this->classlist);
         $ab->setLineBreak("\r\n");
         $expected = "      static \$classes = array(\r\n         'demo' => '".__DIR__."/_data/classfinder/class.php'\r\n      );";
         $this->assertContains($expected, $ab->render());
      }

      /**
       *
       * @covers \TheSeer\Tools\AutoloadBuilder::setIndent
       * @covers \TheSeer\Tools\AutoloadBuilder::render
       */
      public function testIndentWithTabsRendering() {
         $ab = new \TheSeer\Tools\AutoloadBuilder($this->classlist);
         $ab->setIndent("\t");
         $expected = "\t\tstatic \$classes = array(\n\t\t\t'demo' => '".__DIR__."/_data/classfinder/class.php'\n\t\t);";
         $this->assertContains($expected, $ab->render());
      }

      /**
       *
       * @covers \TheSeer\Tools\AutoloadBuilder::setBaseDir
       * @covers \TheSeer\Tools\AutoloadBuilder::render
       */
      public function testSetBaseDirRendering() {
         $ab = new \TheSeer\Tools\AutoloadBuilder($this->classlist);
         $ab->setBaseDir(__DIR__);
         $expected = "require __DIR__ . \$classes[\$cn];";
         $this->assertContains($expected, $ab->render());

         $expected = "      static \$classes = array(\n         'demo' => '/_data/classfinder/class.php'\n      );";
         $this->assertContains($expected, $ab->render());
      }

      /**
       *
       * @covers \TheSeer\Tools\AutoloadBuilder::omitClosingTag
       * @covers \TheSeer\Tools\AutoloadBuilder::render
       */
      public function testClosingTagOnRendering() {
         $ab = new \TheSeer\Tools\AutoloadBuilder($this->classlist);
         $ab->omitClosingTag(false);
         $this->assertContains('?>', $ab->render());
      }

      /**
       *
       * @covers \TheSeer\Tools\AutoloadBuilder::omitClosingTag
       * @covers \TheSeer\Tools\AutoloadBuilder::render
       */
      public function testClosingTagoffRendering() {
         $ab = new \TheSeer\Tools\AutoloadBuilder($this->classlist);
         $ab->omitClosingTag(true);
         $this->assertNotContains('?>', $ab->render());
      }

   }

}