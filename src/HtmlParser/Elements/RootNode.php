<?php

namespace HtmlParser\Elements;

class RootNode extends TagNode
{
    public function __construct()
    {
        parent::__construct('');
        $this->parent = $this;
    }
}