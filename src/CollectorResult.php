<?php
namespace TheSeer\Autoload {

    class CollectorResult {

        private $units = array();
        private $dependencies = array();

        public function addParseResult(\SplFileInfo $file, ParseResult $result) {
            if (!$result->hasUnits()) {
                return;
            }
            $filename = $file->getRealPath();
            foreach($result->getUnits() as $unit) {
                if (isset($this->units[$unit])) {
                    throw new CollectorResultException(
                        sprintf(
                            'Redeclaration of unit "%s" in file "%s" (first occurance was in file "%s")',
                            $unit,
                            $filename,
                            $this->units[$unit]
                        ),
                        CollectorResultException::DuplicateUnitName
                    );
                }
                $unit = addslashes($unit);
                $this->units[$unit] = $filename;
                $this->dependencies[$unit] = $result->getDependenciesForUnit($unit);
            }

        }

        public function hasUnits() {
            return count($this->units) > 0;
        }

        /**
         * @return array
         */
        public function getDependencies() {
            return $this->dependencies;
        }

        /**
         * @return array
         */
        public function getUnits() {
            return $this->units;
        }
    }

    class CollectorResultException extends \Exception {
        const DuplicateUnitName = 1;
    }

}
