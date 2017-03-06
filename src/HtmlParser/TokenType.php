<?php

namespace HtmlParser;

class TokenType
{
    const TEXT = 'TEXT';
    const COMMENT = 'COMMENT';
    const TAG_OPEN = 'TAG_OPEN';
    const TAG_SELF = 'TAG_SELF';
    const TAG_CLOSE = 'TAG_CLOSE';
}