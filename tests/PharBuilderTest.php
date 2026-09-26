<?php
/**
 * Copyright (c) 2009-2026 Arne Blankerts <arne@blankerts.de>
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

    use TheSeer\Autoload\PharBuilder;
    use TheSeer\DirectoryScanner\DirectoryScanner;

    class PharBuilderTest extends \PHPUnit\Framework\TestCase {

        private $directory;
        private $pharFiles = array();

        public function setUp(): void {
            if (ini_get('phar.readonly')) {
                $this->markTestSkipped('phar.readonly must be disabled');
            }
            $this->directory = realpath(__DIR__ . '/_data/dependency');
        }

        public function tearDown(): void {
            foreach($this->pharFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }

        public function testPharDoesNotDependOnOrderOfDirectoryEntries() {
            $files = array();
            foreach(glob($this->directory . '/*.php') as $file) {
                $files[] = new \SplFileInfo($file);
            }

            $this->assertSame(
                file_get_contents($this->buildPhar($files)),
                file_get_contents($this->buildPhar(array_reverse($files)))
            );
        }

        private function buildPhar(array $files) {
            $scanner = $this->createMock(DirectoryScanner::class);
            $scanner->method('__invoke')->willReturn(new \ArrayIterator($files));

            $filename = sys_get_temp_dir() . '/' . uniqid('phpab-test-', true) . '.phar';
            $this->pharFiles[] = $filename;

            $builder = new PharBuilder($scanner, $this->directory);
            $builder->setCompressionMode(\Phar::NONE);
            $builder->addDirectory($this->directory);
            $builder->setAliasName('test.phar');
            $builder->build($filename, '<?php __HALT_COMPILER();');

            return $filename;
        }
    }
}
