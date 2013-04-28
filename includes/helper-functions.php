<?php 
/**
 * Get the BP Follow template directory.
 *
 * @author r-a-y
 * @since 1.2
 *
 * @uses apply_filters()
 * @return string
 */
function cpt4bp_get_template_directory() {
	return apply_filters( 'cpt4bp_get_template_directory', constant( 'CPT4BP_TEMPLATE_PATH' ));
}

/** TEMPLATE LOADER ************************************************/

/**
 * BP Follow template loader.
 *
 * This function sets up BP Follow to use custom templates.
 *
 * If a template does not exist in the current theme, we will use our own
 * bundled templates.
 *
 * We're doing two things here:
 *  1) Support the older template format for themes that are using them
 *     for backwards-compatibility (the template passed in
 *     {@link bp_core_load_template()}).
 *  2) Route older template names to use our new template locations and
 *     format.
 *
 * View the inline doc for more details.
 *
 * @since 1.0
 */
function cpt4bp_load_template_filter( $found_template, $templates ) {
	global $bp;
 
 // echo '<pre>';
 // print_r($bp);
 // echo '</pre>';
 
 	// Only filter the template location when we're on the follow component pages.
	//if ( ! bp_is_current_component( $bp->follow->followers->slug ) && ! bp_is_current_component( $bp->follow->following->slug ) )
		//return $found_template;

	// $found_template is not empty when the older template files are found in the
	// parent and child theme
	//
	//  /wp-content/themes/YOUR-THEME/members/single/following.php
	//  /wp-content/themes/YOUR-THEME/members/single/followers.php
	//
	// The older template files utilize a full template ( get_header() +
	// get_footer() ), which sucks for themes and theme compat.
	//
	// When the older template files are not found, we use our new template method,
	// which will act more like a template part.
	if ( empty( $found_template ) ) {
		// register our theme compat directory
		//
		// this tells BP to look for templates in our plugin directory last
		// when the template isn't found in the parent / child theme
		bp_register_template_stack( 'cpt4bp_get_template_directory', 14 );
	
		// locate_template() will attempt to find the plugins.php template in the
		// child and parent theme and return the located template when found
		//
		// plugins.php is the preferred template to use, since all we'd need to do is
		// inject our content into BP
		//
		// note: this is only really relevant for bp-default themes as theme compat
		// will kick in on its own when this template isn't found
		$found_template = locate_template( 'members/single/plugins.php', false, false );
		
		// add our hook to inject content into BP
		//
		// note the new template name for our template part
		if($bp->current_action == 'my-posts'){
			add_action( 'bp_template_content', create_function( '', "
				bp_get_template_part( 'cpt4bp/bp/members-post-display' );
			" ) );
		} elseif($bp->current_action == 'create'){
			add_action( 'bp_template_content', create_function( '', "
				bp_get_template_part( 'cpt4bp/bp/members-post-create' );
			" ) );
		} 
	}

	return apply_filters( 'cpt4bp_load_template_filter', $found_template );
}
add_filter( 'bp_located_template', 'cpt4bp_load_template_filter', 10, 2 );



/**
 * Delete a product post
 * 
 * @package BuddyPress Custom Group Types
 * @since 0.1-beta	
 */
function cpt4bp_delete_product_post( $group_id ){    
    $groups_post_id = groups_get_groupmeta( $group_id, 'group_post_id' );
    
    wp_delete_post( $groups_post_id );
}
add_action( 'groups_before_delete_group', 'cpt4bp_delete_product_post' );

/**
 * Locate a template
 * 
 * @package BuddyPress Custom Group Types
 * @since 0.1-beta	
 */
function cpt4bp_locate_template( $file ) {
	if( locate_template( array( $file ), false ) ) {
		locate_template( array( $file ), true );
	} else {
		include( CPT4BP_TEMPLATE_PATH .$file );
	}
}

function cpt4bp_group_extension_link(){
	global $bp;
	echo bp_group_permalink().$bp->current_action.'/';
}

/**
 * Clean the input by type
 * 
 * @package BuddyPress Custom Group Types
 * @since 0.1-beta	
 */
function cpt4bp_app_clean_input( $input, $type ) {
	global $allowedposttags;
	
    $cleanInput = false;
    
    switch( $type ) {
		case 'text':
			$cleanInput = wp_filter_nohtml_kses( $input );
	        break;
			
        case 'checkbox':
            $input === '1'? $cleanInput = '1' : $cleanInput = '';
        	break;
			
		case 'html':
            $cleanInput = wp_kses( $input, $allowedposttags );
        	break;
			
    	default:
        	$cleanInput = false;
        	break;
    }
	
    return $cleanInput;
}

?>