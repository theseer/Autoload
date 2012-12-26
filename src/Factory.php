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

    class Factory {

        /**
         * @var bool
         */
        private $quietMode = FALSE;

        /**
         * @param bool $mode
         */
        public function setQuietMode($mode) {
            $this->quietMode = $mode;
        }

        /**
         * @return CLI
         */
        public function getCLI() {
            return new CLI($this);
        }

        /**
         * @return Application
         */
        public function getApplication() {
            return new Application($this->getLogger(), $this);
        }

        public function getLogger() {
            return new Logger($this->quietMode);
        }

        /**
         * Get instance of DirectoryScanner with cli options applied
         *
         * @param string           $directory
         * @param \ezcConsoleInput $input CLI Options pased to app
         *
         * @param bool                                               $filter
         * @return \TheSeer\DirectoryScanner\IncludeExcludeFilterIterator
         */
        public function getScanner($directory, \ezcConsoleInput $input, $filter = TRUE) {
            $scanner = new \TheSeer\DirectoryScanner\DirectoryScanner;

            if ($filter) {
                $include = $input->getOption('include');
                if (is_array($include->value)) {
                    $scanner->setIncludes($include->value);
                } else {
                    $scanner->addInclude($include->value);
                }

                $exclude = $input->getOption('exclude');
                if ($exclude->value) {
                    if (is_array($exclude->value)) {
                        $scanner->setExcludes($exclude->value);
                    } else {
                        $scanner->addExclude($exclude->value);
                    }
                }
            }
            return $scanner($directory);
        }

        /**
         * Helper to get instance of AutoloadBuilder with cli options applied
         *
         * @param ClassFinder      $finder Instance of ClassFinder to get classes from
         * @param \ezcConsoleInput $input  CLI Options pased to app
         *
         * @throws \RuntimeException
         * @return \TheSeer\Autoload\AutoloadBuilder|\TheSeer\Autoload\StaticBuilder
         */
        public function getBuilder(ClassFinder $finder, \ezcConsoleInput $input) {
            $isStatic = $input->getOption('static')->value;
            $isPhar   = $input->getOption('phar')->value;
            $isCompat = $input->getOption('compat')->value;
            $noLower  = $input->getOption('nolower')->value;
            $isOnce   = $input->getOption('once')->value;
            $tplType  = $noLower ? 'cs' : 'ci';

            if ($isStatic === TRUE) {
                $ab = new StaticBuilder($finder->getMerged());
                $ab->setDependencies($finder->getDependencies());
                $ab->setPharMode($isPhar);
                $ab->setRequireOnce($isOnce);
            } else {
                $ab = new AutoloadBuilder($finder->getMerged());
            }

            $ab->setCompat($isCompat);

            $basedir = $input->getOption('basedir');
            if ($basedir->value) {
                $bdir = realpath($basedir->value);
                if (!$bdir || !is_dir($bdir)) {
                    throw new \RuntimeException("Given basedir '{$basedir->value}' does not exist or is not a directory");
                }
                $ab->setBaseDir($bdir);
            } else {
                $args = $input->getArguments();
                $ab->setBaseDir(realpath($args[0]));
            }

            $template = $input->getOption('template');
            if ($template->value) {
                if (!file_exists($template->value)) {
                    $alternative = __DIR__.'/templates/'.$tplType.'/'.$template->value;
                    if (file_exists($alternative)) {
                        $template->value = $alternative;
                    }
                }
                $ab->setTemplateFile($template->value);
            } else {

                // determine auto template to use
                $tplFile = 'default.php.tpl';
                if ($isCompat) {
                    $tplFile = 'php52.php.tpl';
                }

                if ($isPhar) {
                    if ($isStatic) {
                        $tplFile = 'staticphar.php.tpl';
                    } else {
                        $tplFile = 'phar.php.tpl';
                    }
                } elseif ($isStatic) {
                    $tplFile = 'static.php.tpl';
                    $tplType = '.';
                }

                $ab->setTemplateFile(__DIR__.'/templates/'.$tplType.'/'.$tplFile);
            }

            $format = $input->getOption('format');
            if ($format->value) {
                $ab->setDateTimeFormat($format->value);
            }

            $indent = $input->getOption('indent');
            if ($indent->value) {
                if (is_numeric($indent->value)) {
                    $ab->setIndent(str_repeat(' ', $indent->value));
                } else {
                    $ab->setIndent($indent->value);
                }
            } elseif ($isStatic) {
                $ab->setIndent('');
            } else {
                $ab->setIndent(str_repeat(' ', $isCompat ? 12 : 16));
            }

            $linebreak = $input->getOption('linebreak');
            if ($linebreak->value !== FALSE) {
                $lbr = array('LF' => "\n", 'CR' => "\r", 'CRLF' => "\r\n" );
                if (isset($lbr[$linebreak->value])) {
                    $ab->setLineBreak($lbr[$linebreak->value]);
                } else {
                    $ab->setLineBreak($linebreak->value);
                }
            } else {
                $ab->setLineBreak("\n");
            }

            if ($vars = $input->getOption('var')->value) {
                foreach($vars as $var) {
                    if (strpos($var,'=')===FALSE) {
                        throw new \RuntimeException("Variable defintion '$var' is invalid and cannot be processed.");
                    }
                    list($name, $value) = explode('=',$var,2);
                    $ab->setVariable($name, $value);
                }
            }

            return $ab;
        }

    }

}