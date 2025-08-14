<?php

namespace GetPattern;

use GetPattern\Transforms\Fields;
use GetPattern\Transforms\Images;
use GetPattern\Transforms\Videos;
use GetPattern\Transforms\Links;
use GetPattern\DOM\Clean;

class Renderer
{
    public static function return_pattern_block(int $pid): array
    {
        $post = get_post($pid);

        if (! $post) {
            echo 'No post';
            die();
        }

        $blocks = parse_blocks($post->post_content);

        $parsedBlock = $blocks[0];
        $name = str_replace('acf/', '', $parsedBlock['blockName']);

        $html = render_block($parsedBlock);
        
        $html = Clean::removeBlankLines($html);
        $html = Clean::prettify($html);
        $html = Clean::removeLoadedClass($html);
        $html = Clean::replaceBlockId($html);
        $html = Clean::addBlockClass($html);
        
        $html = Links::transform($html);
        $html = Images::transform($html);
        $html = Videos::transform($html);
        
        $html = Fields::transform($html, 'fields', $name);
        
        $html = Clean::fixHtml($html);
        $html = Clean::fixCloseTags($html);


        return [
            'name' => $name,
            'html' => $html,
        ];
    }
}
