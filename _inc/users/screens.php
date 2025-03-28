<?php
/**
 * BP Follow Screens
 *
 * @package BP-Follow
 * @subpackage Screens
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Catches any visits to the "Followers (X)" tab on a users profile.
 *
 * @uses bp_core_load_template() Loads a template file.
 */
/**
 * Catches any visits to the "Followers (X)" tab on a users profile.
 *
 * @uses bp_core_load_template() Loads a template file.
 */
function bp_follow_screen_followers() {
    do_action('bp_follow_screen_followers');
    
    // Add our content to the template
    add_action('bp_template_content', 'bp_follow_screen_followers_content');
    
    // Load the plugins template
    bp_core_load_template('members/single/plugins');
}

/**
 * Content for the followers screen.
 */
function bp_follow_screen_followers_content() {
    echo '<div id="members-dir-list" class="dir-list members follow followers" data-bp-list="members">';
    
    // If BP Nouveau is active
    if (function_exists('bp_nouveau')) {
        echo '<div id="bp-ajax-loader">';
        bp_nouveau_user_feedback('generic-loading');
        echo '</div>';
    }
    
    // Make sure the members query gets the right users
    add_filter('bp_ajax_querystring', function($qs, $object) {
        if ($object !== 'members') return $qs;
        
        $args = wp_parse_args($qs);
        
        // Get followers of displayed user
        $followers = bp_follow_get_followers(array(
            'user_id' => bp_displayed_user_id()
        ));
        
        if (empty($followers)) {
            $followers = array(0); // No one is following, use 0 to return no results
        }
        
        $args['include'] = $followers;
        $args['per_page'] = 20;
        
        return build_query($args);
    }, 20, 2);
    
    bp_get_template_part('members/members-loop');
    
    echo '</div>';
    
    // Add JavaScript to fix AJAX issues
    add_action('wp_footer', 'bp_follow_fix_ajax_script', 30);
}

/**
 * Catches any visits to the "Following (X)" tab on a users profile.
 *
 * @uses bp_core_load_template() Loads a template file.
 */
function bp_follow_screen_following() {
    do_action('bp_follow_screen_following');
    
    // Add our content to the template
    add_action('bp_template_content', 'bp_follow_screen_following_content');
    
    // Load the plugins template
    bp_core_load_template('members/single/plugins');
}

/**
 * Content for the following screen.
 */
function bp_follow_screen_following_content() {
    echo '<div id="members-dir-list" class="dir-list members follow following" data-bp-list="members">';
    
    // If BP Nouveau is active
    if (function_exists('bp_nouveau')) {
        echo '<div id="bp-ajax-loader">';
        bp_nouveau_user_feedback('generic-loading');
        echo '</div>';
    }
    
    // Make sure the members query gets the right users
    add_filter('bp_ajax_querystring', function($qs, $object) {
        if ($object !== 'members') return $qs;
        
        $args = wp_parse_args($qs);
        
        // Get users that the displayed user is following
        $following = bp_follow_get_following(array(
            'user_id' => bp_displayed_user_id()
        ));
        
        if (empty($following)) {
            $following = array(0); // Not following anyone, use 0 to return no results
        }
        
        $args['include'] = $following;
        $args['per_page'] = 20;
        
        return build_query($args);
    }, 20, 2);
    
    bp_get_template_part('members/members-loop');
    
    echo '</div>';
    
    // Add JavaScript to fix AJAX issues
    add_action('wp_footer', 'bp_follow_fix_ajax_script', 30);
}

/**
 * Catches any visits to the "Activity > Following" tab on a users profile.
 *
 * @uses bp_core_load_template() Loads a template file.
 */
function bp_follow_screen_activity_following() {
	bp_update_is_item_admin( is_super_admin(), 'activity' );
	do_action( 'bp_activity_screen_following' );
	bp_core_load_template( apply_filters( 'bp_activity_template_following', 'members/single/home' ) );
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
function bp_follow_load_template_filter( $found_template, $templates ) {
	$bp = $GLOBALS['bp'];

	// Only filter the template location when we're on the follow component pages.
	if ( ! bp_is_current_component( $bp->follow->followers->slug ) && ! bp_is_current_component( $bp->follow->following->slug ) )
		return $found_template;

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
		// when the template isn't found in the parent / child theme.
		bp_register_template_stack( 'bp_follow_get_template_directory', 14 );

		// locate_template() will attempt to find the plugins.php template in the
		// child and parent theme and return the located template when found
		//
		// plugins.php is the preferred template to use, since all we'd need to do is
		// inject our content into BP
		//
		// note: this is only really relevant for bp-default themes as theme compat
		// will kick in on its own when this template isn't found.
		$found_template = locate_template( 'members/single/plugins.php', false, false );

		// add AJAX support to the members loop
		// can disable with the 'bp_follow_allow_ajax_on_follow_pages' filter.
		if ( apply_filters( 'bp_follow_allow_ajax_on_follow_pages', true ) ) {
			// add the "Order by" dropdown filter
			add_action( 'bp_member_plugin_options_nav',    'bp_follow_add_members_dropdown_filter' );

			// add ability to use AJAX.
			add_action( 'bp_after_member_plugin_template', 'bp_follow_add_ajax_to_members_loop' );
		}

		// add our hook to inject content into BP
		//
		// note the new template name for our template part.
		add_action( 'bp_template_content', function() {
			bp_get_template_part( 'members/single/follow' );
		} );
	}

	return apply_filters( 'bp_follow_load_template_filter', $found_template );
}
add_filter( 'bp_located_template', 'bp_follow_load_template_filter', 10, 2 );

/** UTILITY ********************************************************/

/**
 * Get the BP Follow template directory.
 *
 * @author r-a-y
 * @since 1.2
 *
 * @uses apply_filters()
 * @return string
 */
function bp_follow_get_template_directory() {
	return apply_filters( 'bp_follow_get_template_directory', constant( 'BP_FOLLOW_DIR' ) . '/_inc/templates' );
}

/**
 * Add ability to use AJAX on the /members/single/plugins.php template.
 *
 * The plugins.php template hardcodes the 'no-ajax' class to prevent AJAX
 * from being used.
 *
 * We want to use AJAX; so we dynamically remove the class with jQuery after
 * the document has finished loading.
 *
 * This will enable AJAX in our members loop.
 *
 * Hooked to the 'bp_after_member_plugin_template' action.
 *
 * @author r-a-y
 * @since 1.2
 *
 * @see bp_follow_load_template_filter()
 */
function bp_follow_add_ajax_to_members_loop() {
?>

	<script type="text/javascript">
	jQuery(document).ready( function() {
		jQuery('#subnav').removeClass('no-ajax');
	});
	</script>

<?php
}

/**
 * Add "Order By" dropdown filter to the /members/single/plugins.php template.
 *
 * Hooked to the 'bp_member_plugin_options_nav' action.
 *
 * @author r-a-y
 * @since 1.2
 *
 * @see bp_follow_load_template_filter()
 */
function bp_follow_add_members_dropdown_filter() {
?>

	<?php do_action( 'bp_members_directory_member_sub_types' ); ?>

	<li id="members-order-select" class="last filter">

		<?php // the ID for this is important as AJAX relies on it! ?>
		<label for="members-<?php echo bp_current_action(); ?>-orderby"><?php _e( 'Order By:', 'buddypress-followers' ); ?></label>
		<select id="members-<?php echo bp_current_action(); ?>-orderby" data-bp-filter="members">
			<?php if ( class_exists( 'BP_User_Query' ) ) : ?>
				<option value="newest-follows"><?php _e( 'Newest Follows', 'buddypress-followers' ); ?></option>
				<option value="oldest-follows"><?php _e( 'Oldest Follows', 'buddypress-followers' ); ?></option>
			<?php endif; ?>
			<option value="active"><?php _e( 'Last Active', 'buddypress-followers' ); ?></option>
			<option value="newest"><?php _e( 'Newest Registered', 'buddypress-followers' ); ?></option>

			<?php if ( bp_is_active( 'xprofile' ) ) : ?>
				<option value="alphabetical"><?php _e( 'Alphabetical', 'buddypress-followers' ); ?></option>
			<?php endif; ?>

			<?php do_action( 'bp_members_directory_order_options' ); ?>

		</select>
	</li>

<?php
}


/**
 * Add JavaScript to fix AJAX loading issues on follow pages.
 */
function bp_follow_fix_ajax_script() {
    if (!bp_is_user()) {
        return;
    }
    
    $bp = $GLOBALS['bp'];
    
    if (bp_is_current_component($bp->follow->followers->slug) || 
        bp_is_current_component($bp->follow->following->slug)) {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function() {
        // Remove no-ajax class
        jQuery('#subnav').removeClass('no-ajax');
        jQuery('#members-dir-list').removeClass('no-ajax');
        
        // Re-initialize AJAX functionality for BP Nouveau
        if (typeof bp !== 'undefined' && typeof bp.Nouveau !== 'undefined' && typeof bp.Nouveau.objectNavigation !== 'undefined') {
            bp.Nouveau.objectNavigation.setupGlobalSearch();
        }
        
        // For legacy themes
        jQuery(document).ajaxComplete(function(event, xhr, settings) {
            // Re-apply follow buttons after AJAX load
            if (settings.data && settings.data.indexOf('action=members_filter') >= 0) {
                if (typeof bp_follow_button_action === 'function') {
                    jQuery('.follow-button a').off('click').on('click', function() {
                        bp_follow_button_action(jQuery(this), 'member-loop');
                        return false;
                    });
                }
            }
        });
    });
    </script>
    <?php
    }
}