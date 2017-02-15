<?php

namespace HtmlParser;

class Reader
{
    private $html;
    private $length;
    private $position;

    const INCL = true;
    const EXCL = false;

    public function __construct($html)
    {
        $this->html = $html;
        $this->length = strlen($html);
        $this->position = 0;
    }

    public function readUntil($key, $including)
    {
        $end = strpos($this->html, $key, $this->position);

        $end = ($end === false) ? $this->length       :
                (($including)     ? $end + strlen($key) : $end);

        $ret =  substr($this->html, $this->position, ($end-$this->position));
        $this->position = $end;
        return $ret;
    }

    public function doesMatch($key)
    {
        $l = strlen($key);
        if($this->length - $this->position < $l)
            return false;

        for($i = 0; $i < $l; $i++)
            if ($this->html[$this->position + $i] != $key[$i])
                return false;

        return true;
    }

    public function isEnd()
    {
        return ($this->position >= $this->length);
    }
}