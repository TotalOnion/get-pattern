<?php

namespace GetPattern;

class AjaxHandler
{
    public static function init()
    {
        add_action('wp_ajax_nopriv_get_pattern_block', [self::class, 'get_pattern_block']);
    }

    public static function get_pattern_block()
    {
        if (! isset($_POST['postID'])) {
                echo 'No post ID sent';
            wp_die();
        }
        
        $pid = (int) $_POST['postID'];
        echo json_encode(Renderer::return_pattern_block($pid));
        die();
    }
}
