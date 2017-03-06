<?php

namespace HtmlParser;

use HtmlParser\Elements\CommentNode;
use HtmlParser\Elements\RootNode;
use HtmlParser\Elements\TagNode;
use HtmlParser\Elements\TextNode;
use HtmlParser\CloseType;

class Parser
{
    private $reader;
    private $html;
    private $trimTxt = false;


    public function __construct($html)
    {
        $this->html = $html;
        $this->reader = new Reader($html);
    }

    public function trimText($t)
    {
        $this->trimTxt = $t;
    }


    public function parse()
    {
        $rawTokens = $this->splitTokens();
        $tokens = $this->parseTokens($rawTokens);
        $rootNode = $this->buildTree($tokens);
        return $rootNode;
    }

    private function splitTokens()
    {
        $literalTags = ['script', 'style'];
        $rawTokens = [];

        while (!$this->reader->isEnd()) {
            $data = $this->reader->readUntil("<", Reader::EXCL);

            if ($this->trimTxt) {
                $data = trim($data);
            }

            if ($data) {
                $rawTokens[] = $data;
            }

            if ($this->reader->doesMatch("<!--"))        //Comment
            {
                $data = $this->reader->readUntil("-->", Reader::INCL);
                $rawTokens[] = $data;
                continue;
            }

            foreach ($literalTags as $tag) {
                if (!$this->reader->doesMatch("<{$tag}")) {
                    continue;
                }

                $data = $this->reader->readUntil(">", Reader::INCL);             //open tag
                $rawTokens[] = $data;
                $data = $this->reader->readUntil("</{$tag}>", Reader::EXCL);     // content
                $rawTokens[] = $data;
                $data = $this->reader->readUntil("</{$tag}>", Reader::INCL);     //closing tag
                $rawTokens[] = $data;
                continue 2;
            }

            $data = $this->reader->readUntil(">", Reader::INCL);
            $rawTokens[] = $data;
        }
        return $rawTokens;
    }

    private function parseTokens($rawTokens)
    {
        $count = count($rawTokens);
        $tokens = [];

        for ($i = 0; $i < $count; $i++) {
            $t = $rawTokens[$i];

            if (preg_match('/^<!--.*-->$/is', $t))                      //Comment
            {
                $tokens[] = Token::new_Comment($t);
            } else {
                if (preg_match('/^<!/', $t))                           //Doctype
                {
                    $tokens[] = Token::new_Text($t);
                } else {
                    if (preg_match('/^<.*>$/is', $t))   //Tag
                    {
                        if ($t[1] == "/") {
                            $type = TokenType::TAG_CLOSE;
                        } else {
                            if ($t[strlen($t) - 2] == "/") {
                                $type = TokenType::TAG_SELF;
                            } else {
                                $type = TokenType::TAG_OPEN;
                            }
                        }

                        preg_match('#</?(.*?)(?:\s(.*?))?/?>#is', $t, $match);
                        $tag = $match[1];
                        $rawAtt = isset($match[2]) ? $match[2] : "";
                        $tokens[] = Token::new_Tag($type, $tag, $rawAtt);
                    } else        //Text
                    {
                        $tokens[] = Token::new_Text($t);
                    }
                }
            }
        }
        return $tokens;
    }

    private function buildTree($tokens)
    {
        /** @var Node $current */
        $root = new RootNode();
        $current = $root;

        foreach ($tokens as $t) {
            switch ($t['type']) {
                case TokenType::TEXT:
                    $current->addChild(new TextNode($t['text']));
                    break;

                case TokenType::COMMENT:
                    $current->addChild(new CommentNode($t['text']));
                    break;

                case TokenType::TAG_SELF:
                    $current->addChild(new TagNode($t['tag'], $t['rawAtt'], ClosingType::SELF));
                    break;

                case TokenType::TAG_OPEN:
                    $n = new TagNode($t['tag'], $t['rawAtt'], ClosingType::NO);
                    $current->addChild($n);
                    $current = $n;
                    break;

                case TokenType::TAG_CLOSE:
                    $tag = $t['tag'];

                    $opening = $current;
                    if ($opening->getTag() != $tag) {
                        $opening = $current->parent($tag);
                    }
                    if (!$opening) {
                        $opening = $root;
                    }

                    $openChildren = $this->getOpenChildren($opening);

                    $opening->addChildren($openChildren);
                    $opening->setClosing(ClosingType::YES);
                    $current = $opening->parent();

                    if ($opening instanceof RootNode) {
                        $this->closeNodes($opening);
                    }
                    break;

            }
        }
        $this->closeNodes($root);
        return $root;
    }

    private function getOpenChildren(TagNode $node)
    {
        $result = [];

        $children = $node->detachChildren();
        foreach ($children as $c) {
            $result[] = $c;

            if ($c instanceof TagNode && $c->getClosing() == ClosingType::NO) {
                $result = array_merge($result, $this->getOpenChildren($c));
            }
        }
        return $result;
    }

    private function closeNodes(TagNode $node)
    {
        /** @var Node $c */
        foreach ($node as $c) {
            if ($c instanceof TagNode && $c->getClosing() == ClosingType::NO) {
                $c->setClosing(ClosingType::YES);
                $this->closeNodes($c);
            }
        }
    }
}
