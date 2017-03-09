<?php

namespace HtmlParser\Elements;


class CommentNode extends AbstractNode
{
    protected $comment;

    public function __construct($comment)
    {
        $this->comment = $comment;
        parent::__construct();
    }

    public function setText($text)
    {
        $this->comment = $text;
    }

    /* ------------------------- TEXT - HTML -------------------------- */

    public function getHtml()
    {
        return $this->comment;
    }

    public function getText()
    {
        return $this->comment;
    }

    public function getInfo(Array $info = [])
    {
        $info = parent::getInfo($info);
        $info[self::class]['@count']++;
        $info[self::class]['@length'] += strlen($this->comment);
        return $info;
    }


}