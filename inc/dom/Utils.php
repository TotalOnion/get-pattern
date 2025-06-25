<?php

namespace GetPattern\DOM;

class Utils
{
    public static function loadDom(string $html): \DOMDocument
    {
        $dom = new \DOMDocument();
        $dom->loadHTML($html);

        return $dom;
    }
    public static function saveDom(\DOMDocument $dom): string
    {
        return $dom->saveHTML();
    }
    public static function newDom(): \DOMDocument
    {
        $dom = new \DOMDocument();
        $dom->formatOutput = true;
        return $dom;
    }
}
