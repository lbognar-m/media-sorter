<?php

function mediasorter_core_init()
{
	if(class_exists('Mediamatic_Topbar'))
	{
		return;
	}
	add_action( 'wp_enqueue_scripts', 'mediasorter_register_scripts', 20);
}


function mediasorter_register_scripts()
{
	if (!is_user_logged_in())
	{
		return;
	}
	if (is_front_page())
	{
		return;
	}

	
	add_action('wp_enqueue_scripts', function(){wp_enqueue_media();});

	wp_enqueue_media();
	add_thickbox();
	
	wp_register_script( 'mediasorter-builder-util', MEDIASORTER_URL . '/assets/js/mediasorter-util.js', array( 'jquery' ), '1.0', true );
	wp_enqueue_script( 'mediasorter-builder-util' );

	wp_register_script( 'mediasorter-builder-upload-hook', MEDIASORTER_URL . '/assets/js/hook-post-add-media.js', array( 'jquery' ), '1.0', true );
	wp_enqueue_script( 'mediasorter-builder-upload-hook' );

};


function mediasorter_cores()
{
	$free = false;

	if (MEDIASORTER_PLUGIN_NAME == 'mediasorter Lite')
	{
		$free = true;
	}
    
	if ($free)
	{
		add_filter( 'plugin_action_links_' . MEDIASORTER_PLUGIN_BASE, 'mediasorter_go_pro_version' );
	}
}


function mediasorter_go_pro_version($links)
{
	$links[] = '<a target="_blank" href="http://mediasorter.frenify.com/1/" style="color: #43B854; font-weight: bold">'. esc_html__('Go Pro', MEDIASORTER_TEXT_DOMAIN) .'</a>';
	return $links;
}

call_user_func('mediasorter_core_init');
