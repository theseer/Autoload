<?php
namespace TheSeer\Autoload;

class CacheWarmingListRenderer implements StaticListRenderer {
    /**
     * @var boo
     */
    private $addReset;

    /**
     * @var string
     */
    private $indent;

    private $linebreak;

    /**
     * @param boolean    $addReset
     * @param string     $indent
     * @param string     $getLinebreak
     */
    public function __construct($addReset, $indent, $linebreak) {
        $this->addReset = $addReset;
        $this->indent = $indent;
        $this->linebreak = $linebreak;
    }

    /**
     * @return string
     */
    public function render(array $list) {
        $line = $this->indent . 'opcache_compile_file(___BASEDIR___\'';
        $glue = '\');' . $this->linebreak . $line;

        $firstLine = $this->addReset ? $this->indent . 'opcache_reset();' . $this->linebreak : '';
        return $firstLine . $line . implode($glue, $list) . '\');';

    }
}
