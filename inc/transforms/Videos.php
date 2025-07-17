<?php

namespace GetPattern\Transforms;

use GetPattern\DOM\Utils;

class Videos
{
    public static function transform(string $html, string $type = 'fields'): string
    {
        $dom = Utils::loadDom($html);
        $xpath = new \DOMXpath($dom);
        $videoIds = $xpath->query('//*[@data-videoid]');

        if ($videoIds->length > 0) {
            foreach ($videoIds as $videoId) {
                $pattern_replaced = $videoId->getAttribute('data-pattern-replaced');
                if ($pattern_replaced) {
                    return Utils::saveDom($dom);
                } else {
                    $videoId->setAttribute('data-pattern-replaced', 1);
                }

                $render_dynamic = $videoId->getAttribute('data-pattern-dynamic');

                if ($render_dynamic == '1') {
                    $suffix = $videoId->getAttribute('data-pattern-suffix') ? "_" . $videoId->getAttribute('data-pattern-suffix') : '';
                    $video_type = $videoId->getAttribute('data-videotype');

                    $frag = $dom->createDocumentFragment();
                    $frag->appendXML("
					{% set videoType = " . $type . ".video_type" . $suffix . "|ru %}
					{% set youtubeEmbed = " . $type . ".youtube_embed" . $suffix . "|default(false) %}
					{% if 'data-src=' in youtubeEmbed %}
						{% set youtubeDesktopParts = youtubeEmbed|split('data-src=') %}
					{% elseif 'src=' in youtubeEmbed %}
						{% set youtubeDesktopParts = youtubeEmbed|split('src=') %}
					{% endif %}
					{% set youtubeDesktopUrl = youtubeDesktopParts[1]|split('\"')[1] %}
					{% set youtubeDesktopId = youtubeDesktopUrl|split('/embed/')|last|split('?')|first %}

					{% set youtubeMobile = " . $type . ".youtube_mobile" . $suffix . " %}
					{% if 'data-src=' in youtubeMobile %}
						{% set youtubeMobileParts = youtubeMobile|split('data-src=') %}
					{% elseif 'src=' in youtubeMobile %}
						{% set youtubeMobileParts = youtubeMobile|split('src=') %}
					{% endif %}
					{% set youtubeMobileUrl = youtubeMobileParts[1]|split('\"')[1] %}
					{% set youtubeMobileId = youtubeMobileUrl|split('/embed/')|last|split('?')|first|default(youtubeDesktopId) %}

					{% set vimeoEmbed = " . $type . ".vimeo_embed" . $suffix . "|default(false) %}
					{% if 'data-src=' in vimeoEmbed %}
						{% set vimeoDesktopParts = vimeoEmbed|split('data-src=') %}
					{% elseif 'src=' in vimeoEmbed %}
						{% set vimeoDesktopParts = vimeoEmbed|split('src=') %}
					{% endif %}
					{% set vimeoDesktopUrl = vimeoDesktopParts[1]|split('\"')[1] %}
					{% set vimeoDesktopId = vimeoDesktopUrl|split('/video/')|last|split('?')|first %}

					{% set vimeoMobile = " . $type . ".vimeo_mobile" . $suffix . " %}
					{% if 'data-src=' in vimeoMobile %}
						{% set vimeoMobileParts = vimeoMobile|split('data-src=') %}
					{% elseif 'src=' in vimeoMobile %}
						{% set vimeoMobileParts = vimeoMobile|split('src=') %}
					{% endif %}
					{% set vimeoMobileUrl = vimeoMobileParts[1]|split('\"')[1] %}
					{% set vimeoMobileId = vimeoMobileUrl|split('/video/')|last|split('?')|first|default(vimeoDesktopId) %}

					{% set videoDesktop = " . $type . ".video_desktop" . $suffix . "|default(false) %}
					{% set videoMobile = " . $type . ".video_mobile" . $suffix . "|default(false) %}
					{% set videoId = block.id ~ '-' ~ videoNumber|default(1) %}
					");



                    $videoId->parentNode->insertBefore($frag, $videoId);

                    $videoId->setAttribute("data-youtubedesktop", "{{youtubeDesktopId}}");
                    $videoId->setAttribute("data-youtubemobile", "{{youtubeMobileId}}");
                    $videoId->setAttribute("data-vimeodesktopid", "{{vimeoDesktopId}}");
                    $videoId->setAttribute("data-vimeomobileid", "{{vimeoMobileId}}");
                    $videoId->setAttribute("data-vimeo-desktop-url", "{{vimeoDesktopUrl}}");
                    $videoId->setAttribute("data-vimeo-mobile-url", "{{vimeoMobileUrl}}");
                    $videoId->setAttribute("data-desktopvideo", "{{gt_video_mainsrc(videoDesktop['url'])}}");
                    $videoId->setAttribute("data-mobilevideo", "{{gt_video_mainsrc(videoMobile['url'])}}");

                    $videoId->setAttribute("data-videoid", "{{videoId}}");
                    $videoId->setAttribute("data-videotype", "{{videoType}}");


                    // Add video type check
                    $frag_after = $dom->createDocumentFragment();
                    $frag_after->appendXML("{% if videoType == 'upload' %}
					<video class='cblvc__video-player cblvc-video-container__video-player' width='{{videoDesktop.width}}' height='{{videoDesktop.height}}'>
						<source src='' type='video/mp4' />
					</video>
					{% endif %}
					{% if videoType == 'youtube' %}
						<div id='yt-{{videoId}}'></div>
					{% endif %}
					{% if videoType == 'vimeo' %}
						<div id='vimeo-{{videoId}}-{{vimeoDesktopId}}' style='width: 100%'></div>
					{% endif %}");

                    $videoTag = $xpath->query('//*[@data-pattern-type]', $videoId);
                    $parent = $videoTag->item(0);
                    $parent->nodeValue = '';
                    $parent->appendChild($frag_after);

                    $videos = $videoId->getElementsByTagName('video');
                    if ($videos->length > 0) {
                        $videos->item(0)->setAttribute('muted', '{{ muted ? true : false }}');
                    }
                } else {
                    $desktopVideoOriginal = $videoId->getAttribute('data-desktopvideo');
                    $mobileVideoOriginal = $videoId->getAttribute('data-mobilevideo');

                    $desktopVideoReplace = preg_match('/(.*?)(?=\/wp-content|$)/', $desktopVideoOriginal, $matches);
                    $mobileVideoReplace = preg_match('/(.*?)(?=\/wp-content|$)/', $mobileVideoOriginal, $matches);

                    $videoId->setAttribute('data-desktopvideo', str_replace($matches[0], '', $desktopVideoOriginal));
                    $videoId->setAttribute('data-mobilevideo', str_replace($matches[0], '', $mobileVideoOriginal));
                }
            }
        }
        
        return Utils::saveDom($dom);
    }
}
