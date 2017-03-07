<?php

namespace HtmlParser\Elements;

use HtmlParser\Selector;

abstract class AbstractNode
{

    protected $uid;
    protected $type;

    /** @var  ChildrenNode */
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
        if(is_null($selector)) {
            return $this->parent;
        }

        list($selector, $func) = Selector::create($selector);

        if ($this->parent == null || $this->parent instanceof RootNode || $depth == 0) {
            return null;
        }

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
        return $this;
    }

    public function replaceWith($new)
    {
        $this->parent->replaceChild($this, $new);
    }

    public function wrap($tag, $attributes = '')
    {
        $newTag = new TagNode($tag, $attributes);
        $this->replaceWith($newTag);
        $newTag->addChild($this);
        return $newTag;
    }

    public function find($selector, $depth = -1)
    {
        return new NodesArray();
    }

    public function after($new)
    {
        $this->parent->afterChild($this, $new);
    }

    public function before($new)
    {
        $this->parent->beforeChild($this, $new);
    }


    abstract public function getInfo(Array $info = []);

    abstract public function getHtml();

    abstract public function getText();


}