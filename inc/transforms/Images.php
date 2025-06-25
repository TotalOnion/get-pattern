<?php

namespace GetPattern\Transforms;

use GetPattern\DOM\Utils;

class Images
{
    public static function transform(string $html, string $type = 'fields'): string
    {
        $dom = Utils::loadDom($html);
        $xpath = new \DOMXpath($dom);

        // Handle <picture> tags
        foreach ($dom->getElementsByTagName('picture') as $picture) {
            $isDynamic = $picture->getAttribute('data-pattern-dynamic') === '1';
            $alreadyReplaced = $picture->getAttribute('data-pattern-replaced');

            if ($alreadyReplaced) {
                continue;
            }
            $picture->setAttribute('data-pattern-replaced', 1);

            if ($isDynamic) {
                $suffix = $picture->getAttribute('data-pattern-suffix') ? '_' . $picture->getAttribute('data-pattern-suffix') : '';

                $twig = "{% set imageDesktop = get_image({$type}.image_desktop{$suffix}) %}
                        {% set imageTablet = get_image({$type}.image_tablet{$suffix}|default({$type}.image_desktop{$suffix})) %}
                        {% set imageMobile = get_image({$type}.image_mobile{$suffix}|default({$type}.image_desktop{$suffix})) %}
                        {% set isSVG = imageDesktop.post_mime_type == 'image/svg+xml' %}
                        {% set mainImageSrc = isSVG ? imageDesktop.src : gt_image_mainsrc(imageDesktop) %}
                        {% set desktopSrcset = isSVG ? imageDesktop.src : gt_image_srcset(imageDesktop) %}
                        {% set tabletSrcset = isSVG ? imageTablet.src : gt_image_srcset(imageTablet) %}
                        {% set mobileSrcset = isSVG ? imageMobile.src : gt_image_srcset(imageMobile) %}";

                $frag = $dom->createDocumentFragment();
                $frag->appendXML($twig);
                $firstSource = $picture->getElementsByTagName('source')->item(0);
                if ($firstSource) {
                    $picture->insertBefore($frag, $firstSource);
                }

                foreach ($picture->getElementsByTagName('source') as $source) {
                    switch ($source->getAttribute('data-type')) {
                        case 'srcSetDesktop':
                            $source->setAttribute('srcset', '{{desktopSrcset}}');
                            $source->setAttribute('width', '{{imageDesktop.width}}');
                            $source->setAttribute('height', '{{imageDesktop.height}}');
                            break;
                        case 'srcSetTablet':
                            $source->setAttribute('srcset', '{{tabletSrcset|default(desktopSrcset)}}');
                            $source->setAttribute('width', '{{imageTablet.width}}');
                            $source->setAttribute('height', '{{imageTablet.height}}');
                            break;
                        case 'srcSetMobile':
                            $source->setAttribute('srcset', '{{mobileSrcset|default(desktopSrcset)}}');
                            $source->setAttribute('width', '{{imageMobile.width}}');
                            $source->setAttribute('height', '{{imageMobile.height}}');
                            break;
                    }
                }
            } else {
                foreach ($picture->getElementsByTagName('source') as $source) {
                    $original = $source->getAttribute('srcset');
                    $cleaned = preg_replace('/(.*?)(?=\/wp-content|$)/', '', $original);
                    $source->setAttribute('srcset', $cleaned);
                }
            }
        }

        // Handle <img> tags
        foreach ($dom->getElementsByTagName('img') as $img) {
            if ($img->getAttribute('data-pattern-replaced')) {
                continue;
            }

            $img->setAttribute('data-pattern-replaced', 1);
            $name = $img->getAttribute('data-elementname');
            $isDynamic = $img->getAttribute('data-pattern-dynamic');
            $suffix = $img->getAttribute('data-pattern-suffix') ? '_' . $img->getAttribute('data-pattern-suffix') : '';

            if ($isDynamic && $name === 'content-image') {
                $frag = $dom->createDocumentFragment();
                $frag->appendXML("{% set content_image = get_image({$type}.content_image{$suffix}) %}");
                $img->parentNode->insertBefore($frag, $img);

                $img->setAttribute('src', '{{content_image.src}}');
                $img->setAttribute('srcset', '{{content_image.srcset}}');
                $img->setAttribute('alt', '{{content_image.alt}}');
                $img->setAttribute('width', '{{content_image.width}}');
                $img->setAttribute('height', '{{content_image.height}}');
            } elseif ($isDynamic && $name === 'main-image') {
                $img->setAttribute('src', '{{mainImageSrc}}');
                $img->setAttribute('alt', '{{imageDesktop.alt}}');
            } elseif (! $isDynamic) {
                $src = $img->getAttribute('src');
                if (preg_match('/(.*?)(?=\/wp-content|$)/', $src, $matches)) {
                    $img->setAttribute('src', str_replace($matches[0], '', $src));
                }
            }
        }

        return Utils::saveDom($dom);
    }
}
