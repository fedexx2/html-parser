<?php

namespace HtmlParser;

class NodeCollection implements \Iterator
{
    public $nodes = [];

    public function __construct($init = null)
    {
        if (is_array($init))
            $this->nodes = $init;
        else if (is_null($init))
            $this->nodes = [];
        else
            $this->nodes = [$init];
    }

    /* -------------------- ARRAY MANIPULATION --------------------- */

    public function add(Node $node)
    {
        $this->nodes[] = $node;
        return $this;
    }

    public function addRange(NodeCollection $nodes)
    {
        $this->nodes = array_merge($this->nodes, $nodes->nodes);
        return $this;
    }

    public function clear()
    {
        $this->nodes = [];
        return $this;
    }

    public function getCount()
    {
        return count($this->nodes);
    }

    public function remove(Node $node)
    {
        /**
         * @var int $i
         * @var Node $n
         */
        foreach($this->nodes as $i => $n)
            if($n->getUid() == $node->getUid())
                array_splice($this->nodes, $i, 1);
        return $this;
    }

    public function first()
    {
        return $this->nth(0);
    }

    public function nth($i)
    {
        return isset($this->nodes[$i]) ? $this->nodes[$i] : null;
    }

    public function replaceNodeWith(Node $old, $new)
    {
        /** @var Node $n */
        foreach($this->nodes as $i => $n) {
            if ($n->getUid() == $old->getUid()) {

                if(is_a($new, self::class)) {
                    array_splice($this->nodes, $i, 1, $new->nodes);
                }
                else {
                    $this->nodes[$i] = $new;
                }
            }
        }
        return $this;
    }


    /* -------------------------- QUERY -------------------------- */

    public function select($selector, $levels = -1)
    {
        preg_match_all('/[\.#]?([^\.#]+)/', strtolower($selector), $match, PREG_SET_ORDER);

        $tag = '';
        $ids = [];
        $classes = [];

        foreach ($match as $m) {
            if($m[0][0] == '.')
                $classes[] = $m[1];
            else if ($m[0][0] == '#')
                $ids[] = $m[1];
            else
                $tag = $m[1];
        }

        $result = $this->find(function($n) use($tag, $ids, $classes)
        {
            return $n->match($tag, $ids, $classes);
        },$levels);

        return $result;
    }

    public function find($function, $levels = -1)
    {
        $result = new NodeCollection();
        $levels--;

        foreach($this->nodes as $n)
        {
            if($function($n))
                $result->add($n);

            if($levels != 0)
                if($n->getType() == NodeType::TAG || $n->getType() == NodeType::ROOT)
                    $result->addRange($n->find($function, $levels));
        }
        return $result;
    }

    public function each($function, $levels = -1)
    {
        $levels--;

        /** @var Node $n */
        foreach($this->nodes as $n) {
            $function($n);

            if($levels != 0)
                if($n->getType() == NodeType::TAG || $n->getType() == NodeType::ROOT)
                    $n->each($function, $levels - 1);
        }
    }

    /* ------------------------ ITERATOR ------------------------- */

    private $_position = 0;

    function rewind() {
        $this->_position = 0;
    }

    function current() {
        return $this->nodes[$this->_position];
    }

    function key() {
        return $this->_position;
    }

    function next() {
        ++$this->_position;
    }

    function valid() {
        return isset($this->nodes[$this->_position]);
    }
}