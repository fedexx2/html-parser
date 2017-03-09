<?php

namespace HtmlParser\Elements;

use HtmlParser\Selector;


class NodesArray implements \IteratorAggregate, \Countable
{
    protected $nodes;

    public function __construct($init = [])
    {
        $this->nodes = $init;
    }

    public function getArray()
    {
        return $this->nodes;
    }

    /* -------------------------- INTERFACES --------------------------- */

    public function getIterator()
    {
        return new \ArrayIterator($this->nodes);
    }

    public function count()
    {
        return count($this->nodes);
    }

    /* ------------------------ ITEMS MANIPULATION ------------------------- */

    public function first()
    {
        return isset($this->nodes[0]) ? $this->nodes[0] : null;
    }

    public function nth($i)
    {
        return isset($this->nodes[$i]) ? $this->nodes[$i] : null;
    }

    public function last()
    {
        return isset($this->nodes[count($this->nodes) - 1]) ? $this->nodes[count($this->nodes) - 1] : null;
    }

    public function add(AbstractNode $node)
    {
        $this->nodes[] = $node;
    }

    public function addRange($nodes)
    {
        foreach ($nodes as $n) {
            $this->nodes[] = $n;
        }
        return $this;
    }

    public function find($selector, $depth = -1)
    {
        list($selector, $func) = Selector::create($selector);

        $result = new NodesArray();
        $depth--;

        foreach ($this->nodes as $n) {

            if (call_user_func($func, $n)) {
                $result->add($n);
            }

            if ($depth != 0 && $n instanceof ChildrenNode) {
                $result->addRange($n->find($func, $depth));
            }
        }

        if ($selector instanceof Selector && $selector->hasNext()) {
            $selector = $selector->getNext();
            $ec = $result->explodeChildren();
            return $ec->find($selector, $selector->getDepth());
        }
        return $result;
    }

    public function explodeChildren()
    {
        $children = new NodesArray();
        foreach ($this->nodes as $n) {
            $children->addRange($n);
        }
        return $children;
    }

    public function wrap($tag, $attributes = '')
    {
        foreach ($this->nodes as $n) {
            $n->wrap($tag, $attributes);
        }
    }

    public function detach()
    {
        foreach ($this->nodes as $n) {
            $n->detach();
        }
    }
}