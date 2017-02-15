<?php

namespace HtmlParser;

class Uid
{
    private $state;

    public function __construct()
    {
        $this->state = 0;
    }

    public function getNewId()
    {
        return (++$this->state);
    }

}