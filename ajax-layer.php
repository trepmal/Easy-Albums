<?php
if ( ! defined('ABSPATH') )
	die('-1');

new Galleries_Albums_Ajax_Layer();

/** Known issues
 * with history.pushState, you can be in a sub-gallery, refresh the page, and remain in the sub-gallery
 * however, on page refresh, the original albumhtml is lost, rendering the back link useless
 *
 */

class Galleries_Albums_Ajax_Layer {

	function __construct() {
		add_action( 'wp_footer', array( &$this, 'wp_footer' ) );
		add_action( 'wp_ajax_get_gallery', array( &$this, 'get_gallery_cb' ) );
	}

	function wp_footer() {
		?><script>
		var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>',
			loading = '<?php echo admin_url('images/loading.gif'); ?>';

		function getUrlVars(str) {
			var vars = {};
			str = str.replace(/#.*/,''); //remove the hash and everything after
			var parts = str.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
				vars[key] = value;
			});
			return vars;
		}

		var albumhtml;
		jQuery('body').on('click', '.gallery.album a', function(ev) {
			ev.preventDefault();

			var thisimg = jQuery(this),
				thisalb = thisimg.closest('.album'),
				atts = thisalb.prev('.att_string').val();

			history.pushState({}, '', thisimg.attr('href') );

			// basically, get outerHtml
			albumhtml = thisalb.clone().wrap('<div>').parent().html();

			thisalb.html( '<img src="'+ loading +'" />' );

			qvars = getUrlVars( jQuery(this).attr('href') );

			jQuery.post(ajaxurl, {
				'action': 'get_gallery',
				'gallery_id': qvars.showgallery,
				'page': window.location.toString(),
				'att_string': atts
			}, function(response) {

				thisalb.replaceWith( response );
			});
		});
		jQuery('body').on('click', '.backtoalbum', function(ev) {
			ev.preventDefault();

			history.pushState({}, '', jQuery(this).attr('href') );

			// console.log( albumhtml );
			jQuery(this).parent('p').next('.gallery').replaceWith( albumhtml );
			jQuery(this).parent('p').prev('h2').remove(); //remove gallery title
			jQuery(this).parent('p').remove(); //remove back button
		});

		</script><?php
	}

	function get_gallery_cb() {
		$gallery_id = intval( $_POST['gallery_id'] );
		$att_string = isset( $_POST['att_string'] ) ? stripslashes( $_POST['att_string'] ) : '';
		global $post;
		$post = get_post( $gallery_id ); //need the $post global for the shortcode...

		die( get_sub_gallery( array_shift( explode('?', $_POST['page'] ) ), $gallery_id, $att_string ) );

	}

}

//eof