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
    * Builds spl based autoload code for inclusion into projects
    *
    * @author     Arne Blankerts <arne@blankerts.de>
    * @copyright  Arne Blankerts <arne@blankerts.de>, All rights reserved.
    */
   class AutoloadBuilder {

      /**
       * Associative array of classes (key) and the files (value) they are in
       *
       * @var array
       */
      protected $classes;

      /**
       * An optional base dir to strip for the realpath of the filename
       *
       * @var string
       */
      protected $baseDir;

      /**
       * Indenting char(s)
       *
       * @var string
       */
      protected $indent;

      /**
       * Char(s) used as linebreak
       *
       * @var string
       */
      protected $linebreak = PHP_EOL;

      /**
       * PHP Template code to render autoload list
       *
       * @var string
       */
      protected $template;

      /**
       * Timestamp of production start
       *
       * @var integer
       */
      protected $timestamp;

      /**
       * Format string supplied to date() for use with ___CREATED___
       *
       * @var string
       */
      protected $dateformat = 'r';

      /**
       * Variables for templates
       *
       * @var array
       */
      protected $variables = array();

      /**
       * Constructor of AutoloadBuilder class
       *
       * @param array  $classlist Array of classes
       * @param string $baseDir   Base folder for class files
       * @param string $tpl       Template file to use for generated code
       * @param string $indent    Chars to set default indenting to
       *
       * @return void
       */
      public function __construct(array $classlist, $baseDir = '', $tpl = null, $indent = '         ') {
         $this->classes = $classlist;
         $this->baseDir = $baseDir;
         $this->indent  = $indent;
         if ($tpl === null) {
            $tpl = __DIR__ . '/templates/default.php.tpl';
         }
         $this->setTemplateFile($tpl);
      }

      /**
       * Setter to load a new template from a file
       *
       * @param string $fname File to load as template
       *
       * @return void
       */
      public function setTemplateFile($fname) {
         if (!file_exists($fname)) {
            throw new AutoloadBuilderException("Template '$fname' not found.", AutoloadBuilderException::TemplateNotFound);
         }
         $this->template = file_get_contents($fname);
      }

      /**
       * Setter for Template from string
       *
       * @param string $tpl Template code string
       *
       * @return void
       */
      public function setTemplateCode($tpl) {
         $this->template = $tpl;
      }

      /**
       * Setter for the Basedir
       *
       * @param string $dir Path to strip from beginning of filenames
       *
       * @return void
       */
      public function setBaseDir($dir) {
         $this->baseDir = $dir;
      }

      /**
       * Overwrite default or previously set indenting option
       *
       * @param string $indent Char(s) to use for indenting
       *
       * @return void
       */
      public function setIndent($indent) {
         $this->indent = $indent;
      }

      /**
       * Overwrite default or previously set linebreak chars
       *
       * @param string $lbs Code to set linebreak
       *
       * @return void
       */
      public function setLineBreak($lbs) {
         $this->linebreak = $lbs;
      }

      /**
       * Setter to use allow usage of fixed date/time for ___CREATED___
       *
       * @param integer $time unix timestamp
       */
      public function setTimestamp($time) {
         if (!is_int($time) && !is_null($time)) {
            throw new AutoloadBuilderException("'$time' is not a unix timestamp", AutoloadBuilderException::InvalidTimestamp);
         }
         $this->timestamp = $time;
      }

      /**
       * Setter to adjust the date/time format output of ___CREATED___
       *
       * @param string $frmt Date/Time format string
       */
      public function setDateTimeFormat($frmt) {
         $this->dateformat = $frmt;
      }

      /**
       * Set a variable for use with template code
       *
       * @param string $name  Key name (use as ___key___ in template)
       * @param string $value Value to use
       */
      public function setVariable($name, $value) {
         $this->variables['___'.$name.'___'] = $value;
      }

      /**
       * Render autoload code into a string
       *
       * @return string
       */
      public function render() {
         $entries = array();
         foreach($this->classes as $class => $file) {
            $fname = realpath($file);
            if (!empty($this->baseDir) && strpos($fname, $this->baseDir)===0) {
               $fname = str_replace($this->baseDir, '', $fname);
            }
            $entries[] = "'$class' => '$fname'";
         }

         $replace = array_merge($this->variables, array(
            '___CREATED___'   => date( $this->dateformat, $this->timestamp ? $this->timestamp : time()),
            '___CLASSLIST___' => join( ',' . $this->linebreak . $this->indent, $entries),
            '___BASEDIR___'   => $this->baseDir ? '__DIR__ . ' : '',
            '___AUTOLOAD___'  => uniqid('autoload')
         ));
         return str_replace(array_keys($replace), array_values($replace), $this->template);
      }

      /**
       * Save autoload code to given filename
       *
       * @param string $filename Filename to store code in
       *
       * @return integer|boolean
       */
      public function save($filename) {
         return file_put_contents($filename, $this->render());
      }
   }


   class AutoloadBuilderException extends \Exception {

      const TemplateNotFound = 1;
      const InvalidTimestamp = 2;

   }

}