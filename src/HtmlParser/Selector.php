<?php
/**
 * Created by PhpStorm.
 * User: fedexx2
 * Date: 2/26/17
 * Time: 9:32 PM
 */

namespace HtmlParser;


use HtmlParser\Elements\AbstractNode;
use HtmlParser\Elements\CommentNode;
use HtmlParser\Elements\TagNode;
use HtmlParser\Elements\TextNode;

class Selector
{
    protected $tag;
    protected $classes = [];
    protected $ids = [];
    protected $depth;

    /** @var Selector $next */
    protected $next;


    public function __construct($tag, array $classes = [], array $ids = [], $depth = -1, Selector $next = null)
    {
        $this->tag = $tag;
        $this->classes = $classes;
        $this->ids = $ids;
        $this->depth = $depth;
        $this->next = $next;
    }

    public static function fromCss($code)
    {
        $code = strtolower($code);
        $code = preg_replace('/\s*>\s/', '>', $code);
        $code = preg_replace('/\s+/', ' ', $code);

        preg_match_all('/([\s>]?[^\s>]+)/', $code, $matches);

        $items = array_reverse($matches[0]);

        $selector = null;

        foreach ($items as $i) {

            $tag = null;
            $ids = [];
            $classes = [];

            $depth = ($i[0] == '>') ? 1 : -1;
            $i = trim($i, ' >');

            preg_match_all('/[\.#@]?([^\.#@]+)/', $i, $pieces, PREG_SET_ORDER);

            if (!$pieces) {
                throw new \Exception('Error parsing selector code');
            }

            foreach ($pieces as $p) {
                if ($p[0][0] == '.') {
                    $classes[] = $p[1];
                } elseif ($p[0][0] == '#') {
                    $ids[] = $p[1];
                } else {
                    $tag = $p[1];
                }
            }

            $selector = new Selector($tag, $classes, $ids, $depth, $selector);
        }
        return $selector;
    }

    public static function create($selector)
    {
        if(is_callable($selector)) {
            return [$selector, $selector];
        }

        if (is_string($selector)) {
            $selector = self::fromCss($selector);
        }

        if($selector instanceof Selector) {
            return [$selector, [$selector, 'match']];
        }
        throw new \Exception('Invalid Selector');
    }

    public function match(AbstractNode $node)
    {
        if ($this->tag == '**') {
            return true;
        }
        if ($this->tag == '*' && $node instanceof TagNode) {
            return true;
        }
        if ($this->tag == '$' && $node instanceof TextNode) {
            return true;
        }
        if ($this->tag == '%' && $node instanceof CommentNode) {
            return true;
        }
        if (!$node instanceof TagNode) {
            return false;
        }
        if ($this->tag && $this->tag != $node->getTag()) {
            return false;
        }
        if (!empty($this->ids) && !$node->hasId($this->ids)) {
            return false;
        }
        if (!empty($this->classes) && !$node->hasClass($this->classes)) {
            return false;
        }
        return true;
    }

    public function hasNext()
    {
        return !is_null($this->next);
    }

    public function getNext()
    {
        return $this->next;
    }

    public function getDepth()
    {
        return $this->depth;
    }


}