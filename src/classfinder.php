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
        protected $filename;
        protected $isInTolerantMode = false;
        protected $disableLowercase = false;

        protected $tokenArray = array();

        protected $inNamespace = '';
        protected $inClass = '';

        protected $nsBracket = 0;
        protected $classBracket = 0;

        protected $bracketLevel = 0;
        protected $aliases = array();

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
            $this->filename = $file;
            $map = array(
                T_TRAIT      => 'processClass',
                T_CLASS      => 'processClass',
                T_INTERFACE  => 'processInterface',
                T_NAMESPACE  => 'processNamespace',
                T_USE        => 'processUse',
                '}'          => 'processBracketClose',
                '{'          => 'processBracketOpen',
                T_CURLY_OPEN => 'processBracketOpen',
                T_DOLLAR_OPEN_CURLY_BRACES  => 'processBracketOpen'
            );

            $this->tokenArray = token_get_all(file_get_contents($file));
            $tokenCount = count($this->tokenArray);
            $tokList = array_keys($map);
            for($t=0; $t<$tokenCount; $t++) {
                $current = (array)$this->tokenArray[$t];
                if ($current[0]==T_STRING && $current[1]=='trait') {
                    // PHP < 5.4 compat fix
                    $current[0] = T_TRAIT;
                }
                if (!in_array($current[0], $tokList)) {
                    continue;
                }
                $t = call_user_func(array($this, $map[$current[0]]), $t);
            }
            return count($this->foundClasses);
        }

        protected function processBracketOpen($pos) {
            $this->bracketLevel++;
            return $pos + 1;
        }

        protected function processBracketClose($pos) {
            $this->bracketLevel--;
            if ($this->bracketLevel <= $this->nsBracket) {
                $this->inNamespace = '';
                $this->nsBracket = 0;
                $this->aliases = array();
            }
            if ($this->bracketLevel <= $this->classBracket) {
                $this->classBracket = 0;
                $this->inClass = '';
            }
            return $pos + 1;
        }

        protected function processClass($pos) {
            $list = array('{');
            $stack = $this->getTokensTill($pos, $list);
            $stackSize = count($stack);
            $classname = $this->inNamespace != '' ? $this->inNamespace . '\\' : '';
            $extends = '';
            $extendsFound = false;
            $implementsFound = false;
            $implementsList = array();
            $implements = '';
            $mode = 'classname';
            foreach(array_slice($stack,1,-1) as $tok) {
                switch ($tok[0]) {
                    case T_STRING: {
                        $$mode .= $tok[1];
                        continue;
                    }
                    case T_NS_SEPARATOR: {
                        $$mode .= '\\';
                        continue;
                    }
                    case T_EXTENDS: {
                        $extendsFound = true;
                        $mode = 'extends';
                        continue;
                    }
                    case T_IMPLEMENTS: {
                        $implementsFound = true;
                        $mode = 'implements';
                        continue;
                    }
                    case ',': {
                        if ($mode == 'implements') {
                            $implementsList[] = $this->resolveDepdencyName($implements);
                            $implements = '';
                        }
                        continue;
                    }
                }
            }
            if ($implements != '') {
                $implementsList[] = $this->resolveDepdencyName($implements);
            }
            if ($implementsFound && count($implementsList)==0) {
                throw new ClassFinderException(sprintf(
                        "Parse error in file '%s' while trying to process class definition (extends or implements).\n\n",
                        $this->filename
                ), ClassFinderException::ParseError
                );
            }
            $classname = $this->registerClass($classname);
            if ($this->withDeps) {
                $this->dependencies[$classname] = $implementsList;
                if ($extendsFound) {
                    $this->dependencies[$classname][] = $this->resolveDepdencyName($extends);
                }
            }
            $this->inClass = $classname;
            $this->classBracket = $this->bracketLevel + 1;
            return $pos + $stackSize - 1;
        }

        protected function processInterface($pos) {
            $list = array('{');
            $stack = $this->getTokensTill($pos, $list);
            $stackSize = count($stack);
            $name = $this->inNamespace != '' ? $this->inNamespace . '\\' : '';
            $extends = '';
            $extendsList = array();
            $mode = 'name';
            foreach(array_slice($stack,1,-1) as $tok) {
                switch ($tok[0]) {
                    case T_NS_SEPARATOR:
                    case T_STRING: {
                        $$mode .= $tok[1];
                        continue;
                    }
                    case T_EXTENDS: {
                        $mode = 'extends';
                        continue;
                    }
                    case ',': {
                        if ($mode == 'extends') {
                            $extendsList[] = $this->resolveDepdencyName($extends);
                            $extends = '';
                        }
                    }
                }
            }
            $name = $this->registerClass($name);
            if ($this->withDeps) {
                if ($extends != '') {
                    $extendsList[] = $this->resolveDepdencyName($extends);
                }
                $this->dependencies[$name] = $extendsList;
            }
            return $pos + $stackSize - 1;
        }

        protected function resolveDepdencyName($name) {
            if (!$this->disableLowercase) {
                $name = strtolower($name);
            }
            if ($name == '') {
                throw new ClassFinderException(sprintf(
                        "Parse error in file '%s' while trying to process class definition (extends or implements).\n\n",
                        $this->filename
                    ), ClassFinderException::ParseError
                );
            }
            if ($name[0] == '\\') {
                $name = substr($name, 1);
            } else {
                $parts = explode('\\',$name,2);
                $key = array_search($parts[0], $this->aliases);
                if (!$key) {
                    $name = ($this->inNamespace != '' ? $this->inNamespace . '\\' : ''). $name;
                } else {
                    $name = $key;
                    if (isset($parts[1])) {
                        $name .= '\\' . $parts[1];
                    }
                }
            }
            return addslashes($name);
        }

        protected function registerClass($name) {
            if (!$this->disableLowercase) {
                $name = strtolower($name);
            }
            if ($name == '') {
                throw new ClassFinderException(sprintf(
                        "Parse error in file '%s' while trying to process class definition.\n\n",
                        $this->filename
                    ), ClassFinderException::ParseError
                );
            }
            $name = addslashes($name);
            if (isset($this->foundClasses[$name]) && !$this->canTolerateRedeclaration($name, $this->filename)) {
                throw new ClassFinderException(sprintf(
                        "Redeclaration of class '%s' detected\n Original: %s\n Secondary: %s\n\n",
                        stripslashes($name),
                        $this->foundClasses[$name],
                        $this->filename
                    ), ClassFinderException::ClassRedeclaration
                );
            }
            $this->foundClasses[$name] = $this->filename;
            return $name;
        }

        protected function processNamespace($pos) {
            $list = array(';', '{');
            $stack = $this->getTokensTill($pos, $list);
            $stackSize = count($stack);
            $newpos = $pos + count($stack);
            if ($stackSize < 3) { // empty namespace defintion == root namespace
                $this->inNamespace = '';
                $this->aliases = array();
                return $newpos - 1;
            }
            $next = $stack[1];
            if (is_array($next) && $next[0] == T_NS_SEPARATOR) { // inline use - ignore
                return $newpos;
            }
            $this->inNamespace = '';
            foreach(array_slice($stack, 1, -1) as $tok) {
                $this->inNamespace .= $tok[1];
            }
            $this->aliases = array();

            if (!$this->disableLowercase) {
                $this->inNamespace = strtolower($this->inNamespace);
            }

            return $pos + $stackSize - 1;
        }

        protected function processUse($pos) {
            $list = array(';','(');
            $stack = $this->getTokensTill($pos, $list);
            $stackSize = count($stack);
            if ($stack[$stackSize-1][0] == '(') {
                // ignore closure use
                return $pos + $stackSize - 1;
            }

            if ($this->classBracket > 0) {
                if (!$this->withDeps) {
                    return $pos + $stackSize -1 ;
                }
                // trait use
                $use = '';
                for($t=0; $t<$stackSize; $t++) {
                    $current = (array)$stack[$t];
                    switch($current[0]) {
                        case '{': {
                            // find closing bracket to skip contents
                            for($x=$t+1; $x<$stackSize; $x++) {
                                $tok = $stack[$x];
                                if ($tok[0]=='}') {
                                    $t = $x;
                                    break;
                                }
                            }
                            continue;
                        }
                        case ';':
                        case ',': {
                            $this->dependencies[$this->inClass][] = $use;
                            $use = '';
                            continue;
                        }
                        case T_NS_SEPARATOR:
                        case T_STRING: {
                            $use .= $current[1];
                            continue;
                        }
                    }
                }
            } else {
                // namespace import / alias
                $use = '';
                $alias = '';
                $mode = 'use';
                foreach($stack as $tok) {
                    $current = $tok;
                    switch($current[0]) {
                        case ';':
                        case ',': {
                            if (!$this->disableLowercase) {
                                $use = strtolower($use);
                                $alias = strtolower($alias);
                            }
                            if ($alias == '') {
                                $nss = strrpos($use, '\\');
                                if ($nss !== false) {
                                    $alias = substr($use, $nss+1);
                                } else {
                                    $alias = $use;
                                }
                            }
                            $this->aliases[$use] = $alias;
                            $alias = '';
                            $use = '';
                            $mode = 'use';
                            continue;
                        }
                        case T_NS_SEPARATOR:
                        case T_STRING: {
                            $$mode .= $current[1];
                            continue;
                        }
                        case T_AS: {
                            $mode = 'alias';
                            continue;
                        }
                    }
                }
            }

            return $pos + $stackSize - 1;
        }

        protected function getTokensTill($start, $list) {
            $list = (array)$list;
            $stack = array();
            $skip = array(
                T_WHITESPACE,
                T_COMMENT,
                T_DOC_COMMENT
            );
            $limit = count($this->tokenArray);
            for ($t=$start; $t<$limit; $t++) {
                $current = (array)$this->tokenArray[$t];
                if (in_array($current[0], $skip)) {
                    continue;
                }
                $stack[] = $current;
                if (in_array($current[0], $list)) {
                    break;
                }
            }
            return $stack;
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
                $this->parseFile($file->getPathname());
            }
            return count($this->foundClasses);
        }

    }

    class ClassFinderException extends \Exception {

        const NoDependencies = 1;
        const ClassRedeclaration = 2;
        const ParseError = 3;

    }
}
