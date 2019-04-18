<?php
namespace TheSeer\Autoload {

    class CollectorResult {

        /**
         * @var array
         */
        private $whitelist;

        /**
         * @var array
         */
        private $blacklist;

        /**
         * @var array
         */
        private $units = array();

        /**
         * @var array
         */
        private $dependencies = array();

        /**
         * @var array
         */
        private $seenFiles = array();

        /**
         * @var array
         */
        private $duplicates = array();

        public function __construct(array $whitelist, array $blacklist) {
            $this->whitelist = $whitelist;
            $this->blacklist = $blacklist;
        }

        public function hasResultFor(\SplFileInfo $file) {
            return isset($this->seenFiles[$file->getRealPath()]);
        }

        public function addParseResult(\SplFileInfo $file, ParseResult $result) {
            if (!$result->hasUnits()) {
                return;
            }
            $filename = $file->getRealPath();
            $this->seenFiles[$filename] = true;

            foreach($result->getUnits() as $unit) {
                if (!$this->accept($unit)) {
                    continue;
                }
                if (isset($this->units[$unit])) {
                    if (!isset($this->duplicates[$unit])) {
                        $this->duplicates[$unit] = array( $this->units[$unit] );
                    }
                    $this->duplicates[$unit][] = $filename;
                    continue;
                }
                $this->units[$unit] = $filename;
                $this->dependencies[$unit] = $result->getDependenciesForUnit($unit);
            }
        }

        public function hasUnits() {
            return count($this->units) > 0;
        }

        public function hasDuplicates() {
            return count($this->duplicates) > 0;
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

        /**
         * @param string $unit
         *
         * @return bool
         */
        private function accept($unit) {
            foreach($this->blacklist as $entry) {
                if (fnmatch($entry, $unit)) {
                    return false;
                }
            }
            foreach($this->whitelist as $entry) {
                if (fnmatch($entry, $unit)) {
                    return true;
                }
            }
            return false;
        }

        public function getDuplicates() {
            return $this->duplicates;
        }

    }

    class CollectorResultException extends \Exception {
        const DuplicateUnitName = 1;
    }

}
