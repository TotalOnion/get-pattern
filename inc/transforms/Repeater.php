<?php

namespace GetPattern\Transforms;

use GetPattern\DOM\Utils;
use GetPattern\DOM\Clean;
use GetPattern\Transforms\Links;
use GetPattern\Transforms\Images;
use GetPattern\Transforms\Videos;
use GetPattern\Transforms\Fields;

class Repeater
{
    public static function wrapDynamicContainers(string $html, string $name = ''): string
    {
        $dom = Utils::loadDom($html);
        $xpath = new \DOMXPath($dom);

        $containers = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' group-container-v3 ') or contains(concat(' ', normalize-space(@class), ' '), ' sub-group-container-v3 ')][@data-render-dynamic='1' and not(@data-processed)]");

        if ($containers->length === 0) {
            return $html;
        }

        foreach ($containers as $container) {
            $container->setAttribute('data-processed', '1');

            $suffix = self::extractSuffix($container);

            $grid = $xpath->query(".//*[contains(@class, '__grid-container')][1]", $container)->item(0);
            if (!$grid) {
                // Fallback: wrap entire inner
                $inner = '';
                for ($n = $container->firstChild; $n !== null; $n = $n->nextSibling) {
                    $inner .= $dom->saveHTML($n);
                }
                $processed = self::processAsItem($inner, $name);
                while ($container->firstChild) $container->removeChild($container->firstChild);
                self::appendLoopWithChunk($dom, $container, $processed, $suffix);
                continue;
            }

            $firstBlock = $xpath->query(".//*[contains(@class, '__block-container')][1]", $grid)->item(0);
            if (!$firstBlock) {
                continue; // nothing to repeat
            }

            $repeatParent = $firstBlock->parentNode;
            $chunkHtml = '';
            for ($n = $firstBlock; $n !== null; $n = $n->nextSibling) {
                $chunkHtml .= $dom->saveHTML($n);
            }

            $processed = self::processAsItem($chunkHtml, $name);

            $toRemove = [];
            for ($n = $firstBlock; $n !== null; $n = $n->nextSibling) $toRemove[] = $n;
            foreach ($toRemove as $node) {
                if ($node->parentNode) $node->parentNode->removeChild($node);
            }

            self::appendLoopWithChunk($dom, $repeatParent, $processed, $suffix);
        }

        return Utils::saveDom($dom);
    }

    public static function explicitRepeaters(string $html, string $name = ''): string
    {
        $dom = Utils::loadDom($html);
        $xpath = new \DOMXPath($dom);

        $children = $xpath->query('//*[@data-pattern-repeater-child and not(@data-processed)]');
        if ($children->length === 0) {
            return $html;
        }

        $chunkHtml = '';
        foreach ($children as $child) {
            $child->setAttribute('data-processed', '1');

            $tmp = Utils::newDom();
            $tmp->appendChild($tmp->importNode($child, true));
            $repeated = trim($tmp->saveHTML());

            $repeated = self::processAsItem($repeated, $name);
            $chunkHtml .= $repeated;
        }

        $parent = $xpath->query('//*[@data-pattern-repeater-parent]')[0] ?? null;
        if ($parent) {
            $suffix = self::extractSuffix($parent);
            $parent->nodeValue = '';
            self::appendLoopWithChunk($dom, $parent, $chunkHtml, $suffix);
        }

        return $dom->saveHTML();
    }

    public static function processAsItem(string $html, string $name = ''): string
    {
        $out = $html;
        $out = Links::transform($out, 'item');
        $out = Images::transform($out, 'item');
        $out = Videos::transform($out, 'item');
        $out = Fields::replacePostMeta($out, 'item');
        $out = Fields::replaceGenerics($out, 'item');
        $out = Fields::replaceNodeValues($out, 'item', $name ?: '');
        $out = Clean::fixCloseTags($out);
        $out = Clean::fixHtml($out);
        return $out;
    }

    public static function appendLoopWithChunk(\DOMDocument $dom, \DOMNode $parent, string $chunkHtml, string $suffix = ''): void
    {
        $loopStart = $dom->createDocumentFragment();
        $loopStart->appendXML("{% if fields.items{$suffix} %}{% for item in fields.items{$suffix} %}");
        $parent->appendChild($loopStart);

        $chunk = $dom->createDocumentFragment();
        $chunk->appendXML('<![CDATA[' . $chunkHtml . ']]>');
        $parent->appendChild($chunk);

        $loopEnd = $dom->createDocumentFragment();
        $loopEnd->appendXML("{% endfor %}{% endif %}");
        $parent->appendChild($loopEnd);
    }
    private static function extractSuffix(\DOMElement $el): string
    {
        if ($el->hasAttribute('data-render-dynamic-suffix')) {
            $raw = trim((string)$el->getAttribute('data-render-dynamic-suffix'));
            if ($raw !== '') {
                return '_' . $raw;
            }
        }
        return '';
    }
}
