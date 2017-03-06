<?php

namespace HtmlParser\Elements;

class TextNode extends AbstractNode
{
    protected $text;

    public function __construct($text)
    {
        $this->text = $text;
        parent::__construct();
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    /* ------------------------- TEXT - HTML -------------------------- */

    public function getHtml()
    {
        return $this->text;
    }

    public function getText()
    {
        return $this->text;
    }

    public function getInfo(Array $info = [])
    {
        if (!isset($info[self::class])) {
            $info[self::class] = ['@count' => 0, '@length' => 0, '@trimmed' => 0];
        }
        $info[self::class]['@count']++;
        $info[self::class]['@length'] += strlen($this->text);
        $info[self::class]['@trimmed'] += strlen(trim($this->text));
        return $info;
    }
}