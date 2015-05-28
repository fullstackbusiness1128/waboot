<?php
/**
 * Load Bootstrap javascript modules
 *
 * @package Waboot
 * @since 0.1.0.0
 */
function waboot_js_loader() {
	wp_enqueue_script( 'bootstrap.js', wbf_locate_template_uri( 'assets/js/bootstrap.min.js' ), array( 'jquery' ), false, true ); // Bootstrap JS components - Drop a custom build in your child theme's 'js' folder to override this one.
	wp_enqueue_script( 'offcanvas', wbf_locate_template_uri( 'sources/js/vendor/offcanvas.js' ), array( 'jquery' ), false, true );
	waboot_enqueue_main_script();
    // Comment reply script
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) )
		wp_enqueue_script( 'comment-reply' );
}
add_action( 'wp_enqueue_scripts', 'waboot_js_loader', 90 );
add_action( 'admin_enqueue_scripts', 'waboot_enqueue_main_script', 90 );

function waboot_enqueue_main_script(){
	global $wpdb;

	$received_mails = is_admin() ? $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."wb_mails",ARRAY_A) : false;
	if($received_mails){
		foreach($received_mails as $k => $m){
			$received_mails[$k]['sender_info'] = unserialize($m['sender_info']);
		}
	}

	$wpData = apply_filters("wbft/js/localization",array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'wpurl' => get_bloginfo('wpurl'),
			'isMobile' => wb_is_mobile(),
			'isAdmin' => is_admin(),
			'wp_screen' => function_exists("get_current_screen") ? get_current_screen() : null,
			'contactForm' => array(
				'contact_email_subject' => __("New Email from site","waboot"),
				'labels' => array(
					'success' => __("You email was send successfully. Thanks!","waboot"),
					'warning' => __("We are sorry: due technical difficulties we was unable to send your message correctly.","waboot"),
					'error'   => __("We are sorry: an error happens when sending your message.","waboot"),
					'errors'  => array(
						'isEmpty' => __("This field cannot be empty.","waboot"),
						'_default_' => __("An error was triggered by this field","waboot"),
					)
				),
				'mails' => json_encode($received_mails)
			)
		)
	);

	$deps = array('jquery','jquery-ui-core','jquery-ui-dialog','backbone','underscore');

	if(WABOOT_ENV == "dev"){
		wp_register_script( 'waboot', wbf_locate_template_uri( 'sources/js/waboot.js' ), $deps,false, true);
		$child_js = is_child_theme() ? wbf_locate_template_uri( 'assets/js/waboot-child.js' ) : false;
	}else{
		if(is_file(get_template_directory()."/assets/js/waboot.min.js")){
			wp_enqueue_script( 'waboot', wbf_locate_template_uri( 'assets/js/waboot.min.js' ), $deps,false, true);
		}else{
			wp_enqueue_script( 'waboot', wbf_locate_template_uri( 'sources/js/waboot.js' ), $deps,false, true); //Load the source file if minified is not available
		}
		$child_js = is_child_theme() ? wbf_locate_template_uri( 'assets/js/waboot-child.min.js' ) : false;
	}

	wp_localize_script( 'waboot', 'wbData', $wpData);
	wp_enqueue_script( 'waboot');
	if($child_js != ""){
		wp_enqueue_script( 'waboot-child', $child_js, array('jquery'),false, true);
	}

	if(is_admin()){
		$screen = get_current_screen();
		if($screen->base == "waboot-0_page_waboot_inbox"){
			wp_enqueue_style('jquery-ui-style');
		}
	}
}

function waboot_ie_compatibility(){
    ?>
    <!--[if lt IE 9]>
        <script src="<?php echo get_template_directory_uri(); ?>/assets/js/html5shiv.min.js" type="text/javascript"></script>
        <script src="<?php echo get_template_directory_uri(); ?>/assets/js/respond.min.js" type="text/javascript"></script>
    <![endif]-->
    <?php
}
add_action("wp_head",'waboot_ie_compatibility');