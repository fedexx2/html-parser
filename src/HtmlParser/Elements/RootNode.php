<?php

namespace HtmlParser\Elements;

class RootNode extends TagNode
{
    public function __construct()
    {
        parent::__construct('');
        $this->parent = $this;
    }

    public function getHtml()
    {
        return ChildrenNode::getHtml();
    }

    public function getInfo(array $info = [])
    {
        return ChildrenNode::getInfo();
    }

}