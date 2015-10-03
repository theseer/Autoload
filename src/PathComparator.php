<?php
namespace TheSeer\Autoload {

    class PathComparator {

        /**
         * @var string[]
         */
        private $directories = array();

        /**
         * PathComparator constructor.
         *
         * @param array $directories
         */
        public function __construct(array $directories) {
            foreach($directories as $dir) {
                $this->directories[] = realpath($dir);
            }
        }

        public function getCommondBase() {
            if (count($this->directories) == 0) {
                return '/';
            }
            $result = $this->directories[0];
            foreach($this->directories as $dir) {
                $result = substr($dir, 0, $this->commonPrefix($result, $dir));
            }
            return rtrim($result, '/');
        }


        private function commonPrefix( $s1, $s2, $i=0 ) {
            return (
                $i<strlen($s1) && $i<strlen($s2) && $s1[$i] == $s2[$i]
            ) ? $this->commonPrefix( $s1, $s2, ++$i ) : $i;
        }
    }

}
