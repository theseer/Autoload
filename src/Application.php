<?php
/**
 * Copyright (c) 2009-2012 Arne Blankerts <arne@blankerts.de>
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
 *
 */
namespace TheSeer\Autoload {

    class Application {

        private $logger;
        private $factory;

        public function __construct(Logger $logger, Factory $factory) {
            $this->logger = $logger;
            $this->factory = $factory;
        }

        public function run(\ezcConsoleInput $input) {
            $outputFile = $input->getOption('output')->value;
            $pharMode   = $input->getOption('phar')->value;

            if ($input->getOption('phar')->value !== FALSE) {
                $keyfile = $input->getOption('key')->value;
                if ($keyfile != '') {
                    if (!extension_loaded('openssl')) {
                        $this->logger->log("Extension for OpenSSL not loaded - cannot sign phar archive - process aborted.\n\n", STDERR);
                        exit(1);
                    }
                    $keydata = file_get_contents($keyfile);
                    if (strpos($keydata, 'ENCRYPTED') !== FALSE) {
                        $this->beQuiet = FALSE;
                        $this->logger->log("Passphrase for key '$keyfile': ");
                        $g = shell_exec('stty -g');
                        shell_exec('stty -echo');
                        $passphrase = trim(fgets(STDIN));
                        $this->logger->log("\n");
                        shell_exec('stty ' . $g);
                        $private = openssl_pkey_get_private($keydata, $passphrase);
                    } else {
                        $private = openssl_pkey_get_private($keydata);
                    }
                    if (!$private) {
                        $this->logger->log("Opening private key '$keyfile' failed - process aborted.\n\n", STDERR);
                        exit(1);
                    }
                    $keyDetails = openssl_pkey_get_details($private);
                    $privateKey = '';
                    openssl_pkey_export($private, $privateKey);
                    file_put_contents($outputFile . '.pubkey', $keyDetails['key']);
                }
                if (file_exists($outputFile)) {
                    unlink($outputFile);
                }
                $phar = new \Phar($input->getOption('output')->value, 0, basename($input->getOption('output')->value));
                $phar->startBuffering();
                if ($keyfile != '') {
                    $phar->setSignatureAlgorithm(\Phar::OPENSSL, $privateKey);
                }

            }

            $found = 0;
            $withMimeCheck = $input->getOption('paranoid')->value || !$input->getOption('trusting')->value;
            $basedir = $input->getOption('basedir')->value;

            $finder = new ClassFinder(
                $input->getOption('static')->value,
                $input->getOption('tolerant')->value,
                $input->getOption('nolower')->value
            );

            foreach ($input->getArguments() as $directory) {
                $this->logger->log('Scanning directory ' . $directory . "\n");
                if ($basedir == NULL) {
                    $basedir = $directory;
                }
                $scanner = $this->factory->getScanner($directory, $input);
                if ($pharMode !== FALSE) {
                    $pharScanner = $input->getOption('all')->value ? $this->factory->getScanner($directory, $input, FALSE) : $scanner;
                    $phar->buildFromIterator($pharScanner, $basedir);
                    $scanner->rewind();
                }

                $found += $finder->parseMulti($scanner, $withMimeCheck);
                // this unset is needed to "fix" a segfault on shutdown in some PHP Versions
                unset($scanner);
            }

            if ($found == 0) {
                $this->logger->log("No classes were found - process aborted.\n\n", STDERR);
                exit(1);
            }

            $builder = $this->factory->getBuilder($finder, $input);

            if ($input->getOption('lint')->value === TRUE) {
                exit($this->lintCode($builder->render(), $input) ? 0 : 4);
            }

            if ($outputFile == 'STDOUT') {
                echo "\n" . $builder->render() . "\n\n";
            } else {
                if ($pharMode !== FALSE) {
                    $builder->setVariable('PHAR', basename($outputFile));
                    $stub = $builder->render();
                    if (strpos($stub, '__HALT_COMPILER();') === FALSE) {
                        $this->logger->log(
                            "Warning: Template used in phar mode did not contain required __HALT_COMPILER() call\n" .
                                "which has been added automatically. The used stub code may not work as intended.\n\n", STDERR);
                        $stub .= $builder->getLineBreak() . '__HALT_COMPILER();';
                    }
                    $phar->setStub($stub);
                    if ($input->getOption('gzip')->value) {
                        $phar->compressFiles(\Phar::GZ);
                    } elseif ($input->getOption('bzip2')->value) {
                        $phar->compressFiles(\Phar::BZ2);
                    }
                    $phar->stopBuffering();
                    $this->logger->log("\nphar archive '{$outputFile}' generated.\n\n");
                } else {
                    $builder->save($outputFile);
                    $this->logger->log("\nAutoload file '{$outputFile}' generated.\n\n");
                }
            }
        }

        /**
         * Execute a lint check on generated code
         *
         * @param string           $code  Generated code to lint
         * @param \ezcConsoleInput $input CLI Options pased to app
         *
         * @return boolean
         */
        protected function lintCode($code, $input) {
            $dsp = array(
                0 => array("pipe", "r"),
                1 => array("pipe", "w"),
                2 => array("pipe", "w")
            );

            $php = $input->getOption('lint-php');
            if ($php->value === FALSE) {
                $binary = PHP_OS === 'WIN' ? 'C:\php\php.exe' : '/usr/bin/php';
            } else {
                $binary = $php->value;
            }

            $process = proc_open($binary . ' -l', $dsp, $pipes);

            if (!is_resource($process)) {
                $this->logger->log("Opening php binary for linting failed.\n", STDERR);
                exit(1);
            }

            fwrite($pipes[0], $code);
            fclose($pipes[0]);
            fclose($pipes[1]);

            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $rc = proc_close($process);

            if ($rc == 255) {
                $this->logger->log("Syntax errors during lint:\n" .
                    str_replace('in - on line', 'in generated code on line', $stderr) .
                    "\n", STDERR);
                return FALSE;
            }

            $this->logger->log("Lint check of geneated code okay\n\n");
            return TRUE;
        }

    }

}