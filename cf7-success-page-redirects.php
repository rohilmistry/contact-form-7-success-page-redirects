<?php
/**
 * Plugin Name: Contact Form 7 - Success Page Redirects
 * Description: An add-on for Contact Form 7 that provides a straightforward method to redirect visitors to success pages or thank you pages.
 * Version: 1.1.6
 * Author: Ryan Nevius
 * Author URI: http://www.ryannevius.com
 * License: GPLv3
 */

/**
 * Verify CF7 dependencies.
 */
function cf7_success_page_admin_notice() {
    // Verify that CF7 is active and updated to the required version (currently 3.9.0)
    if ( is_plugin_active('contact-form-7/wp-contact-form-7.php') ) {
        $wpcf7_path = plugin_dir_path( dirname(__FILE__) ) . 'contact-form-7/wp-contact-form-7.php';
        $wpcf7_plugin_data = get_plugin_data( $wpcf7_path, false, false);
        $wpcf7_version = (int)preg_replace('/[.]/', '', $wpcf7_plugin_data['Version']);
        // CF7 drops the ending ".0" for new major releases (e.g. Version 4.0 instead of 4.0.0...which would make the above version "40")
        // We need to make sure this value has a digit in the 100s place.
        if ( $wpcf7_version < 100 ) {
            $wpcf7_version = $wpcf7_version * 10;
        }
        // If CF7 version is < 3.9.0
        if ( $wpcf7_version < 390 ) {
            echo '<div class="error"><p><strong>Warning: </strong>Contact Form 7 - Success Page Redirects requires that you have the latest version of Contact Form 7 installed. Please upgrade now.</p></div>';
        }
    }
    // If it's not installed and activated, throw an error
    else {
        echo '<div class="error"><p>Contact Form 7 is not activated. The Contact Form 7 Plugin must be installed and activated before you can use Success Page Redirects.</p></div>';
    }
}
add_action( 'admin_notices', 'cf7_success_page_admin_notice' );


/**
 * Disable Contact Form 7 JavaScript completely
 */
add_filter( 'wpcf7_load_js', '__return_false' );


/**
 * Adds a box to the main column on the form edit page.
 */
// Register the meta boxes
function cf7_success_page_settings() {
    add_meta_box( 'cf7-redirect-settings', 'Success Page Redirect', 'cf7_success_page_metaboxes', '', 'form', 'low');
}
add_action( 'wpcf7_add_meta_boxes', 'cf7_success_page_settings' );

// Store Success Page Info
function cf7_success_page_save_contact_form( $contact_form ) {
    
	$contact_form_id = $contact_form->id();

    if ( !isset( $_POST ) || empty( $_POST ) || !isset( $_POST['cf7-redirect-page-id'] ) ) {
        return;
    }
    else {
        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $_POST['cf7_success_page_metaboxes_nonce'], 'cf7_success_page_metaboxes' ) ) {
            return;
        }
		//if nothing is in the search page box then simply assign 0
		if(empty($_POST['search_page'])){
			$cf7_page_key	=	0;
		}
		else{
			$cf7_page_key	=	$_POST['cf7-redirect-page-id'];
		}
        // Update the stored value
        update_post_meta( $contact_form_id, '_cf7_success_page_key', $cf7_page_key );
    }
}
add_action( 'wpcf7_after_save', 'cf7_success_page_save_contact_form' );


/**
 * Copy Redirect page key and assign it to duplicate form
 */
function cf7_success_page_after_form_create( $contact_form ){
    $contact_form_id = $contact_form->id();

    // Get the old form ID
    if ( !empty( $_REQUEST['post'] ) && !empty( $_REQUEST['_wpnonce'] ) ) {
        $old_form_id = get_post_meta( $_REQUEST['post'], '_cf7_success_page_key', true );
    }
    // Update the duplicated form
    update_post_meta( $contact_form_id, '_cf7_success_page_key', $old_form_id );
}
add_action( 'wpcf7_after_create', 'cf7_success_page_after_form_create' );


/**
 * Redirect the user, after a successful email is sent
 */
function cf7_success_page_form_submitted( $contact_form ) {
    $contact_form_id = $contact_form->id();

    // Send us to a success page, if there is one
    $success_page = get_post_meta( $contact_form_id, '_cf7_success_page_key', true );
    if ( !empty($success_page) ) {
        wp_redirect( get_permalink( $success_page ) );
        die();
    }
}
add_action( 'wpcf7_mail_sent', 'cf7_success_page_form_submitted' );

/*=================================================================================*/

/*
 *	Added new codes and new scripts
 *
 */

/*
 *
 *	Add CSS and JS for the AJAX search
 *
 */
wp_enqueue_style( 'cf7_ajax_page_search',  plugin_dir_url(__FILE__) . 'assets/css/cf7_ajax_page_search.css' );

//Auto complete jQuery plugin Src : https://github.com/devbridge/jQuery-Autocomplete
wp_enqueue_script('cf7_jquery-autocomplete', plugin_dir_url(__FILE__)  . 'assets/js/devbridge-jquery-autocomplete.js', array('jquery'), '1.2.9', true); 
wp_enqueue_script('cf7_ajax_page_search', plugin_dir_url(__FILE__)  . 'assets/js/backend.js', array('jquery'), '1.0', true);

//Localize script to use parameter in backend.js
wp_localize_script( 'cf7_ajax_page_search', 'cf7_redirect_params', array(
	'loading' => plugin_dir_url(__FILE__)  .'assets/images/ajax-loader.gif',
	'admin_ajax_url'=>admin_url( 'admin-ajax.php' )
));

//For AJAX in wordpress
add_action('wp_ajax_find_searched_page', 'get_searched_page');
add_action('wp_ajax_nopriv_find_searched_page', 'get_searched_page');

function get_searched_page(){
	
	$search_keyword = $_REQUEST['query']; //Get Requested parameter
	$suggestions   = array();

	$args = array(
		's'                   => $search_keyword,
		'post_type'           => 'page',
		'post_status'         => 'publish',
		'posts_per_page'      => '3',
	);

	$pages = get_posts( $args );

	if ( !empty( $pages ) ) {
		foreach ( $pages as $page ) {	
			$suggestions[] = array(
				'id'    => $page->ID,
				'value' => $page->post_title
			);
		}
	}
	else {
		$suggestions[] = array(
			'id'    => - 1,
			'value' => __( 'No results', 'cf7' )
		);
	}
	wp_reset_postdata();


	$suggestions = array(
		'suggestions' => $suggestions
	);


	echo json_encode( $suggestions );
	die(); //Die so that it won't give '0' in return
	
}
/*=================================================================================*/

// Create the meta boxes
function cf7_success_page_metaboxes( $post ) {
	
    wp_nonce_field( 'cf7_success_page_metaboxes', 'cf7_success_page_metaboxes_nonce' );
    $cf7_success_page = get_post_meta( $post->id(), '_cf7_success_page_key', true );
	//New Added Line	
	$redirect_key	=	!empty($cf7_success_page) ? $cf7_success_page : 0; //Default is 0

    // The meta box content
    echo '<label for="cf7-redirect-page-id"><strong>Redirect to: </strong></label><br> ';
	
	$args=array(
        'orderby' =>'parent',
        'order' =>'asc',
        'post_type' =>'page',
        'post__in' => array($redirect_key),
    );
	$page_query = get_posts($args);
	?>

	<input type="text"
		   name="search_page"
		   id="cf7-s"
		   class="cf7-s"
		   placeholder="Search Page"
		   data-min-chars="3"
		   autocomplete="off"
		   value="<?php echo $page_query[0]->post_title; ?>"
		   />
	<input type="hidden" value="<?php echo $redirect_key; ?>" class="cf7_key" name="cf7-redirect-page-id" id="cf7" />
	<?php
}
