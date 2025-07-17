<?php

namespace GetPattern\Transforms;

use GetPattern\DOM\Utils;
use GetPattern\DOM\Clean;

class Fields
{
    public static function transform(string $html, string $type = 'fields', string $name = ''): string
    {
        $html = self::repeaters($html);
        $html = self::replacePostMeta($html, $type);
        $html = self::replaceGenerics($html, $type);
        $html = self::replaceNodeValues($html, $type, $name);
        $html = self::wrapGradientOverlay($html);
        return $html;
    }
    // Repeaters
    private static function repeaters(string $html): string
    {
        $dom = Utils::loadDom($html);
        $xpath = new \DOMXPath($dom);
        // $nodes = $xpath->query('//*[@data-pattern-repeater-child]');
        $nodes = $xpath->query('//*[@data-pattern-repeater-child and not(@data-processed)]');

        if ($nodes->length === 0) {
            return $html;
        }

        $chunk_html = '';
        foreach ($nodes as $node) {
            $node->setAttribute('data-processed', '1');

            $tmp_dom = Utils::newDom();
            $tmp_dom->appendChild($tmp_dom->importNode($node, true));

            $repeated = trim($tmp_dom->saveHTML());

            $repeated = Images::transform($repeated, 'item');
            $repeated = Videos::transform($repeated, 'item');

            $repeated = self::replacePostMeta($repeated, 'item');
            $repeated = self::replaceGenerics($repeated, 'item');
            $repeated = self::replaceNodeValues($repeated, 'item', $name ?? '');

            $repeated = Clean::fixCloseTags($repeated);
            $repeated = Clean::fixHtml($repeated);

            $chunk_html .= $repeated;
        }


        $parent = $xpath->query('//*[@data-pattern-repeater-parent]')[0];
        $parent->nodeValue = ''; // clear

        $loop_start = $dom->createDocumentFragment();
        $loop_start->appendXML("{% if fields.items %}\n{% for item in fields.items %}");
        $parent->appendChild($loop_start);

        $chunk = $dom->createDocumentFragment();
        $chunk->appendXML('<![CDATA[' . $chunk_html . ']]>');
        $parent->appendChild($chunk);

        $loop_end = $dom->createDocumentFragment();
        $loop_end->appendXML("{% endfor %}\n{% endif %}");
        $parent->appendChild($loop_end);

        $html = $dom->saveHTML();

        return $html;
    }
    // Generic fields
    private static function replaceGenerics(string $html, string $type): string
    {
        preg_match_all('/%%(.*?)%%/', $html, $matches);

        foreach ($matches[1] as $match) {
            if (!str_contains($match, '|')) {
                $html = str_replace(
                    '%%' . $match . '%%',
                    '{{ ' . $type . '.' . $match . ' }}',
                    $html
                );
            }
        }

        return $html;
    }
    // Post meta
    private static function replacePostMeta(string $html, string $type): string
    {
        preg_match_all('/%%(.*?)%%/', $html, $matches);

        foreach ($matches[1] as $match) {
            if (str_contains($match, '|')) {
                [$field, $subfield] = explode('|', $match);
                $html = str_replace(
                    '%%' . $match . '%%',
                    '{{ get_post(' . $type . '.' . $field . ').' . $subfield . ' }}',
                    $html
                );
            }
        }

        return $html;
    }
    // Post info
    private static function replaceNodeValues(string $html, string $type, string $name): string
    {
        $dom = Utils::loadDom($html);
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//*[@data-pattern-post-info]');

        if ($nodes->length === 0) {
            return $html;
        }

        $postInfo = 'postInfoItem';
        $postInfoType = 'item.post';

        if ($type === 'fields') {
            if ($name === 'current-post-info') {
                $postInfoType = 'current_post';
            } else {
                $postInfoType = 'fields.post';
            }
        }
        $imageSelectNode = $xpath->query('//*[@data-image-select]')->item(0);
        $imageSelect = $imageSelectNode ? $imageSelectNode->getAttribute('data-image-select') : 'image';
        $imageKey = "post_" . $imageSelect;

        foreach ($nodes as $key => $node) {
            $elem = $node->getAttribute('data-pattern-post-info');

            if ($key === 0) {
                $frag = $dom->createDocumentFragment();
                $frag->appendXML("<inserttwig>{% set {$postInfo} = get_post({$postInfoType}) %}</inserttwig>");

                $section = $xpath->query('//section[1]')->item(0);
                if ($section && $section->parentNode) {
                    $section->parentNode->insertBefore($frag, $section);
                }
            }

            if ($elem === 'link') {
                if (!$node->getAttribute('data-pattern-replaced')) {
                    $node->setAttribute('data-pattern-replaced', 1);
                    $node->setAttribute('href', "{{ {$postInfo}.link }}");
                    $node->setAttribute('aria-label', "{{ {$postInfo}.title }}");
                }
            } elseif ($elem === 'post_image' || $elem === 'product_icon') {
                $frag = $dom->createDocumentFragment();
                $frag->appendXML("<inserttwig>{% set image = get_image({$postInfo}.{$imageKey}) %}
                {% set isSVG = check_file_type(image.id) == 'image/svg+xml' %}
                {% set mainImageSrc = gt_image_mainsrc(image) %}
                {% set srcset = isSVG ? '' : gt_image_srcset(image) %}</inserttwig>");

                if ($node->nodeName === 'img' && $node->hasAttribute('srcset')) {
                    $img = $node;
                    $img->parentNode->insertBefore($frag, $img);
                    $img->setAttribute('srcset', '{{ srcset }}');
                    $img->setAttribute('src', '{{ mainImageSrc }}');
                    $img->setAttribute('title', '{{ image.title }}');
                    $img->setAttribute('alt', '{{ image.alt }}');
                }
            } else {
                $node->nodeValue = "{{ {$postInfo}.{$elem} }}";
            }
        }
        return Utils::saveDom($dom);
    }
    // Gradient Overlay
    private static function wrapGradientOverlay(string $html): string
    {
        $dom = Utils::loadDom($html);
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//*[@data-pattern-gradient-overlay]');

        foreach ($nodes as $node) {
            if ($node->getAttribute('data-renderdynamic') !== '1') {
                continue;
            }
            
            $container = $node->parentNode;

            $start = $dom->createDocumentFragment();
            $start->appendXML('<inserttwig>{% if fields.enable_gradient_layer %}</inserttwig>');

            $end = $dom->createDocumentFragment();
            $end->appendXML('<inserttwig>{% endif %}</inserttwig>');

            $container->parentNode->insertBefore($start, $container);
            if ($container->nextSibling) {
                $container->parentNode->insertBefore($end, $container->nextSibling);
            } else {
                $container->parentNode->appendChild($end);
            }
        }

        return Utils::saveDom($dom);
    }
}
