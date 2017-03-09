<?php

namespace HtmlParser\Elements;

use HtmlParser\ClosingType;

class TagNode extends ChildrenNode
{
    protected $tag;

    protected $closing = null;

    protected $attributes = [];

    protected $id = [];

    protected $class = [];

    public function __construct($tag, $attributes = null, $closing = ClosingType::YES)
    {
        $this->tag = $tag;
        $this->closing = $closing;

        if (is_array($attributes)) {
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

        foreach (['id', 'class'] as $a) {
            if (isset($this->attributes[$a])) {
                $this->$a = array_flip(explode(' ', $this->attributes[$a]));
                unset($this->attributes[$a]);
            }
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

    /* ------------------------- CLASS - ID -------------------------- */

    public function hasClass($class)
    {
        if (is_array($class)) {
            return empty(array_diff($class, array_keys($this->class)));
        }
        return isset($this->class[$class]);
    }

    public function addClass($class)
    {
        $this->class[$class] = true;
        return this;
    }

    public function removeClass($class)
    {
        unset ($this->class[$class]);
        return this;
    }

    public function hasId($id)
    {
        if (is_array($id)) {
            return empty(array_diff($id, array_keys($this->id)));
        }
        return isset($this->id[$id]);
    }

    public function addId($id)
    {
        $this->id[$id] = true;
        return this;
    }

    public function removeId($id)
    {
        unset ($this->id[$id]);
        return this;
    }


    /* ------------------------- TEXT - HTML -------------------------- */

    private function att2str()
    {
        $atts = [];

        foreach (['id', 'class'] as $a) {
            if (!empty($this->$a)) {
                $atts[] = "{$a}=\"" . implode(' ', array_keys($this->$a)) . "\"";
            }
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