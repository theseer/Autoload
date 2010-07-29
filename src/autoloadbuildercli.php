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
    * CLI interface to AutoloadBuilder
    *
    * @author     Arne Blankerts <arne@blankerts.de>
    * @copyright  Arne Blankerts <arne@blankerts.de>, All rights reserved.
    */
   class AutoloadBuilderCLI {

      /**
       * Version identifier
       *
       * @var string
       */
      const VERSION = "%version%";

      /**
       * Main executor method
       *
       * @return void
       */
      public function run() {

         $input = new \ezcConsoleInput();

         $versionOption = $input->registerOption( new \ezcConsoleOption( 'v', 'version' ) );
         $versionOption->shorthelp    = 'Prints the version and exits';
         $versionOption->isHelpOption = true;

         $helpOption = $input->registerOption( new \ezcConsoleOption( 'h', 'help' ) );
         $helpOption->isHelpOption = true;
         $helpOption->shorthelp    = 'Prints this usage information';

         $outputOption = $input->registerOption( new \ezcConsoleOption(
            'o', 'output', \ezcConsoleInput::TYPE_STRING, 'STDOUT', false,
            'Output file for generated code (default: STDOUT)'
            ));

         $pharOption = $input->registerOption( new \ezcConsoleOption(
            'p', 'phar', \ezcConsoleInput::TYPE_NONE, null, false,
            'Build a phar archive of directory contents',
            null,
            array( new \ezcConsoleOptionRule( $input->getOption( 'o' ) ) )
         ));

         $input->registerOption( new \ezcConsoleOption(
            'i', 'include', \ezcConsoleInput::TYPE_STRING, '*.php', true,
            'File pattern to include (default: *.php)'
         ));
         $input->registerOption( new \ezcConsoleOption(
            'e', 'exclude', \ezcConsoleInput::TYPE_STRING, null, true,
            'File pattern to exclude'
         ));
         $input->registerOption( new \ezcConsoleOption(
            'b', 'basedir', \ezcConsoleInput::TYPE_STRING, null, false,
            'Basedir for filepaths'
         ));
         $input->registerOption( new \ezcConsoleOption(
            't', 'template', \ezcConsoleInput::TYPE_STRING, null, false,
            'Path to code template to use'
         ));
         $input->registerOption( new \ezcConsoleOption(
            '', 'format', \ezcConsoleInput::TYPE_STRING, null, false,
            'Dateformat string for timestamp'
         ));
         $input->registerOption( new \ezcConsoleOption(
            '', 'linebreak', \ezcConsoleInput::TYPE_STRING, null, false,
            'Linebreak style (CR, CR/LF or LF)'
         ));
         $input->registerOption( new \ezcConsoleOption(
            '', 'indent', \ezcConsoleInput::TYPE_STRING, null, false,
            'String used for indenting (default: 3 spaces)'
         ));
         $lintOption = $input->registerOption( new \ezcConsoleOption(
            '', 'lint', \ezcConsoleInput::TYPE_NONE, null, false,
            'Run lint on generated code'
         ));
         $input->registerOption( new \ezcConsoleOption(
            '', 'lint-php', \ezcConsoleInput::TYPE_STRING, null, false,
            'PHP binary path for linting (default: /usr/bin/php or c:\\php\\php.exe)'
         ));

         $input->argumentDefinition = new \ezcConsoleArguments();
         $input->argumentDefinition[0] = new \ezcConsoleArgument( "directory" );
         $input->argumentDefinition[0]->shorthelp = "The directory to process.";

         try {
            $input->process();
         } catch (\ezcConsoleException $e) {
            echo $e->getMessage()."\n\n";
            $this->showVersion();
            $this->showUsage();
            exit(3);
         }

         if ($helpOption->value === true) {
            $this->showVersion();
            $this->showUsage();
            exit(0);
         }

         if ($versionOption->value === true ) {
            $this->showVersion();
            exit(0);
         }

         try {
            $scanner = $this->getScanner($input);
            if ($pharOption->value !== false) {
               $phar = $this->buildPhar($scanner, $input);
               $scanner->rewind();
            }
            $finder = new ClassFinder;
            $found  = $finder->parseMulti($scanner);
            // this unset is needed to "fix" a segfault on shutdown
            unset($scanner);

            $builder = $this->getBuilder($found, $input);

            if ($lintOption->value === true) {
               exit( $this->lintCode($builder->render(), $input) ? 0 : 4);
            }

            if ($outputOption->value == 'STDOUT') {
               echo $builder->render();
            } else {
               if ($pharOption->value !== false) {
                  $builder->setVariable('PHAR', basename($outputOption->value));
                  $phar->setStub($builder->render());
                  $phar->stopBuffering();
                  echo "phar archive '{$outputOption->value}' generated.\n\n";
               } else {
                  $builder->save($outputOption->value);
                  echo "Autoload file '{$outputOption->value}' generated.\n\n";
               }
            }
            exit(0);

         } catch (\Exception $e) {
            fwrite(STDERR, "Error while processing request:\n");
            fwrite(STDERR, ' - ' . $e->getMessage()."\n");
            exit(1);
         }
      }

      /**
       * Helper to get instance of DirectoryScanner with cli options applied
       *
       * @param ezcConsoleInput $input  CLI Options pased to app
       *
       * @return Theseer\Tools\IncludeExcludeFilterIterator
       */
      protected function getScanner(\ezcConsoleInput $input) {
         $scanner = new DirectoryScanner;

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

         $args = $input->getArguments();
         return $scanner($args[0]);
      }

      /**
       * Helper to get instance of AutoloadBuilder with cli options applied
       *
       * @param Array            $found Iterator or Array with files to process
       * @param \ezcConsoleInput $input CLI Options pased to app
       */
      protected function getBuilder(Array $found, \ezcConsoleInput $input) {
         $ab = new AutoloadBuilder($found);

         $basedir = $input->getOption('basedir');
         if ($basedir->value) {
            $ab->setBaseDir($basedir->value);
         } else {
            $args = $input->getArguments();
            $ab->setBaseDir(realpath($args[0]));
         }

         $phar = $input->getOption('phar');
         if ($phar->value) {
            $ab->setTemplateFile( __DIR__ . '/templates/phar.php.tpl');
         }

         $template = $input->getOption('template');
         if ($template->value) {
            if (!file_exists($template->value)) {
               $alternative = __DIR__.'/templates/'.$template->value;
               if (file_exists($alternative)) {
                  $template->value = $alternative;
               }
            }
            $ab->setTemplateFile($template->value);
         }

         $format = $input->getOption('format');
         if ($format->value) {
            $ab->setDateTimeFormat($format->value);
         }

         $indent = $input->getOption('indent');
         if ($indent->value) {
            $ab->setIndent($indent->value);
         }

         $linebreak = $input->getOption('linebreak');
         if ($linebreak->value !== false) {
            $lbr = array('LF' => "\n", 'CR' => "\r", 'CRLF' => "\r\n" );
            if (in_array($linebreak->value, $lbr)) {
               $ab->setLineBreak($lbr[$linebreak->value]);
            } else {
               $ab->setLineBreak($linebreak->value);
            }
         } else {
            $ab->setLineBreak(PHP_EOL);
         }

         return $ab;
      }


      protected function buildPhar(\Iterator $scanner, \ezcConsoleInput $input) {
         $basedir = $input->getOption('basedir')->value;
         $phar    = new \Phar($input->getOption('output')->value, 0, basename($input->getOption('output')->value));
         $phar->startBuffering();
         if ($basedir) {
            $phar->buildFromIterator($scanner, $basedir);
         } else {
            $args = $input->getArguments();
            $phar->buildFromIterator($scanner, $args[0]);
         }
         return $phar;
      }

      /**
       * Helper to execute a lint check on generated code
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
         if ($php->value === false) {
            $binary = PHP_OS === 'WIN' ? 'C:\php\php.exe' : '/usr/bin/php';
         } else {
            $binary = $php->value;
         }

         $process = proc_open($binary . ' -l', $dsp, $pipes);

         if (!is_resource($process)) {
            fwrite(STDERR, "Opening php binary for linting failed.\n");
            exit(1);
         }

         fwrite($pipes[0], $code);
         fclose($pipes[0]);

         $stdout = stream_get_contents($pipes[1]);
         fclose($pipes[1]);

         $stderr = stream_get_contents($pipes[2]);
         fclose($pipes[2]);

         $rc = proc_close($process);

         if ($rc == 255) {
            fwrite(STDERR, "Syntax errors during lint:\n" .
                           str_replace('in - on line', 'in generated code on line', $stderr) .
                           "\n");
            return false;
         }

         echo "Lint check of geneated code okay\n\n";
         return true;
      }

      /**
       * Helper to output version information
       */
      protected function showVersion() {
         printf("phpab %s - Copyright (C) 2009 - 2010 by Arne Blankerts\n\n", self::VERSION);
      }

      /**
       * Helper to output usage information
       */
      protected function showUsage() {
         print <<<EOF
Usage: phpab [switches] <directory>

  -i, --include    File pattern to include (default: *.php)
  -e, --exclude    File pattern to exclude

  -b, --basedir    Basedir for filepaths
  -t, --template   Path to code template to use

  -o, --output     Output file for generated code (default: STDOUT)
  -p, --phar       Create a phar archive (requires -o )

      --format     Dateformat string for timestamp
      --linebreak  Linebreak style (CR, CR/LF or LF)
      --indent     String used for indenting (default: 3 spaces)

      --lint       Run lint on generated code and exit
      --lint-php   PHP binary to use for linting (default: /usr/bin/php or c:\php\php.exe)

  -h, --help       Prints this usage information
  -v, --version    Prints the version and exits


EOF;
      }
   }
}