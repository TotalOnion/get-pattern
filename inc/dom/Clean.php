<?php

namespace GetPattern\DOM;

use DOMDocument;
use DOMXPath;

class Clean
{
    public static function removeBlankLines(string $html): string
    {
        return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $html);
    }

    public static function prettify(string $html): string
    {
        return Utils::saveDom(Utils::loadDom($html));
    }

    public static function removeLoadedClass(string $html): string
    {
        return str_replace('loaded', '', $html);
    }

    public static function replaceBlockId(string $html): string
    {
        return preg_replace("/\bblock_[a-zA-Z0-9]*/", '{{block.id}}', $html);
    }

    public static function addBlockClass(string $html): string
    {
        $dom = Utils::loadDom($html);
        $xpath = new \DOMXpath($dom);
        $section = $xpath->query('//section[1]')[0];
        if ($section) {
            $section->setAttribute('class', $section->getAttribute('class') . ' {{ block.className }}');
        }
        return Utils::saveDom($dom);
    }

    public static function fixHtml(string $html): string
    {
        $html = Utils::saveDom(Utils::loadDom($html));


        // Replace entities
        $html = str_replace(
            array('%20', '%7B', '%7D', '%5B', '%5D'),
            array(' ', '{', '}', '[', ']'),
            $html
        );

        // Had to keep these wrappers in, so removing here
        $html = str_replace(
            array('<body>', '</body>', '<head>', '</head>', '<html>', '</html>', '<inserttwig>', '</inserttwig>', 'http:', '</source>'),
            array('', '', '', '', '', '', '', '', 'https:', ''),
            $html
        );

        // Doctype be gone!
        $html = preg_replace(
            '/<!DOCTYPE\s+html[\s>](.*?)>/',
            '',
            $html
        );

        return $html;
    }

    public static function fixCloseTags(string $html): string
    {
        $voidTags = ['area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

        // Close tags like <img></img> → <img />
        $html = preg_replace(
            '#<(' . implode('|', $voidTags) . ')([^>]*)>\s*</\1>#i',
            '<$1$2 />',
            $html
        );

        // Ensure single <img> and others are self-closed if not already
        $html = preg_replace_callback(
            '#<(' . implode('|', $voidTags) . ')([^>/]*)(?<!/)>#i',
            function ($matches) {
                return '<' . $matches[1] . $matches[2] . ' />';
            },
            $html
        );

        return $html;
    }
}
