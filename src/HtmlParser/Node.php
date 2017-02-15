<?php

namespace HtmlParser;

class Node
{
    protected $uid;
    protected $type;
    protected $closing;

    protected $tag;
    protected $text;
    protected $rawAtt;

    /** @var  Node */
    protected $parent;

    /** @var NodeCollection  */
    protected $children = null;

    protected $attributes = [];
    protected $ids = [];
    protected $classes = [];

    private function __construct($uid, $type, $tag=null, $rawAtt=null, $text=null)
    {
        $this->uid = $uid;
        $this->tag = $tag;
        $this->text = $text;
        $this->type = $type;
        $this->rawAtt = $rawAtt;

        if($this->type == NodeType::TAG || $this->type == NodeType::ROOT)
            $this->children = new NodeCollection();

        if ($this->type == NodeType::TAG && $this->rawAtt)
            $this->parseAttributes();
    }


    public function addChild($node)
    {
        if(is_null($this->children))
            throw new \Exception("this->children IS NULL");

        $this->children->add($node);
        $node->parent = $this;
        return $this;
    }

    public function addChildren($nc)
    {
        if(is_null($this->children))
            throw new \Exception("this->children IS NULL");

        $nc->each(function($n) {
            $n->parent = $this;
        });
        $this->children->addRange($nc);
        return $this;
    }

    public function clearChildren()
    {
        if(is_null($this->children))
            throw new \Exception("this->children IS NULL");
        $this->children->clear();
        return $this;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function hasAttribute($key)
    {
        return isset($this->attributes[$key]);
    }

    public function getAttribute($key)
    {
        if(!isset($this->attributes[$key]))
            return null;
        return $this->attributes[$key];
    }

    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function hasClass($class)
    {
        return in_array($class, $this->classes);
    }

    public function addClass($class)
    {
        $pos = array_search($class, $this->classes);
        if($pos === false)
            $this->classes[] = $class;
        return this;
    }

    public function removeClass($class)
    {
        $pos = array_search($class, $this->classes);
        if($pos !== false)
            array_splice($this->classes, $pos, 1);
        return this;
    }

    /* --------------------- STATIC CONSTRUCTORS ---------------------- */

    static public function new_Root()
    {
        return new self(0, NodeType::ROOT);
    }

    static public function new_Text($text, $uid)
    {
        return new self($uid, NodeType::TEXT, null, null, $text);
    }

    static public function new_Comment($text, $uid)
    {
        return new self($uid, NodeType::COMMENT, null, null, $text);
    }

    static public function new_TagSelf($tag, $rawAtt, $uid)
    {
        $n = new self($uid, NodeType::TAG, $tag, $rawAtt);
        $n->closing = CloseType::SELF;
        return $n;
    }

    static public function new_TagOpen($tag, $rawAtt, $uid)
    {
        $n = new self($uid, NodeType::TAG, $tag, $rawAtt);
        $n->closing = CloseType::NO;
        return $n;
    }

    static public function new_Tag($tag, $rawAtt, $uid)
    {
        $n = new self($uid, NodeType::TAG, $tag, $rawAtt, '');
        $n->closing = CloseType::YES;
        return $n;
    }

    /* --------------------- GETTER - SETTER ---------------------- */

    public function getUid()
    {
        return $this->uid;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getTag()
    {
        return $this->tag;
    }

    public function setTag($tag)
    {
        if($this->type == NodeType::TAG)
            $this->tag = $tag;
    }

    public function getClosing()
    {
        return $this->closing;
    }

    public function setClosing($closing)
    {
        if($this->type != NodeType::TAG)
            throw new \Exception("SET CLOSING ON NON-TAG NODE!");
        $this->closing = $closing;
    }

    public function getParent()
    {
        return $this->parent;
    }


    /* -------------------- ATTRIBUTES PARSING --------------------- */

    private $regex = [ "#([^\s='\"\/]+)=\"([^\"]*)\"#", "#([^\s='\"\/]+)='([^']*)'#", "#([^\s='\"\/]+)=([^'\"\/\s]*)#", "#([^\s='\"\/]+)#" ];

    private function parseAttributes()
    {
        foreach ($this->regex as $rx)
        {
            preg_match_all($rx, $this->rawAtt, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
            $dele = [];
            foreach ($matches as $match)
            {
                $this->attributes[strtolower($match[1][0])] = isset($match[2]) ? $match[2][0] : null;
                $dele[$match[0][1]] = strlen($match[0][0]);
            }
            krsort($dele);
            foreach($dele as $k => $v)
                $this->rawAtt = substr_replace($this->rawAtt, '', $k, $v);
        }
        $this->rawAtt = trim($this->rawAtt);

        if (isset($this->attributes['id']))
        {
            $this->ids = explode(' ', $this->attributes['id']);
            unset($this->attributes['id']);
        }
        if (isset($this->attributes['class']))
        {
            $this->classes = explode(' ', $this->attributes['class']);
            unset($this->attributes['class']);
        }
    }

    /* ---------------- TREE MANIPULATION FUNCTIONS ---------------- */

    public function find($function, $levels = -1)
    {
        if($this->children)
            return $this->children->find($function, $levels);
    }

    public function select($selector, $levels = -1)
    {
        if($this->children)
            return $this->children->select($selector, $levels);
    }

    public function findParent($function, $levels = -1)
    {
        if($this->parent == null || $levels == 0) {
            return null;
        }

        if($function($this->parent)) {
            return $this->parent;
        }

        return $this->parent->findParent($function, $levels-1);
    }

    public function each($function, $levels = -1)
    {
        if($this->children)
            $this->children->each($function, $levels);
    }

    public function match($tag, array $ids, array $classes)
    {
        if($this->type != NodeType::TAG) return false;
        if($tag == '*') return true;
        if($tag     && $tag != $this->tag) return false;
        if($ids     && array_diff($ids, $this->ids)) return false;
        if($classes && array_diff($classes, $this->classes)) return false;
        return true;
    }


    public function getChildPosition(Node $node)
    {
        $count = count($this->children);
        for ($i=0; $i<$count; $i++)
            if ($this->children[$i]->uid == $node->uid)
                return $i;
        return -1;
    }

    public function append($nodes, $position=null)
    {
        if(is_a($nodes, get_class($this)))
            $nodes = [$nodes];

        if (is_null($position)) $position = count($this->children);
        array_splice($this->children, $position, 0, $nodes);
        foreach ($nodes as $n)
            $n->parent = $this;
    }

    public function replaceWithChildren()
    {
        $parent = $this->parent;
        $parent->children->replaceNodeWith($this, $this->children);
        $this->detach();
        return $this;
    }

    public function replaceWith(Node $node)
    {
        $parent = $this->parent;
        $parent->children->replaceNodeWith($this, $node);
        $this->detach();
        return $this;
    }

    public function detach()
    {
        $this->parent->children->remove($this);
        $this->parent = null;
        return $this;
    }

    public function getChildrenInfo()
    {
        $info = [NodeType::TAG =>  [],
                 NodeType::TEXT =>  ['count' => 0, 'length' => 0],
                 NodeType::COMMENT => ['count' => 0, 'length' => 0]
        ];
        $this->getChildrenInfoRecurs($this, $info);
        return $info;
    }

    private function getChildrenInfoRecurs(Node $node, Array &$info)
    {
        /** @var Node $c */
        foreach ($node->children as $c)
        {
            if ($c->type == NodeType::TEXT)
            {
                $info[NodeType::TEXT]['count']++;
                $info[NodeType::TEXT]['length'] += strlen(trim($c->text));
                continue;
            }

            if ($c->type == NodeType::COMMENT)
            {
                $info[NodeType::COMMENT]['count']++;
                $info[NodeType::COMMENT]['length'] += strlen(trim($c->text));
                continue;
            }

            if ($c->type != NodeType::TAG)
                continue;

            if (!isset($info[NodeType::TAG][$c->tag]))
                $info[NodeType::TAG][$c->tag] = 0;
            $info[NodeType::TAG][$c->tag]++;

            if (count($c->children) > 0)
                $this->getChildrenInfoRecurs($c, $info);
        }
    }

    /* ----------------------- HTML OUTPUT ----------------------- */

    private function getTagAttributesString()
    {
        $atts = [];
        if($this->ids)
            $atts[] = "id=\"". implode(' ', $this->ids) . "\"";

        if($this->classes)
            $atts[] = "class=\"". implode(' ', $this->classes) . "\"";

        foreach ($this->attributes as $key => $value)
        {
            if (is_null($value))
            {
                $atts[] = $key;
            }
            else
            {
                $quote = (strpos($value, "\"") !== false) ? "'" : "\"";
                $atts[] = "{$key}={$quote}{$value}{$quote}";
            }
        }

        if ($this->rawAtt)
            $atts[] = $this->rawAtt;

        return ($atts) ? ' '.implode(' ', $atts) : '';
    }

    public function getHtml()
    {
        $html = [];
        $this->getHtmlRecurs($html);
        return implode('', $html);
    }

    private function getHtmlRecurs(&$html)
    {
        switch ($this->type)
        {
            case NodeType::ROOT:
            {
                foreach ($this->children as $c)
                    $c->getHtmlRecurs($html);
                break;
            }

            case NodeType::TAG:
            {
                $atts = $this->getTagAttributesString();
                switch ($this->closing)
                {
                    case CloseType::YES:
                    {
                        $html[] = "<{$this->tag}{$atts}>";
                        foreach ($this->children as $c)
                            $c->getHtmlRecurs($html);
                        $html[] = "</{$this->tag}>";
                        break;
                    }
                    case CloseType::SELF:
                    {
                        $html[] = "<{$this->tag}{$atts} />";
                        break;
                    }
                    case CloseType::NO:
                    {
                        $html[] = "<{$this->tag}{$atts}>";
                        break;
                    }
                }
                break;
            }
            case NodeType::TEXT:
            case NodeType::COMMENT:
            {
                $html[] = $this->text;
                break;
            }
        }
    }

    /* ------------------- DEBUG FUNCTIONS ------------------- */

    public function printIds()
    {
        return implode(' ',$this->ids);
    }

    public function printClasses()
    {
        return implode(' ',$this->classes);
    }


    public function printDebug($indent, &$result)
    {
        $prefix = str_repeat('    ',$indent);
        switch ($this->type)
        {
            case NodeType::ROOT:
            {
                $result[] = "{$prefix}<{$this->tag}>";
                foreach ($this->children as $c)
                    $c->printDebug($indent+1, $result);
                $result[] = "{$prefix}</{$this->tag}>";
                break;
            }
            case NodeType::TAG:
            {
                switch($this->closing)
                {
                    case CloseType::YES:
                    {
                        $result[] = "{$prefix}<{$this->tag}>";
                        foreach ($this->children as $c)
                            $c->printDebug($indent+1, $result);
                        $result[] = "{$prefix}</{$this->tag}>";
                        break;
                    }
                    case CloseType::SELF:
                    {
                        $result[] = "{$prefix}<{$this->tag}/>";
                        break;
                    }
                    case CloseType::NO:
                    {
                        $result[] = "{$prefix}<{$this->tag}?>";
                        break;
                    }
                }
                break;
            }
            case NodeType::TEXT:
            {
                $result[] = "{$prefix}<TEXT>";
                break;
            }
            case NodeType::COMMENT:
            {
                $result[] = "{$prefix}<COMMENT>";
                break;
            }
        }
    }
}