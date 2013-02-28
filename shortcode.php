<?php
if ( ! defined('ABSPATH') )
	die('-1');

remove_shortcode( 'gallery', 'gallery_shortcode' );
add_shortcode( 'gallery', 'gallery_shortcode_plus' );
/**
 * The Gallery Plus shortcode.
 *
 * Plus simply adds 'imgw' and 'imgh' attributes that allow generating of real thumbnails on-the-fly
 *
 * This implements the functionality of the Gallery Shortcode for displaying
 * WordPress images on a post.
 *
 * @since 2.5.0
 *
 * @param array $attr Attributes of the shortcode.
 * @return string HTML content to display gallery.
 */
function gallery_shortcode_plus($attr) {
	$post = get_post();

	static $instance = 0;
	$instance++;

	if ( ! empty( $attr['ids'] ) ) {
		// 'ids' is explicitly ordered, unless you specify otherwise.
		if ( empty( $attr['orderby'] ) )
			$attr['orderby'] = 'post__in';
		$attr['include'] = $attr['ids'];
	}

	// Allow plugins/themes to override the default gallery template.
	$output = apply_filters('post_gallery', '', $attr);
	if ( $output != '' )
		return $output;

	// We're trusting author input, so let's at least make sure it looks like a valid orderby statement
	if ( isset( $attr['orderby'] ) ) {
		$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
		if ( !$attr['orderby'] )
			unset( $attr['orderby'] );
	}

	extract(shortcode_atts(array(
		'order'	  => 'ASC',
		'orderby'	=> 'menu_order ID',
		'id'		 => $post->ID,
		'itemtag'	=> 'dl',
		'icontag'	=> 'dt',
		'captiontag' => 'dd',
		'columns'	=> 3,
		'size'	   => 'thumbnail',
		'include'	=> '',
		'exclude'	=> '',
		'imgw' => false,
		'imgh' => false,
		'linkimgw' => false,
		'linkimgh' => false,
		'album' => false,
		'nolinks' => false,
	), $attr));


	// print_r( $attr );
	// var_dump( $album );

	// for albums, we need to associate the thumbs (ids) with their parent galleries (gals)
	if ( isset( $album ) && isset( $attr['gals'] ) && isset( $attr['ids'] ) )
		$pairs = array_combine( explode( ',', $attr['ids'] ), explode( ',', $attr['gals'] ) );

	if ( $imgw && $imgh ) {
		$size = "_{$imgw}x{$imgh}";
	}
	if ( $linkimgw && $linkimgh ) {
		$linksize = "_{$linkimgw}x{$linkimgh}";
	}

	$id = intval($id);
	if ( 'RAND' == $order )
		$orderby = 'none';

	if ( !empty($include) ) {
		$_attachments = get_posts( array('include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );

		$attachments = array();
		foreach ( $_attachments as $key => $val ) {
			$attachments[$val->ID] = $_attachments[$key];
		}
	} elseif ( !empty($exclude) ) {
		$attachments = get_children( array('post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
	} else {
		$attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
	}

	if ( empty($attachments) )
		return '';

	if ( is_feed() ) {
		$output = "\n";
		foreach ( $attachments as $att_id => $attachment )
			$output .= wp_get_attachment_link($att_id, $size, true) . "\n";
		return $output;
	}

	$itemtag = tag_escape($itemtag);
	$captiontag = tag_escape($captiontag);
	$icontag = tag_escape($icontag);
	$valid_tags = wp_kses_allowed_html( 'post' );
	if ( ! isset( $valid_tags[ $itemtag ] ) )
		$itemtag = 'dl';
	if ( ! isset( $valid_tags[ $captiontag ] ) )
		$captiontag = 'dd';
	if ( ! isset( $valid_tags[ $icontag ] ) )
		$icontag = 'dt';

	$columns = intval($columns);
	$itemwidth = $columns > 0 ? floor(100/$columns) : 100;
	$float = is_rtl() ? 'right' : 'left';

	$selector = "gallery-{$instance}";

	$gallery_style = $gallery_div = '';
	if ( ( is_admin() && !defined('DOING_AJAX') ) || apply_filters( 'use_default_gallery_style', true ) )
		$gallery_style = "
		<style type='text/css'>
			#{$selector} {
				margin: auto;
			}
			#{$selector} .gallery-item {
				float: {$float};
				margin-top: 10px;
				text-align: center;
				width: {$itemwidth}%;
			}
			#{$selector} img {
				border: 2px solid #cfcfcf;
			}
			#{$selector} .gallery-caption {
				margin-left: 0;
			}
		</style>
		<!-- see gallery_shortcode() in wp-includes/media.php -->";
	$size_class = sanitize_html_class( $size );
	$album_class = $album ? ' album' : '';
	$gallery_div = "<div id='$selector' class='gallery galleryid-{$id} gallery-columns-{$columns} gallery-size-{$size_class}{$album_class}'>";
	$output = apply_filters( 'gallery_style', $gallery_style . "\n\t\t" . $gallery_div );

	$i = 0;
	foreach ( $attachments as $id => $attachment ) {
		// don't add_image_size for reserved name, breaks things
		$reserved = explode(',', 'thumb,thumbnail,medium,large,post-thumbnail' );

		if ( ! in_array( $size, $reserved ) )
			add_image_size( $size, $imgw, $imgh, true );

		maybe_create_missing_intermediate_images( $id, $size );

		$link = isset($attr['link']) && 'file' == $attr['link'] ? wp_get_attachment_link($id, $size, false, false) : wp_get_attachment_link($id, $size, true, false);

		// if viewing a gallery, check if we should link to a specific file size
		if ( !$album && isset( $linksize ) ) {
			// again, careful about reserved names
			if ( ! in_array( $linksize, $reserved ) )
				add_image_size( $linksize, $linkimgw, $linkimgh, true );
			maybe_create_missing_intermediate_images( $id, $linksize );
			//get url of custom sized image
			$href = array_shift( wp_get_attachment_image_src( $id, $linksize ) );
			//swap it in
			$link = str_replace( wp_get_attachment_url( $id ), $href, $link );
		}

		// if viewing album
		if ( $album ) {
			// swap the link out
			$url = add_query_arg( array(
					'showgallery' =>  $pairs[$id],
					) ). "#albumgal-{$pairs[$id]}";
			$link = str_replace( wp_get_attachment_url( $id ), $url, $link );
		}

		// for easier lightboxing
		if ( apply_filters( 'easy_album_insert_rel', true ) )
			$link = str_replace( 'a href', "a rel='{$selector}' href", $link );

		if ( $nolinks )
			$link = strip_tags( $link, '<img>' );

		$output .= "<{$itemtag} class='gallery-item'>";
		$output .= "
			<{$icontag} class='gallery-icon'>
				$link
			</{$icontag}>";

		if ( $album && $captiontag ) {
			// captions in album-mode should be our gallery names
			$output .= "
				<{$captiontag} class='wp-caption-text gallery-caption'>
				" . get_the_title( $pairs[$id] ) . "
				</{$captiontag}>";
		} else if ( $captiontag && trim($attachment->post_excerpt) ) {
			// core behavior
			$output .= "
				<{$captiontag} class='wp-caption-text gallery-caption'>
				" . wptexturize($attachment->post_excerpt) . "
				</{$captiontag}>";
		}
		$output .= "</{$itemtag}>";
		if ( $columns > 0 && ++$i % $columns == 0 )
			$output .= '<br style="clear: both" />';
	}

	$output .= "
			<br style='clear: both;' />
		</div>\n";

	return $output;
}

//eof