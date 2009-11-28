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

namespace TheSeer\Tools {

   /**
    * Namespace aware parser to find and extract defined classes within php source files
    *
    * @author     Arne Blankerts <arne@blankerts.de>
    * @copyright  Arne Blankerts <arne@blankerts.de>, All rights reserved.
    */
   class ClassFinder {

      /**
       * Parse a given file for defintions of classes and interfaces
       *
       * @param string $file Filename of file to process
       *
       * @return array
       */
      public function parseFile($file) {
         $entries      = array();
         $classFound   = false;
         $nsFound      = false;
         $nsProc       = false;
         $inNamespace  = null;
         $bracketCount = 0;
         $bracketNS    = false;

         $token=token_get_all(file_get_contents($file));
         foreach($token as $tok) {
            if (!is_array($tok)) {
               switch ($tok) {
                  case '{': {
                     $bracketCount++;
                     if ($nsProc) {
                        $bracketNS = true;
                     }
                     $nsProc = false;
                     break;
                  }
                  case '}': {
                     $bracketCount--;
                     if ($bracketCount==0 && $inNamespace && $bracketNS) {
                        $inNamespace = null;
                     }
                     break;
                  }
                  case ";": {
                     if ($nsProc) {
                        $nsProc = false;
                        $bracketNS = false;
                     }
                     break;
                  }
               }
               continue;
            }

            switch ($tok[0]) {
               case T_CLASS:
               case T_INTERFACE: {
                  $classFound = true;
                  continue;
               }
               case T_NAMESPACE: {
                  $nsFound = true;
                  $nsProc  = true;
                  $inNamespace = null;
                  continue;
               }
               case T_NS_SEPARATOR: {
                  if ($nsProc) {
                     $nsFound = true;
                     $inNamespace .= '\\\\';
                  }
                  continue;
               }
               case T_STRING: {
                  if ($nsFound) {
                     $inNamespace .= strtolower($tok[1]);
                     $nsFound = false;
                  } elseif ($classFound) {
                     $entries[($inNamespace ? $inNamespace .'\\\\' : '') . strtolower($tok[1])] = $file;
                     $classFound=false;
                  }
               }
            }
         }
         return $entries;
      }

      /**
       * Process multiple files and parse them for classes and interfaces
       *
       * @param Iterator $sources Iterator based list of files (SplFileObject) to parse
       *
       * @return array
       */
      public function parseMulti(\Iterator $sources) {
         $entries = array();
         $worker = new PHPFilterIterator($sources);
         foreach($worker as $file) {
            $entries = array_merge($entries, $this->parseFile($file->getPathname()));
         }
         return $entries;
      }

   }
}