<?php

namespace HtmlParser;

class Reader
{
    private $html;
    private $length;
    private $position;

    public function __construct($html)
    {
        $this->html = $html;
        $this->length = strlen($html);
        $this->position = 0;
    }

    public function readUntilIncluding($key)
    {
        return $this->readUntil($key, true);
    }

    public function readUntilExcluding($key)
    {
        return $this->readUntil($key, false);
    }

    public function readUntil($key, $including)
    {
        $end = strpos($this->html, $key, $this->position);

        if ($end === false) {
            $end = $this->length;
        } elseif ($including) {
            $end += strlen($key);
        }

        $ret = substr($this->html, $this->position, ($end - $this->position));
        $this->position = $end;
        return $ret;
    }

    public function doesMatch($key)
    {
        $l = strlen($key);
        if ($this->length - $this->position < $l) {
            return false;
        }

        return (substr($this->html, $this->position, $l) == $key);
    }

    public function isEnd()
    {
        return ($this->position >= $this->length);
    }
}