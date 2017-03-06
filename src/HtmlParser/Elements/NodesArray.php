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
        return $this->nodes[0];
    }

    public function nth($i)
    {
        return $this->nodes[$i];
    }

    public function last()
    {
        return $this->nodes[count($this->nodes) - 1];
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
        if (is_callable($selector)) {
            $func = $selector;
        } elseif (is_string($selector)) {
            $selector = Selector::build($selector);
            $func = [$selector, 'match'];
        } elseif ($selector instanceof Selector) {
            $func = [$selector, 'match'];
        } else {
            throw new \Exception("Invalid selector");
        }

        $result = new NodesArray();
        $depth--;

        foreach ($this->nodes as $n) {

            if (call_user_func($func, $n)) {
                $result->add($n);
            }

            if ($depth != 0 && $n instanceof CollectionNode) {
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