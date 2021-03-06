<?php

namespace HtmlParser\Elements;

use HtmlParser\Selector;

class ChildrenNode extends AbstractNode implements \IteratorAggregate, \Countable
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
        $node->detach();
        $node->parent = $this;
        $this->nodes[] = $node;
        return $this;
    }

    public function addChildren($nodes)
    {
        foreach ($nodes as $n) {
            $n->detach();
            $n->parent = $this;
            $this->nodes[] = $n;
        }
        return $this;
    }

    public function detachChildren()
    {
        $ret = $this->nodes;
        $this->nodes = [];
        foreach ($ret as $n) {
            $n->parent = null;
        }
        return $ret;
    }

    private function removeAt($position)
    {
        $this->nodes[$position]->parent = null;
        array_splice($this->nodes, $position, 1);
    }

    private function insertAt($position, $new)
    {
        if ($new instanceof AbstractNode) {
            $new = [$new];
        } elseif ($new instanceof NodesArray) {
            $new = $new->getArray();
        } elseif (!is_array($new)) {
            throw new \Exception("Invalid new nodes");
        }

        foreach ($new as $n) {
            $n->detach();
            $n->parent = $this;
        }
        array_splice($this->nodes, $position, 0, $new);
    }

    public function removeChild(AbstractNode $child)
    {
        if (($i = array_search($child, $this->nodes)) !== false) {
            $this->removeAt($i);
        }
    }

    public function replaceChild(AbstractNode $child, $new)
    {
        if (($i = array_search($child, $this->nodes)) !== false) {
            $this->removeAt($i);
            $this->insertAt($i, $new);
        }
    }

    public function afterChild(AbstractNode $child, $new)
    {
        if (($i = array_search($child, $this->nodes)) !== false) {
            $this->insertAt($i + 1, $new);
        }
    }

    public function beforeChild(AbstractNode $child, $new)
    {
        if (($i = array_search($child, $this->nodes)) !== false) {
            $this->insertAt($i, $new);
        }
    }

    public function replaceWithChildren()
    {
        $this->parent->replaceChild($this, $this->nodes);
    }

    public function getInfo(Array $info = [])
    {
        $info = parent::getInfo($info);
        foreach ($this->nodes as $n) {
            $info = $n->getInfo($info);
        }
        return $info;
    }
}