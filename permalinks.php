<?php
if ( ! defined('ABSPATH') )
	die('-1');

$galleries_and_albums_permalinks = new Galleries_and_Albums_Permalinks();

class Galleries_and_Albums_Permalinks {

	function __construct() {
		add_action( 'wp_loaded', array( &$this, 'wp_loaded' ) );
		add_filter( 'rewrite_rules_array', array( &$this, 'rewrite_rules_array' ) );
		add_filter( 'query_vars', array( &$this, 'query_vars' ) );
		add_action( 'pre_get_posts', array( &$this, 'pre_get_posts' ) );
	}

	// flush_rules() if our rules are not yet included
	function wp_loaded(){
		$rules = get_option( 'rewrite_rules' );

		if ( ! isset( $rules['(.+?)/album/(\d*)/([^/]*)/?$'] ) ) {
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
		}
	}

	// Adding a new rule
	function rewrite_rules_array( $rules ) {
		$newrules = array();
		$newrules['(.+?)/album/(\d*)/([^/]*)/?$'] = 'index.php?pagename=$matches[1]&ea_album_id=$matches[2]&ea_gallery_name=$matches[3]';
		return $newrules + $rules;
	}

	// Adding the id var so that WP recognizes it
	function query_vars( $vars ) {
		array_push( $vars, 'ea_album_id' );
		array_push( $vars, 'ea_gallery_name' );
		return $vars;
	}

	function pre_get_posts( $query ) {
		if ( is_admin() ) return;

		// don't bother doing anything here if there necessary query vars aren't set
		if ( ! isset( $query->query_vars['ea_album_id'] ) || ! isset( $query->query_vars['ea_gallery_name'] ) ) return;

		$albumid = $query->get('ea_album_id');
		$album = get_post( $albumid );
		// make sure we have a valid album
		if ( is_null( $album ) || $album->post_type != 'easy_album' ) {
			status_header( 404 );
			$query->set_404();
			return;
		}

		$galname = $query->get('ea_gallery_name');
		$gallery = get_page_by_path( $galname, OBJECT, 'easy_gallery' );

		// make sure we have a valid gallery
		// should also check that gallery is in said album
		if ( is_null( $gallery ) ) {
			status_header( 404 );
			$query->set_404();
			return;
		}
		$_GET['showgallery'] = $gallery->ID;
	}
}

//eof