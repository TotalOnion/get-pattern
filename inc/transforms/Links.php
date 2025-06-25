<?php

namespace GetPattern\Transforms;

use GetPattern\DOM\Utils;

class Links
{
    public static function transform(string $html, string $type = 'fields'): string
    {
        $dom = Utils::loadDom($html);
        $xpath = new \DOMXpath($dom);
        $ctas = $xpath->query('//*[@data-pattern-cta-selection]');

        if ($ctas->length > 0) {
            foreach ($ctas as $cta) {
                $pattern_replaced = $cta->getAttribute('data-pattern-replaced');

                if ($pattern_replaced) {
                    return $html;
                } else {
                    $cta->setAttribute('data-pattern-replaced', 1);
                }

                $fragStart = $dom->createDocumentFragment();
                $fragStart->appendXML("{% if ( " . $type . ".link" . " or " . $type . ".cta ) %}");
                $cta->parentNode->insertBefore($fragStart, $cta);

                $anchors = $xpath->query('a', $cta);

                $tmpDom = new \DOMDocument();
                $tmpDom->appendChild($tmpDom->importNode($anchors[0], true));
                $anchor = trim($tmpDom->saveHTML());

                preg_match_all('/%%(.*?)%%/', $anchor, $matches);

                foreach ($matches[1] as $match) {
                    if (str_contains($match, '~')) {
                        [$field, $key] = explode('~', $match);

                        $anchor = str_replace(
                            ["http://", "https://", "%%{$match}%%"],
                            ["", "", "{{ {$type}.{$field}['{$key}'] }}"],
                            $anchor
                        );

                        $anchor = preg_replace(
                            '/\btarget="[^"]*"/',
                            "target=\"{{ {$type}.{$field}['target'] }}\"",
                            $anchor
                        );
                    }
                }

                // Replace anchor node content
                $newNode = $dom->createDocumentFragment();
                $newNode->appendXML($anchor);
                $cta->nodeValue = '';
                $cta->appendChild($newNode);

                // Close Twig condition
                $fragEnd = $dom->createDocumentFragment();
                $fragEnd->appendXML("{% endif %}");
                $cta->parentNode->appendChild($fragEnd);
            }
        }

        return Utils::saveDom($dom);
    }
}
