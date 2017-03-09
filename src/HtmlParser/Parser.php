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

    private $trimText = false;

    private $literalTags = ['script', 'style'];
    private $voidTags = [
        'area',
        'base',
        'br',
        'col',
        'command',
        'embed',
        'hr',
        'img',
        'input',
        'keygen',
        'link',
        'meta',
        'param',
        'source',
        'track',
        'wbr'
    ];


    public function __construct($html)
    {
        $this->html = $html;
        $this->reader = new Reader($html);
    }

    public function setTrimText($t)
    {
        $this->trimText = $t;
    }


    public function parse(&$times = [])
    {
        $t0 = microtime(true);
        $rawTokens = $this->splitTokens();
        $t1 = microtime(true);
        $tokens = $this->parseTokens($rawTokens);
        $t2 = microtime(true);
        $rootNode = $this->buildTree($tokens);
        $t3 = microtime(true);

        $times = [
            ($t1 - $t0) * 1000,
            ($t2 - $t1) * 1000,
            ($t3 - $t2) * 1000
        ];

        return $rootNode;
    }

    private function splitTokens()
    {
        $rawTokens = [];

        while (!$this->reader->isEnd()) {

            if ($this->reader->doesMatch("<!--"))        //Comment
            {
                $rawTokens[] = $this->reader->readUntilIncluding("-->");
                continue;
            }

            foreach ($this->literalTags as $tag) {
                if ($this->reader->doesMatch("<{$tag}")) {
                    $rawTokens[] = $this->reader->readUntilIncluding(">");             //open tag
                    $rawTokens[] = $this->reader->readUntilExcluding("</{$tag}>");     //content
                    $rawTokens[] = $this->reader->readUntilIncluding("</{$tag}>");     //closing tag
                    continue 2;
                }
            }

            if ($this->reader->doesMatch("<")) {
                $rawTokens[] = $this->reader->readUntilIncluding(">");
                continue;
            }

            $data = $this->reader->readUntilExcluding("<");
            if ($data) {
                $rawTokens[] = $data;
            }
        }
        return $rawTokens;
    }


    private function parseTokens($rawTokens)
    {
        $count = count($rawTokens);
        $tokens = [];

        for ($i = 0; $i < $count; $i++) {
            $t = $rawTokens[$i];

            if (preg_match('/^<!--.*-->$/is', $t)) {
                $tokens[] = Token::new_Comment($t);
            } elseif (preg_match('/^<!/', $t)) {
                $tokens[] = Token::new_Text($t);
            } elseif (preg_match('/^<.*>$/is', $t)) {
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
            } else {
                $tokens[] = Token::new_Text($t);
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
                    $opening = $current;

                    if ($opening->getTag() != $t['tag']) {
                        $opening = $current->parent($t['tag']) ?: $root;
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
