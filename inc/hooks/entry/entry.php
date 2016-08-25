<?php

namespace Waboot\hooks\entry;
use WBF\components\mvc\HTMLView;
use WBF\components\utils\Utilities;

//Header:
add_action("waboot/entry/header",__NAMESPACE__."\\display_title");

//Footer:
add_action("waboot/entry/footer","display_post_date",10);
add_action("waboot/entry/footer","display_post_author",11);
add_action("waboot/entry/footer","display_post_categories",12);
add_action("waboot/entry/footer","display_post_tags",13);
add_action("waboot/entry/footer","display_post_comment_link",14);

/**
 * Display title in entry header
 *
 * @param \WP_Post $post
 */
function display_title($post = null){
	if(!$post) global $post;

	$can_display_title = $post instanceof \WP_Post &&
	                     (bool) \Waboot\functions\get_behavior("show-title",true) &&
	                     \Waboot\functions\get_behavior('title-position',"bottom") == "bottom"; //todo: add this

	if(!$can_display_title) return;

	if(is_singular()){
		$tpl = "templates/view-parts/entry-title-singular.php";
	}else{
		$tpl = "templates/view-parts/entry-title.php";
	}

	(new HTMLView($tpl))->display([
		'title' => get_the_title($post->ID)
	]);
}

/**
 * Prints HTML with date posted information for the current post.
 */
function display_post_date(){
	// Return early if theme options are set to hide date
	if(!\Waboot\functions\get_option('show_post_date', true)) return;

	$tpl = "templates/view-parts/entry-date.php";

	$relative_time = \Waboot\functions\get_option('show_post_relative_time', false);

	(new HTMLView($tpl))->display([
		'tag_date' => esc_attr(get_the_date('c')),
		'date' => !$relative_time ? esc_html( get_the_date() ) : sprintf( _x( '%s ago', 'Relative date output for entry footer' ,'waboot' ), human_time_diff( get_the_date( 'U' ), current_time( 'timestamp' ) ) )
	]);
}

/**
 * Prints HTML with meta information for the current post's author.
 */
function display_post_author(){
	// Return early if theme options are set to hide author
	if(!\Waboot\functions\get_option('show_post_author', true)) return;

	$tpl = "templates/view-parts/entry-author.php";

	(new HTMLView($tpl))->display([
		'author_url' =>  esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
		'author_title' => esc_attr(sprintf(__( 'View all posts by %s', 'waboot' ), get_the_author())),
		'author_name' => esc_html(get_the_author())
	]);
}

/**
 * Display the list of categories on a post in hierarchical order
 *
 * @use \Waboot\template_tags\get_the_terms_list_hierarchical()
 */
function display_post_categories(){
	// Return early if theme options are set to hide categories
	if (!\Waboot\functions\get_option('show_post_categories', true)) return;

	echo \Waboot\template_tags\get_the_terms_list_hierarchical(get_the_ID(), 'category', '<span class="cat-links">', ', ', '</span>');
}

function display_post_tags(){

}

function display_post_comment_link(){

}