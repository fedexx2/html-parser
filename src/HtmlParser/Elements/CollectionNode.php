<?php

namespace HtmlParser\Elements;

use HtmlParser\NodeCollection;
use HtmlParser\Selector;

class CollectionNode extends AbstractNode implements \IteratorAggregate, \Countable
{
    protected $nodes;
    protected $position;

    public function __construct()
    {
        $this->nodes = [];
        parent::__construct();
    }

    /* ------------------------- TEXT - HTML -------------------------- */

    public function getHtml()
    {
        $html = '';
        /** @var AbstractNode $node */
        foreach ($this->nodes as $node) {
            $html .= $node->getHtml();
        }
        return $html;
    }

    public function getText()
    {
        $text = '';
        /** @var AbstractNode $node */
        foreach ($this->nodes as $node) {
            $text .= $node->getText();
        }
        return $text;
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

    /* ------------------------ TREE MANIPULATION ------------------------- */

    public function find($selector, $depth = -1)
    {
        return (new NodesArray($this->nodes))->find($selector, $depth);
    }

    public function addChild(AbstractNode $node)
    {
        $node->parent = $this;
        $this->nodes[] = $node;
        return $this;
    }

    public function addChildren($nodes)
    {
        foreach ($nodes as $n) {
            $n->parent = $this;
            $this->nodes[] = $n;
        }
        return $this;
    }

    public function detachChildren()
    {
        $ret = [];
        foreach ($this->nodes as $n) {
            $ret[] = $n->detach();
        }
        return $ret;
    }


    public function removeChild(AbstractNode $node)
    {
        foreach ($this->nodes as $i => $n) {
            if ($n == $node) {
                array_splice($this->nodes, $i, 1);
            }
        }
    }

    public function replaceChild($child, $new)
    {
        if ($new instanceof AbstractNode) {
            $new = [$new];
        } elseif ($new instanceof NodesArray) {
            $new = $new->getArray();
        } elseif (!is_array($new)) {
            throw new \Exception("Invalid new nodes");
        }

        foreach($this->nodes as $i => $c) {
            if($child == $c) {
                array_splice($this->nodes, $i, 1, $new);
                $c->parent = null;

                foreach($new as $n) {
                    $n->parent = $this;
                }
                break;
            }
        }
    }

    public function replaceWithChildren()
    {
        $this->parent->replaceChild($this, $this->nodes);
    }

    public function getInfo(Array $info = [])
    {
        foreach ($this->nodes as $n) {
            $info = $n->getInfo($info);
        }
        return $info;
    }


}