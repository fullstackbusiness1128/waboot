<?php

namespace Waboot\functions;
use WBF\components\mvc\HTMLView;
use WBF\components\utils\Query;

/**
 * Renders archive.php content. This is not used ATM.
 *
 * @param $template_file
 */
function render_archives($template_file){
	$args = get_archives_template_vars();
	(new HTMLView($template_file))->clean()->display($args);
}

/**
 * Gets the variables needed to render an archive.php page
 * 
 * @return array
 */
function get_archives_template_vars(){
	$vars = [];

	$tax = get_current_taxonomy();

	$vars['page_title'] = get_archive_page_title();
	if(is_author()){
		$vars['term_description'] = get_the_author_meta('description');
	}else{
		$vars['term_description'] = term_description();
	}
	$vars['blog_class'] = get_posts_wrapper_class();
	$vars['display_nav_above'] = (bool) \Waboot\functions\get_option('show_content_nav_above', 1);
	$vars['display_nav_below'] =  (bool) \Waboot\functions\get_option('show_content_nav_below', 1);
	$vars['options']['display_title'] = get_archive_option("display_title",$tax);
	$vars['options']['title_position'] = get_archive_option("title_position",$tax);
	$vars['options']['layout'] = get_archive_option("layout",$tax);
	$vars['options']['primary_sidebar_size'] = get_archive_option("primary_sidebar_size",$tax);
	$vars['options']['secondary_sidebar_size'] = get_archive_option("secondary_sidebar_size",$tax);

	$o = get_queried_object();

	//@see https://developer.wordpress.org/files/2014/10/wp-hierarchy.png

	if(is_author()){
		$tpl_base = "templates/author/";
		$tpl[] = $tpl_base.'author-'.get_the_author_meta('user_nicename');
		$tpl[] = $tpl_base.'author-'.get_the_author_meta('ID');
		$tpl[] = $tpl_base.'author';
	}else{
		$tpl_base = "templates/archive/";
		if($o instanceof \WP_Term){
			if($o->taxonomy === 'category'){
				$tpl[] = $tpl_base.'category'.'-'.$o->slug;
				$tpl[] = $tpl_base.'category-'.$o->term_id;
				$tpl[] = $tpl_base.'category';
			}else{
				$tpl[] = $tpl_base.$o->taxonomy.'-'.$o->slug;
				$tpl[] = $tpl_base.'taxonomy-'.$o->taxonomy.'-'.$o->slug;
				$tpl[] = $tpl_base.'taxonomy-'.$o->taxonomy;
				$tpl[] = $tpl_base.'taxonomy';
			}
		}elseif($o instanceof \WP_Post_Type){
			$tpl = $tpl_base."archive-".$o->name;
		}elseif(is_date()){
			$tpl = $tpl_base."date";
		}else{
			$tpl = "";
		}
	}

	if($tpl !== '' || \is_array($tpl)){
		$tpl = Waboot()->locate_template($tpl);
	}

	$vars['tpl'] = $tpl;

	return $vars;
}

/**
 * Gets the additional variables needed to render an aside.php
 *
 * @param $slug
 *
 * @return array
 */
function get_aside_template_vars($slug){
	$vars = [];
	switch($slug){
		case "aside-primary":
			$vars['classes'] = call_user_func(function(){
				if(has_filter("waboot_primary_container_class")){
					return apply_filters('waboot_primary_container_class', 'wbcol-4'); //backward compatibility
				}else{
					return apply_filters('waboot/layout/sidebar/primary/classes', 'wbcol-4');
				}
			});
			break;
		case "aside-secondary":
			$vars['classes'] = call_user_func(function(){
				if(has_filter("waboot_secondary_container_class")){
					return apply_filters('waboot_secondary_container_class', 'wbcol-4'); //backward compatibility
				}else{
					return apply_filters('waboot/layout/sidebar/secondary/classes', 'wbcol-4');
				}
			});
			break;
	}

	return $vars;
}


/**
 * Template for comments and pingbacks.
 *
 * Used as a callback by wp_list_comments() for displaying the comments.
 */
function render_comment($comment, $args, $depth){
	$vars = [
		'additional_comment_class' => empty( $args['has_children'] ) ? '' : 'parent',
		'is_approved' => $comment->comment_approved  != '0',
		'has_avatar' => $args['avatar_size'] != '0',
		'avatar' => get_avatar( $comment, $args['avatar_size'] ),
		'comment' => $comment,
		'args' => $args,
		'depth' => $depth
	];

	$template_file = "templates/view-parts/single-comment.php";
	$v = new HTMLView($template_file);
	$v->display($vars);
}

/**
 * Wraps the post video oembed within a post that is not video format
 *
 * todo: verify this
 *
 * @param $video
 *
 * @return string
 */
function wraps_video_embed_html($video) {
	return "<div class='wb-video-container'>{$video}</div>";
}
add_filter( 'embed_oembed_html', __NAMESPACE__. '\wraps_video_embed_html' );