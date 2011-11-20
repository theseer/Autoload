<?php
/**
 * Copyright (c) 2009-2010 Arne Blankerts <arne@blankerts.de>
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
    use TheSeer\Autoload\AutoloadBuilder;

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
            $this->classlist['demo1'] = realpath(__DIR__ . '/_data/classfinder/class.php');
            $this->classlist['demo2'] = realpath(__DIR__ . '/_data/classfinder/class.php');
        }

        /**
         *
         * @covers \TheSeer\Autoload\AutoloadBuilder::__construct
         * @covers \TheSeer\Autoload\AutoloadBuilder::render
         */
        public function testDefaultRendering() {
            $ab = new \TheSeer\Autoload\AutoloadBuilder($this->classlist);
            $expected = "         \$classes = array(\n                'demo1' => '".__DIR__."/_data/classfinder/class.php',\n";
            $this->assertContains($expected, $ab->render());
            $expected = "require \$classes[\$cn]";
            $this->assertContains($expected, $ab->render());
        }

        /**
         *
         * @covers \TheSeer\Autoload\AutoloadBuilder::save
         */
        public function testSaveFile() {
            $tempFileName = sprintf('%s/%s.php', sys_get_temp_dir(), uniqid());
            $ab = new \TheSeer\Autoload\AutoloadBuilder($this->classlist);
            $ab->save($tempFileName);
            $this->assertFileExists($tempFileName);
            unlink($tempFileName);
        }

        /**
         *
         * @covers \TheSeer\Autoload\AutoloadBuilder::setTemplateFile
         * @expectedException \TheSeer\Autoload\AutoloadBuilderException
         */
        public function testExceptionForSettingTemplateFileToNonExisting() {
            $ab = new \TheSeer\Autoload\AutoloadBuilder($this->classlist);
            $ab->setTemplateFile('NonExistend.file');
        }

        /**
         *
         * @covers \TheSeer\Autoload\AutoloadBuilder::setTemplateFile
         */
        public function testSettingNonDefaultTemplate() {
            $ab = new \TheSeer\Autoload\AutoloadBuilder($this->classlist);
            $ab->setTemplateFile(__DIR__ . '/_data/templates/simple.php');
            $expected = "'demo1' => '".__DIR__."/_data/classfinder/class.php',\n";
            $this->assertContains($expected, $ab->render());
        }

        /**
         *
         * @covers \TheSeer\Autoload\AutoloadBuilder::setTemplateCode
         */
        public function testSettingTemplateCode() {
            $ab = new \TheSeer\Autoload\AutoloadBuilder($this->classlist);
            $ab->setTemplateCode('___CLASSLIST___');
            $expected = "'demo1' => '".__DIR__."/_data/classfinder/class.php',\n";
            $this->assertContains($expected, $ab->render());
        }

        /**
         *
         * @covers \TheSeer\Autoload\AutoloadBuilder::setLinebreak
         * @covers \TheSeer\Autoload\AutoloadBuilder::render
         */
        public function testWindowsLFRendering() {
            $ab = new \TheSeer\Autoload\AutoloadBuilder($this->classlist);
            $ab->setLineBreak("\r\n");
            $expected = "_data/classfinder/class.php',\r\n";
            $this->assertContains($expected, $ab->render());
        }

        /**
         *
         * @covers \TheSeer\Autoload\AutoloadBuilder::setLinebreak
         * @covers \TheSeer\Autoload\AutoloadBuilder::getLinebreak
         */
        public function testSettingAndGettingLinebreakWorks() {
            $ab = new \TheSeer\Autoload\AutoloadBuilder($this->classlist);
            $ab->setLineBreak('foo');
            $this->assertEquals('foo', $ab->getLineBreak());
        }

        /**
         *
         * @covers \TheSeer\Autoload\AutoloadBuilder::setIndent
         * @covers \TheSeer\Autoload\AutoloadBuilder::render
         */
        public function testIndentWithTabsRendering() {
            $ab = new \TheSeer\Autoload\AutoloadBuilder($this->classlist);
            $ab->setIndent("\t");
            $expected = "\t'demo2'";
            $this->assertContains($expected, $ab->render());
        }


        /**
         *
         * @covers \TheSeer\Autoload\AutoloadBuilder::setBaseDir
         * @covers \TheSeer\Autoload\AutoloadBuilder::render
         */
        public function testSetBaseDirRendering() {
            $ab = new \TheSeer\Autoload\AutoloadBuilder($this->classlist);
            $ab->setBaseDir(realpath(__DIR__ . '/..'));
            $result = $ab->render();

            $expected = "require __DIR__ . \$classes[\$cn];";
            $this->assertContains($expected, $result);

            $expected = "         \$classes = array(\n                'demo1' => '/tests/_data/classfinder/class.php',\n";
            $this->assertContains($expected, $result);
        }

        /**
         *
         * @covers \TheSeer\Autoload\AutoloadBuilder::render
         */
        public function testRenderingInCompatMode() {
            $ab = new \TheSeer\Autoload\AutoloadBuilder($this->classlist);
            $ab->setCompat(true);
            $ab->setBaseDir(realpath(__DIR__));
            $expected = "require dirname(__FILE__) . \$classes[\$cn];";
            $this->assertContains($expected, $ab->render());

        }

        /**
         * @covers \TheSeer\Autoload\AutoloadBuilder::resolvePath
         */
        public function testRelativeSubBaseDirRendering() {
            $ab = new \TheSeer\Autoload\AutoloadBuilder($this->classlist);
            $ab->setBaseDir(realpath(__DIR__.'/_data/dependency'));
            $expected = "'demo1' => '/../classfinder/class.php'";
            $this->assertContains($expected, $ab->render());
        }

        /**
         *
         * @depends testSettingTemplateCode
         * @expectedException \TheSeer\Autoload\AutoloadBuilderException
         */
        public function testSettingInvalidTimestamp() {
            $ab = new \TheSeer\Autoload\AutoloadBuilder($this->classlist);
            $ab->setTimestamp('Bad');
        }

        /**
         *
         * @depends testSettingTemplateCode
         */
        public function testSettingTimestamp() {
            $ab = new \TheSeer\Autoload\AutoloadBuilder($this->classlist);
            $ab->setTemplateCode('___CREATED___');
            $now = time();
            $ab->setTimestamp($now);
            $this->assertEquals(date('r',$now), $ab->render());
        }

        /**
         *
         * @depends testSettingTemplateCode
         * @depends testSettingTimestamp
         */
        public function testSettingDateTimeFormat() {
            $ab = new \TheSeer\Autoload\AutoloadBuilder($this->classlist);
            $ab->setTemplateCode('___CREATED___');
            $now = time();
            $ab->setTimestamp($now);
            $ab->setDateTimeFormat('dmYHis');
            $this->assertEquals(date('dmYHis',$now), $ab->render());
        }

        /**
         *
         * @depends testSettingTemplateCode
         * @covers \TheSeer\Autoload\AutoloadBuilder::setVariable
         */
        public function testSetVariable() {
            $ab = new \TheSeer\Autoload\AutoloadBuilder($this->classlist);
            $ab->setTemplateCode('___TEST___');
            $ab->setVariable('TEST','variableValue');
            $this->assertEquals('variableValue', $ab->render());
        }

        /**
         *
         * @depends testSettingTemplateCode
         * @covers \TheSeer\Autoload\AutoloadBuilder::render
         */
        public function testGetUniqueValueForAutoloadName() {
            $ab = new \TheSeer\Autoload\AutoloadBuilder($this->classlist);
            $ab->setTemplateCode('___AUTOLOAD___');
            $first = $ab->render();
            $this->assertNotEquals($first, $ab->render());
        }

        /**
         * @depends testSettingTemplateCode
         * @covers \TheSeer\Autoload\AutoloadBuilder::setCompat
         */
        public function testSetCompatMode() {
            $ab = new \TheSeer\Autoload\AutoloadBuilder($this->classlist);
            $ab->setCompat(true);
            $ab->setBaseDir('.');
            $ab->setTemplateCode('___BASEDIR___');
            $this->assertEquals('dirname(__FILE__) . ', $ab->render());
        }

    }

}