<?php
/*
Plugin Name: Easy Albums
Description: Put a group of galleries into an album, then put the album in a page.
Version: 2013.02.28
Author: Kailey Lampert
Author URI: kaileylampert.com
*/
if ( ! defined('ABSPATH') )
	die('-1');

// replacement [gallery] shortcode. Does everything core does, plus a little more
require plugin_dir_path(__FILE__) . 'shortcode.php';

require plugin_dir_path(__FILE__) . 'widgets.php';

require plugin_dir_path(__FILE__) . 'mce-integration.php';

// optional
// require plugin_dir_path(__FILE__) . 'ajax-layer.php';


$galleries_and_albums = new Galleries_and_Albums();

class Galleries_and_Albums {
	var $gallery_cpt = 'easy_gallery';
	var $album_cpt = 'easy_album';
	/**
	 * 
	 */
	function __construct() {

		add_action( 'init', array( &$this, 'init_register_cpt' ) );
		add_action( 'admin_head', array( &$this, 'cpt_icons' ) );

		add_filter( 'manage_'. $this->gallery_cpt .'_posts_columns', array( &$this, 'manage_gallery_posts_columns' ) );
		add_action( 'manage_'. $this->gallery_cpt .'_posts_custom_column', array( &$this, 'manage_gallery_posts_custom_column' ), 10, 2 );
		add_filter( 'manage_'. $this->album_cpt .'_posts_columns', array( &$this, 'manage_album_posts_columns' ) );
		add_action( 'manage_'. $this->album_cpt .'_posts_custom_column', array( &$this, 'manage_album_posts_custom_column' ), 10, 2 );

		add_action( 'edit_form_after_title', array( &$this, 'album_edit_form_after_title' ) );
		add_action( 'edit_form_after_editor', array( &$this, 'gallery_edit_form_after_editor' ) );

		add_filter( 'is_protected_meta', array( &$this, 'is_protected_meta'), 10, 3 );

		add_action( 'save_post', array( &$this, 'save_box' ), 10, 2 );

		add_shortcode( 'album', array( &$this, 'sc_album' ) );
	}

	/**
	 * Register our 2 custom post types
	 */
	function init_register_cpt() {

		$labels = array(
			'name' => 'Galleries',
			'singular_name' => 'Gallery',
			'add_new' => 'Add Gallery',
			'add_new_item' => 'Add New Gallery',
			'edit_item' => 'Edit Gallery',
			'new_item' => 'New Gallery',
			'all_items' => 'All Galleries',
			'view_item' => 'View Gallery',
			'search_items' => 'Search Galleries',
			'not_found' =>  'No galleries found',
			'not_found_in_trash' => 'No galleries found in Trash', 
			'parent_item_colon' => '',
			'menu_name' => 'Galleries'
		);
		$args = array(
			'labels' => $labels,
			'public' => false,
			'show_ui' => true,
			'rewrite' => false,
			'query_var' => false,
			'supports' => array( 'title', 'editor', 'thumbnail' )
		);
		register_post_type( $this->gallery_cpt, $args );

		$labels = array(
			'name' => 'Albums',
			'singular_name' => 'Album',
			'add_new' => 'Add Album',
			'add_new_item' => 'Add New Album',
			'edit_item' => 'Edit Album',
			'new_item' => 'New Album',
			'all_items' => 'All Albums',
			'view_item' => 'View Album',
			'search_items' => 'Search Albums',
			'not_found' =>  'No albums found',
			'not_found_in_trash' => 'No albums found in Trash', 
			'parent_item_colon' => '',
			'menu_name' => 'Albums'
		);
		$args = array(
			'labels' => $labels,
			'public' => false,
			'show_ui' => true,
			'show_in_nav_menu' => false,
			'show_in_menu' => 'edit.php?post_type='. $this->gallery_cpt,
			'rewrite' => false,
			'query_var' => false,
			'supports' => array( 'title' ),
			'register_meta_box_cb' => array( &$this, 'register_album_meta_box' )
		);
		register_post_type( $this->album_cpt, $args );
	}

		/**
		 * Prepare meta boxes for the Albums CPT
		 */
		function register_album_meta_box( $post ) {
			wp_enqueue_script('jquery-ui-sortable');
			add_meta_box( 'gallerypicker', 'Galleries', array( &$this, 'the_album_box' ), $post->post_type, 'normal' );
			add_meta_box( 'albumpreview', 'Preview', array( &$this, 'the_album_preview_box' ), $post->post_type, 'normal' );
		}

			/**
			 * Fill in "Galleries" metabox for Albums CPT
			 * Creates a checklist of available galleries
			 */
			function the_album_box( $post ) {

				echo '<p class="description">You can drag the names to change their order.</p>';

				$savedgalleries = (array) get_post_meta( $post->ID, 'galleries', true );

				$args = array(
					'post_type' => $this->gallery_cpt,
					'orderby' => 'post__in',
					'numberposts' => -1,
				);
				$args['post__in'] = $savedgalleries;

				$chkd = get_posts( $args );
				if ( empty( $savedgalleries ) ) $chkd = array();

				unset( $args['post__in'] );
				unset( $args['orderby'] );
				$args['post__not_in'] = $savedgalleries;
				$unchkd = get_posts( $args );

				$allgalleries = array_merge( $chkd, $unchkd );
				if ( count( $allgalleries ) < 1 ) {
					echo '<p><em>No galleries created yet</em></p>';
					return;
				}

				wp_nonce_field( 'na_galleries', 'nn_galleries');
				echo '<input type="hidden" name="galleries[]" value="0" /><ul id="gallery-cbs">';
				foreach( $allgalleries as $gallery ) {
					$s = in_array( $gallery->ID, $savedgalleries ) ? " checked='checked'" : '';
					$title = get_the_title( $gallery->ID );
					if ( empty( $title ) ) $title = '<em>no title</em>';
					echo "<li><label><input type='checkbox' name='galleries[]' value='{$gallery->ID}'$s /> {$title}</label></li>";
				}
				echo '</ul>';

				add_action('admin_print_footer_scripts', array( &$this, 'admin_footer' ) );

			}
				/**
				 * Make our gallery checkboxes sortable
				 */
				function admin_footer() {
					?><script>jQuery('#gallery-cbs').sortable(); jQuery('#gallery-cbs li').css('cursor', 'move');</script><?php
				}

			/**
			 * Fill in "Preview" metabox for Albums CPT
			 */
			function the_album_preview_box( $post ) {
				echo '<p class="description">Preview updates on save. Links disabled.</em></p>';
				echo do_shortcode( "[album id='{$post->ID}' nolinks=1]" );
			}

	function cpt_icons() {
		?><style type="text/css">
		#adminmenu #menu-posts-<?php echo $this->gallery_cpt; ?> div.wp-menu-image{
			background: transparent url(<?php echo plugins_url( 'pictures-stack.png', __FILE__ ); ?>) no-repeat 6px -17px;
		}
		#adminmenu #menu-posts-<?php echo $this->gallery_cpt; ?>:hover div.wp-menu-image,
		#adminmenu #menu-posts-<?php echo $this->gallery_cpt; ?>.wp-has-current-submenu div.wp-menu-image {
			background-position: 6px 7px;
		}
		</style><?php
	}
	/**
	 * Add new columns for Galleries CPT
	 */
	function manage_gallery_posts_columns( $columns ) {
		unset( $columns['date'] );
		$columns['gallerythumb'] = 'Thumbnail';
		$columns['date'] = 'Date';

		return $columns;
	}

	/**
	 * Fill in columns for Galleries CPT
	 */
	function manage_gallery_posts_custom_column( $column, $post_id ) {
		if ( $column == 'gallerythumb' )
			echo wp_get_attachment_image( $this->get_gallery_thumbnail_id( $post_id ), array( 50, 50 ) );
	}

	/**
	 * Add new columns for Albums CPT
	 */
	function manage_album_posts_columns( $columns ) {
		unset( $columns['date'] );
		$columns['albumpreview'] = 'Preview';
		$columns['date'] = 'Date';

		return $columns;
	}

	/**
	 * Fill in columns for Albums CPT
	 */
	function manage_album_posts_custom_column( $column, $post_id ) {
		if ( $column == 'albumpreview' )
			echo do_shortcode( "[album id='{$post_id}' nolinks=1 imgh=50 imgw=50 columns=5]" );
	}

	/**
	 * Show [album] shortcode on Albums CPT
	 */
	function album_edit_form_after_title() {
		if ( $this->album_cpt != get_post_type() ) return;
		global $post;
		if ( empty( $post->post_name ) ) return;
		echo "<p>Embed this album in a post or page by using: <code>[album name='{$post->post_name}']</code></p>";
	}

	/**
	 * Show additional info for Galleries CPT
	 */
	function gallery_edit_form_after_editor() {
		if ( $this->gallery_cpt != get_post_type() ) return;
		echo '<p>You can include just about any content in these galleries, but it works best with a gallery shortcode. <span class="description">e.g. <code>[gallery ids="123,456"]</code></span></p>';
	}

	/**
	 * In Albums CPT, selected galleries are saved in post meta
	 * Hide our meta in case the custom fields box is shown
	 */
	function is_protected_meta( $protected, $meta_key, $meta_type ) {
		if ( $this->album_cpt != get_post_type() ) return $protected;
		if ( 'galleries' == $meta_key ) return true;
		return $protected;
	}

	/**
	 * Save our selected galleries for Album CPT
	 */
	function save_box( $post_id, $post ) {

		if ( ! isset( $_POST['galleries'] ) ) //make sure our custom value is being sent
			return;
		if ( ! wp_verify_nonce( $_POST['nn_galleries'], 'na_galleries' ) ) //verify intent
			return;
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) //no auto saving
			return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) //verify permissions
			return;

		// make sure they're ints
		$galleries = array_map( 'intval', $_POST['galleries'] ); //sanitize
		
		// unset the placeholder
		if ( $galleries[0] === 0 )
			unset( $galleries[0] );

		// if new selection equals old selection, no need to save
		if ( $galleries == get_post_meta( $post_id, 'galleries', true ) )
			return;

		// delete what we have
		delete_post_meta( $post_id, 'galleries' );

		// insert the nw
		add_post_meta( $post_id, 'galleries', $galleries );

		// note: initially, each gallery id would be stored on its own meta field
		// so that get_post_meta( $post_id, 'galleries' ) would return our ready-array
		// but the ordering wasn't being preservered

	}

	/**
	 * The [album] shortcode
	 */
	function sc_album( $atts, $content ) {
		extract( shortcode_atts( array(
			'name' => false,
			'id' => false,
			'nolinks' => false,
		), $atts ) );
		unset( $atts['name'] );
		unset( $atts['id'] );

		// in the confines of our album, all images should link to the file
		$att_string = $nolinks ? ' link="none"' : " link='file'";
		foreach ( $atts as $k => $v ) {
			//pass params on to sub-galleries for consistency
			$att_string .= " $k='$v'";
		}

		// bail if no name or id provided
		if ( !$id && !$name ) return;

		if ( ! $id ) {
			$name = strtolower( $name );
			$album_post = array_shift( get_posts( "name={$name}&post_type={$this->album_cpt}") );
			$id = $album_post->ID;
		} else {
			$album_post = get_post( $id );
		}

		$galleries = (array) get_post_meta( $id, 'galleries', true );

		//if requesting a sub-gallery
		if ( isset( $_GET['showgallery'] ) ) {
			//and sub-gallery is in this set (in case of multiple albums per page)
			if ( in_array($_GET['showgallery'], $galleries ) ) {

				$back = remove_query_arg( 'showgallery' );

				return get_sub_gallery( $back, $_GET['showgallery'], $att_string );
			}
		}

		// otherwise...

		// get the feat or first image in each gallery for use as a gallery thumb
		$gallery_thumbs = array_map( array( &$this, 'get_gallery_thumbnail_id'), $galleries );

		$sc = '[gallery album="1" ids="'. implode( ',', $gallery_thumbs ).'" gals="'. implode( ',', $galleries ).'"'. $att_string .']';
		$html = "<input type='hidden' class='att_string' value=\"$att_string\" />" .do_shortcode( $sc );

		return $html;
	} // end sc_album()

	/**
	 * Get a galleries thumbnail
	 * Either featured image, first in [gallery] shortcode, or first inserted image
	 */
	function get_gallery_thumbnail_id( $gallery_id ) {
		if ( has_post_thumbnail( $gallery_id ) ) {
			// get post thumb if available
			return get_post_thumbnail_id( $gallery_id );
		} else {
			// else grab the first id in the shortcode
			$galpost = get_post( $gallery_id )->post_content;
			preg_match( '/\[gallery ids="(\d*)/', $galpost, $matches );
			if ( isset( $matches[1] ) ) {
				return $matches[1];
			} else {
				// else grab it from any first image
				preg_match( '/wp-image-(\d*)/', $galpost, $matches );
				if ( isset( $matches[1] ) )
					return $matches[1];
				else
					return false;

			}
		}
		return false;
	}


} //end class

/**
 * Get a gallery based on ID
 * 
 * @param string $back Link back to main album
 * @param int $gal_id ID of gallery to fetch
 * @param string $att_string Attributes inherited from [album] shortcode 
 */
function get_sub_gallery( $back, $gal_id, $att_string ) {
	$back = "<p><a class='backtoalbum' href='$back'>&larr; Back</a></p>";
	$gal = get_post( $gal_id );

	// insert inheritable shortcode attributes
	$content = preg_replace( '/\[gallery([^[]*)\]/', '[gallery$1'.$att_string.']', $gal->post_content );

	$content = do_shortcode( $content );
	return "<h2 id='albumgal-{$gal->ID}'>{$gal->post_title}</h2>{$back}". $content;
}


if ( ! function_exists( 'maybe_create_missing_intermediate_images') ) {
/*
 * @param int $id Image attachment ID
 * @param string $size_name Name of custom image size as added with add_image_size()
 * return bool True if intermediate image exists or was created. False if failed to create.
 */

function maybe_create_missing_intermediate_images( $id, $size_name ) {

	if ( ! image_get_intermediate_size( $id, $size_name ) ) { //if size doesn't exist for given image

		if ( ! function_exists('wp_generate_attachment_metadata' ) )
			include( ABSPATH . 'wp-admin/includes/image.php' );

		$upload_dir = wp_upload_dir();
		$image_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], wp_get_attachment_url( $id ) );
		$new = wp_generate_attachment_metadata( $id, $image_path );
		wp_update_attachment_metadata( $id, $new );

		if ( image_get_intermediate_size( $id, $size_name ) ) {
			// echo 'new image size created';
			return true; //new image size created
		} else {
			// echo 'failed to create new image size';
			return false; //failed to create new image size
		}

	}
	// echo 'already exists';
	return true; //already exists

}

}// end if func not exists

if ( ! function_exists( 'printer') ) {
	function printer( $input ) {
		echo '<pre>' . print_r( $input, true ) . '</pre>';
	}
}

//eof