<?php
/**
 * Copyright (c) 2009-2019 Arne Blankerts <arne@blankerts.de>
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

    /**
     * Builds static require list for inclusion into projects
     *
     * @author     Arne Blankerts <arne@blankerts.de>
     * @copyright  Arne Blankerts <arne@blankerts.de>, All rights reserved.
     */
    class StaticRenderer extends AutoloadRenderer {

        private $dependencies;
        private $phar;
        private $require = 'require';

        /**
         * @var StaticListRenderer
         */
        private $renderHelper;

        public function __construct(array $classlist, StaticListRenderer $renderHelper) {
            parent::__construct($classlist);
            $this->renderHelper = $renderHelper;
        }

        /**
         * Setter for Dependency Array
         * @param array $dep Dependency Array from classfinder
         */
        public function setDependencies(Array $dep) {
            $this->dependencies = $dep;
        }

        /**
         * Toggle phar outut mode
         *
         * @param boolean $phar
         */
        public function setPharMode($phar) {
            $this->phar = (boolean)$phar;
        }

        /**
         * Toggle wether or not to use require_once over require
         *
         * @param boolean $mode
         */
        public function setRequireOnce($mode) {
        }


        /**
         * @param string $template
         *
         * @return string
         */
        public function render($template) {
            $baseDir = '';
            if ($this->phar) {
                $baseDir = "'phar://". $this->variables['___PHAR___']."' . ";
            } else if ($this->baseDir) {
                $baseDir = $this->compat ? 'dirname(__FILE__) . ' : '__DIR__ . ';
            }

            $entries = array();
            foreach($this->sortByDependency() as $fname) {
                $entries[] = $this->resolvePath($fname);
            }

            $replace = array_merge(
                $this->variables,
                array(
                    '___CREATED___'   => date( $this->dateformat, $this->timestamp ? $this->timestamp : time()),
                    '___FILELIST___' => $this->renderHelper->render($entries),
                    '___BASEDIR___'   => $baseDir,
                    '___AUTOLOAD___'  => uniqid('autoload', true)
                )
            );

            return str_replace(array_keys($replace), array_values($replace), $template);
        }

        /**
         * Helper to sort classes/interfaces and traits based on their depdendency info
         *
         * @return array
         */
        protected function sortByDependency() {
            $sorter  = new ClassDependencySorter($this->classes, $this->dependencies);
            $list    = $sorter->process();

            return array_unique($list);
        }

    }

}
