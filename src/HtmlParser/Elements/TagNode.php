<?php

namespace HtmlParser\Elements;

use HtmlParser\ClosingType;

class TagNode extends ChildrenNode
{
    protected $tag;

    protected $closing = null;

    protected $attributes = [];

    protected $ids = [];

    protected $classes = [];

    public function __construct($tag, $attributes = null, $closing = ClosingType::YES)
    {
        $this->tag = $tag;
        $this->closing = $closing;

        if(is_array($attributes)) {
            $this->attributes = $attributes;
        } elseif (is_string($attributes)) {
            $attRegex = "#(?:[^\s='\"\/]+)=\"(?:[^\"]*)\"|(?:[^\s='\"\/]+)='(?:[^']*)'|(?:[^\s='\"\/]+)=(?:[^'\"\/\s]*)|(?:[^\s='\"\/]+)#";
            preg_match_all($attRegex, $attributes, $matches);
            foreach ($matches[0] as $match) {
                $tmp = explode('=', $match, 2);
                $key = $tmp[0];
                $val = (isset($tmp[1])) ? $tmp[1] : null;
                $val = trim($val, "\"''");
                $this->attributes[$key] = $val;
            }
        }

        if (isset($this->attributes['id'])) {
            $this->ids = array_flip(explode(' ', $this->attributes['id']));
            unset($this->attributes['id']);
        }
        if (isset($this->attributes['class'])) {
            $this->classes = array_flip(explode(' ', $this->attributes['class']));
            unset($this->attributes['class']);
        }

        parent::__construct();
    }

    public function getTag()
    {
        return $this->tag;
    }

    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    public function getClosing()
    {
        return $this->closing;
    }

    public function setClosing($closing)
    {
        $this->closing = $closing;
    }

    /* ------------------------ ATTRIBUTES ------------------------- */

    public function listAttributes()
    {
        return array_keys($this->attributes);
    }

    public function getAttribute($key)
    {
        if (!$this->hasAttribute($key)) {
            return null;
        }
        return $this->attributes[$key];
    }

    public function hasAttribute($key)
    {
        return isset($this->attributes[$key]);
    }

    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /* ------------------------- CLASSES - IDS -------------------------- */

    public function hasClass($class)
    {
        if (is_array($class)) {
            return empty(array_diff($class, array_keys($this->classes)));
        }
        return isset($this->classes[$class]);
    }

    public function addClass($class)
    {
        $this->classes[$class] = true;
        return this;
    }

    public function removeClass($class)
    {
        unset ($this->classes[$class]);
        return this;
    }

    public function hasId($id)
    {
        if (is_array($id)) {
            return empty(array_diff($id, array_keys($this->ids)));
        }
        return isset($this->ids[$id]);
    }

    public function addId($id)
    {
        $this->ids[$id] = true;
        return this;
    }

    public function removeId($id)
    {
        unset ($this->ids[$id]);
        return this;
    }


    /* ------------------------- TEXT - HTML -------------------------- */

    private function att2str()
    {
        $atts = [];
        if (!empty($this->ids)) {
            $atts[] = "id=\"" . implode(' ', array_keys($this->ids)) . "\"";
        }

        if (!empty($this->classes)) {
            $atts[] = "class=\"" . implode(' ', array_keys($this->classes)) . "\"";
        }

        foreach ($this->attributes as $key => $value) {
            if (is_null($value)) {
                $atts[] = $key;
            } else {
                $quote = (strpos($value, "\"") !== false) ? "'" : "\"";
                $atts[] = "{$key}={$quote}{$value}{$quote}";
            }
        }
        return ($atts) ? ' ' . implode(' ', $atts) : '';
    }

    public function getHtml()
    {
        if ($this instanceof RootNode) {
            return parent::getHtml();
        }

        $atts = $this->att2str();

        switch ($this->closing) {

            case ClosingType::SELF:
                return "<{$this->tag}{$atts} />";

            case ClosingType::NO:
                return "<{$this->tag}{$atts}>" . parent::getHtml();

            case ClosingType::YES:
                return "<{$this->tag}{$atts}>" . parent::getHtml() . "</{$this->tag}>";
        }
    }

    public function getText()
    {
        return parent::getText();
    }

    public function getInfo(Array $info = [])
    {
        if (!isset($info[self::class])) {
            $info[self::class] = ['@count' => 0];
        }
        if (!empty($this->tag)) {
            if (!isset($info[self::class][$this->tag])) {
                $info[self::class][$this->tag] = 0;
            }
            $info[self::class][$this->tag]++;
            $info[self::class]['@count']++;
        }
        return parent::getInfo($info);
    }
}