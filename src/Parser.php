<?php

namespace HtmlParser;

use HtmlParser\Reader;
use HtmlParser\Token;
use HtmlParser\TokenType;
use HtmlParser\CloseType;

class Parser
{
    private $reader;
    private $html;
    private $uid;
    private $debugPath;
    private $trimTxt = false;

    public function __construct($html, Uid $uid, $debugPath = false)
    {
        $this->html = $html;
        $this->uid = $uid;
        $this->reader = new Reader($html);
        $this->debugPath = $debugPath;
    }

    public function trimText($t)
    {
        $this->trimTxt = $t;
    }


    public function parse()
    {
        $stop1 = microtime(true);

        $rawTokens = $this->splitTokens();
        if ($this->debugPath)
            $this->debugSplittedTokens($rawTokens);
        $stop2 = microtime(true);

        $tokens = $this->parseTokens($rawTokens);
        if ($this->debugPath)
            $this->debugTokens($tokens);
        $stop3 = microtime(true);

        $rootNode = $this->buildTree($tokens);
        if ($this->debugPath)
            $this->debugTree($rootNode);
        $stop4 = microtime(true);

        echo "RAW:  " . ($stop2-$stop1) *1000 . "<br>";
        echo "TOK:  " . ($stop3-$stop2) *1000 . "<br>";
        echo "TREE: " . ($stop4-$stop3) *1000 . "<br>";

        return $rootNode;

    }

    private function splitTokens()
    {
        $literalTags = ['script', 'style'];
        $rawTokens = [];

        while (!$this->reader->isEnd())
        {
            $data = $this->reader->readUntil("<", Reader::EXCL);

            if($this->trimTxt)
                $data = trim($data);

            if($data)
                $rawTokens[] = $data;

            if ($this->reader->doesMatch("<!--"))        //Comment
            {
                $data = $this->reader->readUntil("-->", Reader::INCL);
                $rawTokens[] = $data;
                continue;
            }

            foreach($literalTags as $tag)
            {
                if (!$this->reader->doesMatch("<{$tag}"))
                    continue;

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

        for ($i=0; $i<$count; $i++)
        {
            $t = $rawTokens[$i];

            if (preg_match('/^<!--.*-->$/is', $t))                      //Comment
            {
                $tokens[] = Token::new_Comment($t);
            }
            else if (preg_match('/^<!/', $t))                           //Doctype
            {
                $tokens[] = Token::new_Text($t);
            }
            else if (preg_match('/^<.*>$/is', $t))   //Tag
            {
                if($t[1] == "/")
                    $type = TokenType::TAG_CLOSE;
                else if ($t[strlen($t)-2] == "/")
                    $type = TokenType::TAG_SELF;
                else
                    $type = TokenType::TAG_OPEN;

                preg_match('#</?(.*?)(?:\s(.*?))?/?>#is', $t, $match);
                $tag = $match[1];
                $rawAtt = isset($match[2]) ? $match[2] : "";
                $tokens[] = Token::new_Tag($type, $tag, $rawAtt);
            }
            else        //Text
            {
                $tokens[] = Token::new_Text($t);
            }
        }
        return $tokens;
    }

    private function buildTree($tokens)
    {
        /** @var Node $current */
        $root = Node::new_Root();
        $current = $root;

        foreach ($tokens as $t)
        {
            switch ($t['type'])
            {
                case TokenType::TEXT:
                {
                    $uid = $this->uid->getNewId();
                    $current->addChild(Node::new_Text($t['text'], $uid));
                    break;
                }
                case TokenType::COMMENT:
                {
                    $uid = $this->uid->getNewId();
                    $current->addChild(Node::new_Comment($t['text'], $uid));
                    break;
                }
                case TokenType::TAG_SELF:
                {
                    $uid = $this->uid->getNewId();
                    $current->addChild(Node::new_TagSelf($t['tag'], $t['rawAtt'], $uid));
                    break;
                }
                case TokenType::TAG_OPEN:
                {
                    $uid = $this->uid->getNewId();
                    $n = Node::new_TagOpen($t['tag'], $t['rawAtt'], $uid);
                    $current->addChild($n);
                    $current = $n;
                    break;
                }
                case TokenType::TAG_CLOSE:
                {
                    $tag = $t['tag'];

                    $opening = $current;
                    if($opening->getTag() != $tag) {
                        $opening = $current->findParent(function ($n) use ($tag) {
                            return $n->getTag() == $tag || $n->getType() == NodeType::ROOT;
                        });
                    }

                    $openChildren = $this->getOpenChildren($opening);

                    $opening->clearChildren();
                    $opening->addChildren($openChildren);


                    if ($opening->getType() == NodeType::ROOT)
                    {
                        $this->closeNodes($opening);
                        $current = $opening;
                    }
                    else
                    {
                        $opening->setClosing(CloseType::YES);
                        $current = $opening->getParent();
                    }
                    break;
                }
            }
        }
        $this->closeNodes($root);
        return $root;
    }

    private function getOpenChildren(Node $node)
    {
        $result = new NodeCollection();

        /** @var Node $n */
        foreach ($node->getChildren() as $n)
        {
            $result->add($n);

            if ($n->getType() == NodeType::TAG && $n->getClosing() == CloseType::NO)
            {
                $result->addRange($this->getOpenChildren($n));
                $n->clearChildren();
            }
        }
        return $result;
    }

    private function closeNodes(Node $n)
    {
        /** @var Node $c */
        foreach ($n->getChildren() as $c)
        {
            if ($c->getType() == NodeType::TAG && $c->getClosing() == CloseType::NO)
            {
                $c->setClosing(CloseType::YES);
                $this->closeNodes($c);
            }
        }
    }

    //------------------ DEBUG --------------------

    private function debugSplittedTokens($rawTokens)
    {
        file_put_contents($this-> debugPath . '1_rawTokens.txt', print_r($rawTokens, true));
    }
    private function debugTokens($Tokens)
    {
        file_put_contents($this-> debugPath . '2_Tokens.txt', print_r($Tokens, true));
    }
    public function debugTree($root)
    {
        $data = [];
        $root->printDebug(0, $data);
        file_put_contents($this-> debugPath . '3_Tree.txt', implode("\r\n",$data));
    }

}
