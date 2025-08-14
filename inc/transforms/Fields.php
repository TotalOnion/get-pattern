<?php

namespace GetPattern\Transforms;

use GetPattern\DOM\Utils;
use GetPattern\DOM\Clean;

class Fields
{
    public static function transform(string $html, string $type = 'fields', string $name = ''): string
    {
        $html = Repeater::wrapDynamicContainers($html, $name);
        $html = Repeater::explicitRepeaters($html, $name);

        $html = self::replacePostMeta($html, $type);
        $html = self::replaceGenerics($html, $type);
        $html = self::replaceNodeValues($html, $type, $name);
        $html = self::wrapGradientOverlay($html);
        $html = self::formReplace($html);
        return $html;
    }
    // Generic fields
    public static function replaceGenerics(string $html, string $type): string
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
    public static function replacePostMeta(string $html, string $type): string
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
    public static function replaceNodeValues(string $html, string $type, string $name): string
    {
        $dom = Utils::loadDom($html);
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//*[@data-pattern-post-info]');
        if ($nodes->length === 0) return $html;

        $sectionTypeNode = $xpath->query('//section[@data-post-info-type][1]')->item(0);
        $postInfoKind    = $sectionTypeNode ? $sectionTypeNode->getAttribute('data-post-info-type') : 'post-info';
        $isTaxonomy      = ($postInfoKind === 'post-taxonomy');
        $getter          = $isTaxonomy ? 'get_term' : 'get_post';

        $postInfo = 'postInfoItem';
        $postInfoType = ($type === 'fields')
            ? ($name === 'current-post-info' ? 'current_post' : 'fields.post')
            : 'item.post';

        $imageSelectNode = $xpath->query('//*[@data-image-select]')->item(0);
        $imageSelect = $imageSelectNode ? $imageSelectNode->getAttribute('data-image-select') : 'image';
        $imageKey = "post_" . $imageSelect;

        foreach ($nodes as $i => $node) {
            if ($node->getAttribute('data-post-info-processed') === '1') continue;
            $node->setAttribute('data-post-info-processed', '1');

            $elem = $node->getAttribute('data-pattern-post-info');

            if ($i === 0) {
                $frag = $dom->createDocumentFragment();
                $frag->appendXML("<inserttwig>{% set {$postInfo} = {$getter}({$postInfoType}) %}</inserttwig>");
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
            } elseif ($elem === 'post_video') {
                $videoSelect = $node->getAttribute('data-video-select');
                $frag = $dom->createDocumentFragment();
                $frag->appendXML("<inserttwig>{% set videoSelect = '$videoSelect' %}
                    {% set videoDesktop = attribute({$postInfo}, videoSelect) %}
                    {% set mainVideoSrc = gt_video_mainsrc(videoDesktop['url']) %}</inserttwig>");
                $videos = $node->getElementsByTagName('video');
                if ($videos->length > 0) {
                    $video = $videos->item(0);
                    $video->parentNode->insertBefore($frag, $video);
                    $sources = $video->getElementsByTagName('source');
                    foreach ($sources as $source) {
                        $source->setAttribute('src', '{{ mainVideoSrc }}');
                    }
                }
            } else {
                $node->nodeValue = "{{ {$postInfo}.{$elem} }}";
                $parent = $node->parentNode;
                if ($parent && $parent->nodeType === XML_ELEMENT_NODE) {
                    $parentClass = ' ' . ($parent->getAttribute('class') ?? '') . ' ';
                    if (strpos($parentClass, ' post-info-v3__content-container ') !== false) {
                        $toRemove = [];
                        for ($child = $parent->firstChild; $child !== null; $child = $child->nextSibling) {
                            if ($child !== $node) {
                                $toRemove[] = $child;
                            }
                        }
                        foreach ($toRemove as $rm) {
                            if ($rm->parentNode) {
                                $rm->parentNode->removeChild($rm);
                            }
                        }
                    }
                }
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
    // Form Selection
    private static function formReplace(string $html): string
    {
        $dom = Utils::loadDom($html);
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//*[@data-pattern-form-selection]');

        foreach ($nodes as $node) {
            $formId = $node->getAttribute('data-formid');
            $renderDynamic = $node->getAttribute('data-renderdynamic');

            [$startNode, $endNode] = Utils::findComments($node, 'inline-form');

            if (!$startNode || !$endNode) {
                continue;
            }

            $toRemove = [];
            $current = $startNode;
            while ($current !== null) {
                $next = $current->nextSibling;
                $toRemove[] = $current;
                if ($current === $endNode) {
                    break;
                }
                $current = $next;
            }

            if ($endNode->parentNode) {
                if ($renderDynamic === '1') {
                    $shortcode = $dom->createTextNode("{{ function('do_shortcode', '[cdbform id=' ~ fields.form ~ ']') }}");
                } else {
                    $shortcode = $dom->createTextNode("{{ function('do_shortcode', '[cdbform id=" . $formId . "]') }}");
                }
                $endNode->parentNode->insertBefore($shortcode, $endNode);
            }

            foreach ($toRemove as $removalNode) {
                if ($removalNode->parentNode) {
                    $removalNode->parentNode->removeChild($removalNode);
                }
            }
        }

        return Utils::saveDom($dom);
    }
}
