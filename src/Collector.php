<?php
namespace TheSeer\Autoload {

    use TheSeer\DirectoryScanner\PHPFilterIterator;

    class Collector {

        /**
         * @var Parser
         */
        private $parser;

        /**
         * @var CollectorResult
         */
        private $collectorResult;

        /**
         * @var bool
         */
        private $tolerantMode;

        /**
         * @var bool
         */
        private $paranoidMode;

        /**
         * @param Parser $parser
         * @param bool   $tolerantMode
         * @param bool   $paranoidMode
         */
        public function __construct(Parser $parser, $tolerantMode = false, $paranoidMode = false) {
            $this->parser = $parser;
            $this->tolerantMode = $tolerantMode;
            $this->paranoidMode = $paranoidMode;
            $this->collectorResult = new CollectorResult();
        }

        public function getResult() {
            return $this->collectorResult;
        }

        public function processDirectory(\Iterator $sources) {
            $worker = $this->paranoidMode ? new PHPFilterIterator($sources) : $sources;
            foreach($worker as $file) {
                try {
                    $parseResult = $this->parser->parse(new SourceFile($file->getRealpath()));
                    if ($parseResult->hasRedeclarations() && !$this->tolerantMode) {
                        throw new CollectorException(
                            sprintf(
                                "Duplicate (potentially conditional) definitions of the following unit(s) found:\n\n\tUnit(s): %s\n\tFile: %s",
                                join(', ', $parseResult->getRedeclarations()),
                                $file->getRealPath()
                            ),
                            CollectorException::InFileRedeclarationFound
                        );
                    }
                    $this->collectorResult->addParseResult($file, $parseResult);
                } catch(ParserException $e) {
                    throw new CollectorException(
                        sprintf(
                            "Could not process file '%s' due to parse errors",
                            $file->getRealPath()
                        ),
                        CollectorException::ParseErrror,
                        $e
                    );
                } catch(CollectorResultException $e) {
                    throw new CollectorException(
                        $e->getMessage(),
                        CollectorException::RedeclarationFound
                    );
                }
            }
        }
    }

    class CollectorException extends \Exception {
        const ParseErrror = 1;
        const RedeclarationFound = 2;
        const InFileRedeclarationFound = 3;
    }
}
