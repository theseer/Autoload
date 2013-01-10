<?php
/**
 * Copyright (c) 2009-2013 Arne Blankerts <arne@blankerts.de>
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
         * @var Config
         */
        private $config;

        /**
         * @param \TheSeer\Autoload\Config $config
         */
        public function setConfig(Config $config) {
            $this->config = $config;
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
            return new Application($this->getLogger(), $this->config, $this);
        }

        public function getLogger() {
            return new Logger($this->config->isQuietMode());
        }

        public function getFinder() {
            return new ClassFinder(
                $this->config->isStaticMode(),
                $this->config->isTolerantMode(),
                !$this->config->isLowercaseMode()
            );
        }


        /**
         * Get instance of DirectoryScanner with filter options applied
         *
         * @param bool                                               $filter
         * @return \TheSeer\DirectoryScanner\IncludeExcludeFilterIterator
         */
        public function getScanner($filter = TRUE) {
            $scanner = new \TheSeer\DirectoryScanner\DirectoryScanner;
            if ($filter) {
                $scanner->setIncludes($this->config->getInclude());
                $scanner->setExcludes($this->config->getExclude());
            }
            return $scanner;
        }


        public function getPharBuilder() {
            $builder = new PharBuilder(
                $this->getScanner(!$this->config->isPharAllMode()),
                $this->config->getBaseDirectory()
            );
            $builder->setCompressionMode($this->config->getPharCompression());
            foreach($this->config->getDirectories() as $directory) {
                $builder->addDirectory($directory);
            }

            return $builder;
        }

        /**
         * Helper to get instance of AutoloadBuilder with cli options applied
         *
         * @throws \RuntimeException
         * @return \TheSeer\Autoload\AutoloadBuilder|\TheSeer\Autoload\StaticBuilder
         */
        public function getBuilder(ClassFinder $finder) {
            $isStatic = $this->config->isStaticMode();
            $isPhar   = $this->config->isPharMode();
            $isCompat = $this->config->isCompatMode();
            $noLower  = !$this->config->isLowercaseMode();
            $isOnce   = $this->config->isOnceMode();
            $tplType  = $noLower ? 'cs' : 'ci';

            if ($isStatic === TRUE) {
                $builder = new StaticBuilder($finder->getMerged());
                $builder->setDependencies($finder->getDependencies());
                $builder->setPharMode($isPhar);
                $builder->setRequireOnce($isOnce);
            } else {
                $builder = new AutoloadBuilder($finder->getMerged());
            }

            $builder->setCompat($isCompat);

            $basedir = $this->config->getBaseDirectory();
            if (!$basedir || !is_dir($basedir)) {
                throw new \RuntimeException("Given basedir '{$basedir}' does not exist or is not a directory");
            }
            $builder->setBaseDir($basedir);

            $template = $this->config->getTemplate();
            if ($template !== NULL) {
                if (!file_exists($template)) {
                    $alternative = __DIR__.'/templates/'.$tplType.'/'.$template;
                    if (file_exists($alternative)) {
                        $template = $alternative;
                    }
                }
                $builder->setTemplateFile($template);
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

                $builder->setTemplateFile(__DIR__.'/templates/'.$tplType.'/'.$tplFile);
            }

            $format = $this->config->getDateFormat();
            if ($format) {
                $builder->setDateTimeFormat($format);
            }

            $builder->setIndent($this->config->getIndent());
            $builder->setLineBreak($this->config->getLinebreak());

            foreach($this->config->getVariables() as $name => $value) {
                $builder->setVariable($name, $value);
            }

            return $builder;
        }

    }

}