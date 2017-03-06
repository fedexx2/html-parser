<?php

namespace HtmlParser;

use HtmlParser\TokenType;


class Token
{

    public static function new_Text($text)
    {
        return [
            'type' => TokenType::TEXT,
            'text' => $text,
            'tag' => '',
            'rawAtt' => ''
        ];
    }

    public static function new_Comment($text)
    {
        return [
            'type' => TokenType::COMMENT,
            'text' => $text,
            'tag' => '',
            'rawAtt' => ''
        ];
    }

    public static function new_Tag($type, $tag, $rawAtt = '')
    {
        return [
            'type' => $type,
            'tag' => $tag,
            'rawAtt' => $rawAtt,
            'text' => '',
        ];
    }
}