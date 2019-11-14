<?php
namespace TheSeer\Autoload;

class StaticRequireListRenderer implements StaticListRenderer {

    /** @var boolean */
    private $useOnce;

    /** @var string */
    private $indent;

    /** @var string */
    private $linebreak;

    /**
     * @param $useOnce
     */
    public function __construct($useOnce, $indent, $linebreak) {
        $this->useOnce = $useOnce;
        $this->indent = $indent;
        $this->linebreak = $linebreak;
    }

    /**
     * @return string
     */
    public function render(array $list) {
        $require = (boolean)$this->useOnce ? 'require_once' : 'require';
        $glue = '\';' . $this->linebreak . $this->indent . $require . ' ___BASEDIR___\'';

        return $this->indent . $require . ' ' . implode($glue, $list) . '\';';
    }
}
