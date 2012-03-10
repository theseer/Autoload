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
 */

namespace TheSeer\Autoload {

    use \TheSeer\DirectoryScanner\PHPFilterIterator;

    // PHP 5.3 compat
    if (!defined('T_TRAIT')) {
        define('T_TRAIT', 355);
    }

    /**
     * Namespace aware parser to find and extract defined classes within php source files
     *
     * @author     Arne Blankerts <arne@blankerts.de>
     * @copyright  Arne Blankerts <arne@blankerts.de>, All rights reserved.
     */
    class ClassFinder {

        protected $withDeps;
        protected $isInTolerantMode;

        protected $foundClasses = array();
        protected $dependencies = array();

        public function __construct($doDeps = false, $tolerantMode = false, $disableLower = false) {
            $this->withDeps = $doDeps;
            $this->isInTolerantMode = $tolerantMode;
            $this->disableLowercase = $disableLower;
        }

        public function getClasses() {
            return $this->foundClasses;
        }

        public function getDependencies() {
            if (!$this->withDeps) {
                throw new ClassFinderException('Dependency collection disabled', ClassFinderException::NoDependencies);
            }
            return $this->dependencies;
        }

        /**
         * Parse a given file for defintions of classes and interfaces
         *
         * @param string $file Filename of file to process
         *
         * @return integer
         */
        public function parseFile($file) {
            $entries         = 0;
            $classFound      = false;
            $interfaceFound  = false;
            $nsFound         = false;
            $nsProc          = false;
            $inNamespace     = null;
            $inClass         = false;
            $bracketCount    = 0;
            $classBracket    = 0;
            $bracketNS       = false;
            $extendsFound    = false;
            $implementsFound = false;
            $useFound        = false;
            $lastClass       = '';
            $dependsClass    = '';
            $classNameStart  = false;

            $token = token_get_all(file_get_contents($file));
            foreach($token as $pos => $tok) {
                if (!is_array($tok)) {
                    switch ($tok) {
                        case '{': {
                            $bracketCount++;
                            if ($nsProc) {
                                $bracketNS = true;
                            }
                            if ($this->withDeps && ($dependsClass != '')) {
                                if (!isset($this->dependencies[$lastClass])) {
                                    $this->dependencies[$lastClass] = array();
                                }
                                $this->dependencies[$lastClass][] = $dependsClass;
                                $dependsClass = '';
                            }

                            $nsProc = false;
                            $implementsFound = false;
                            $extendsFound    = false;
                            $useFound        = false;
                            break;
                        }
                        case '}': {
                            $bracketCount--;
                            if ($bracketCount==0 && $inNamespace && $bracketNS) {
                                $inNamespace = null;
                            }
                            if ($bracketCount == $classBracket) {
                                $inClass = false;
                            }
                            break;
                        }
                        case ";": {
                            if ($nsProc) {
                                $nsProc    = false;
                                $bracketNS = false;
                            }
                            if ($useFound) {
                                $useFound = false;
                                if (!isset($this->dependencies[$lastClass])) {
                                    $this->dependencies[$lastClass] = array();
                                }
                                $this->dependencies[$lastClass][] = $dependsClass;
                                $dependsClass   = '';
                            }
                            break;
                        }
                        case ',': {
                            if ($this->withDeps && $implementsFound || $useFound) {
                                if (!isset($this->dependencies[$lastClass])) {
                                    $this->dependencies[$lastClass] = array();
                                }
                                $this->dependencies[$lastClass][] = $dependsClass;
                                $dependsClass   = '';
                                $classNameStart = true;
                            }
                            break;
                        }
                    }
                    continue;
                }

                switch ($tok[0]) {
                    case T_CURLY_OPEN:
                    case T_DOLLAR_OPEN_CURLY_BRACES: {
                        $bracketCount++;
                        continue;
                    }
                    case T_IMPLEMENTS: {
                        $implementsFound = true;
                        $classNameStart  = true;
                        continue;
                    }
                    case T_EXTENDS: {
                        $extendsFound    = true;
                        $implementsFound = false;
                        $classNameStart  = true;
                        continue;
                    }
                    case T_TRAIT:
                    case T_CLASS: {
                        $classFound = true;
                        $inClass = true;
                        $classBracket = $bracketCount + 1;
                        continue;
                    }
                    case T_INTERFACE: {
                        $interfaceFound = true;
                        continue;
                    }
                    case T_NAMESPACE: {
                        if ($token[$pos + 1][0] == T_NS_SEPARATOR) {
                            // Ignore inline use of namespace keyword
                            continue;
                        }
                        $nsFound     = true;
                        $nsProc      = true;
                        $inNamespace = null;
                        continue;
                    }
                    case T_NS_SEPARATOR: {
                        if ($nsProc) {
                            $nsFound      = true;
                            $inNamespace .= '\\\\';
                        }
                        if ($extendsFound || $implementsFound) {
                            if (!$classNameStart) $dependsClass .= '\\\\';
                            $classNameStart = false;
                        }
                        continue;
                    }
                    case T_USE: {
                        if ($inClass && ($bracketCount == $classBracket)) {
                            $useFound = true;
                        }
                        continue;
                    }
                    case T_STRING: {
                        if ($nsFound) {
                            $inNamespace .= $this->disableLowercase ? $tok[1] : strtolower($tok[1]);
                            $nsFound = false;
                        } elseif ($classFound || $interfaceFound) {
                            $lastClass = $inNamespace ? $inNamespace .'\\\\' : '';
                            $lastClass .= $this->disableLowercase ? $tok[1] : strtolower($tok[1]);
                            if (isset($this->foundClasses[$lastClass])) {
                                if ($this->canTolerateRedeclaration($lastClass, $file)) {
                                    continue;
                                }
                                throw new ClassFinderException(sprintf(
                                    "Redeclaration of class '%s' detected\n   Original:  %s\n   Secondary: %s\n\n",
                                    stripslashes($lastClass),
                                    $this->foundClasses[$lastClass],
                                    $file
                                    ), ClassFinderException::ClassRedeclaration
                                );
                            }
                            $this->foundClasses[$lastClass] = $file;
                            $entries++;
                            $classFound = false;
                            $interfaceFound = false;
                        } elseif ($extendsFound || $implementsFound || $useFound) {
                            if ($classNameStart && $inNamespace) {
                                $dependsClass   = $inNamespace . '\\\\';
                                $classNameStart = false;
                            }
                            $dependsClass .= $this->disableLowercase ? $tok[1] : strtolower($tok[1]);
                        }
                        continue;
                    }
                }
            }

            return $entries;
        }

        /**
         * @return boolean
         */
        protected function canTolerateRedeclaration($redeclaredClassName, $redeclaredInFilePath)
        {
            return $this->foundClasses[$redeclaredClassName] === $redeclaredInFilePath
            && $this->isInTolerantMode === true;
        }

        /**
         * Process multiple files and parse them for classes and interfaces
         *
         * @param Iterator $sources Iterator based list of files (SplFileObject) to parse
         *
         * @return integer
         */
        public function parseMulti(\Iterator $sources) {
            $count = 0;
            $worker  = new PHPFilterIterator($sources);
            foreach($worker as $file) {
                $count += $this->parseFile($file->getPathname());
            }
            return $count;
        }

    }

    class ClassFinderException extends \Exception {

        const NoDependencies = 1;
        const ClassRedeclaration = 2;

    }
}
