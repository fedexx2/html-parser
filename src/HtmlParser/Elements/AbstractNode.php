<?php

namespace HtmlParser\Elements;

use HtmlParser\Selector;

abstract class AbstractNode
{

    protected $uid;
    protected $type;

    /** @var  AbstractNode */
    protected $parent;


    public function __construct()
    {
        $this->uid = sha1(spl_object_hash($this) . uniqid('', true));
    }

    public function getUid()
    {
        return $this->uid;
    }

    public function parent($selector = null, $depth = -1)
    {
        if (empty($selector)) {
            return $this->parent;
        } elseif (is_callable($selector)) {
            $func = $selector;
        } elseif (is_string($selector)) {
            $selector = Selector::build($selector);
            $func = [$selector, 'match'];
        } elseif ($selector instanceof Selector) {
            $func = [$selector, 'match'];
        } else {
            throw new \Exception("Invalid selector");
        }

        if ($this->parent == null || $this->parent instanceof RootNode || $depth == 0) {
            return null;
        }

        $result = null;

        if ($func($this->parent)) {

            if ($selector instanceof Selector && $selector->hasNext()) {
                $selector = $selector->getNext();
                return $this->parent->find($selector, $selector->getDepth());
            }
            return $this->parent;
        }

        return $this->parent->parent($selector, $depth - 1);
    }

    public function detach()
    {
        $this->parent->removeChild($this);
        $this->parent = null;
        return $this;
    }

    public function replaceWith($new)
    {
        if ($new instanceof AbstractNode) {
            $new = [$new];
        } elseif ($new instanceof NodesArray) {
            $new = $new->getArray();
        } elseif (is_array($new)) {
        } else {
            throw new \Exception("Invalid new nodes");
        }
        $parent = $this->parent;
        $done = false;
        foreach ($parent->nodes as $i => $n) {
            if ($n == $this) {
                array_splice($parent->nodes, $i, 1, $new);
                $done = true;
            }
        }
        if ($done) {
            foreach ($new as $n) {
                $n->parent = $parent;
            }
            $this->parent = null;
        }
    }

    public function wrap($tag, $attributes = '')
    {
        $newTag = new TagNode($tag, $attributes);
        $this->replaceWith($newTag);
        $newTag->addChild($this);
        return $newTag;
    }

    abstract public function getInfo(Array $info = []);

    abstract public function getHtml();

    abstract public function getText();


}