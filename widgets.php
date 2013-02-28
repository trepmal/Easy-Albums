<?php
if ( ! defined('ABSPATH') )
	die('-1');

// Widgets

add_action( 'widgets_init', 'register_easy_albums_widgets' );
function register_easy_albums_widgets() {
	register_widget( 'Galleries_Widget' );
	register_widget( 'Albums_Widget' );
}

class Galleries_Widget extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'galleries-widget', 'description' => __( 'Display Galleries' ) );
		$control_ops = array( );
		parent::WP_Widget( 'gallerieswidget', __( 'Display Gallery' ), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {

		extract( $args, EXTR_SKIP );
		echo $before_widget;

		echo $instance['hide_title'] ? '' : $before_title . $instance['title'] . $after_title;

		echo do_shortcode( get_post( $instance['gallery'] )->post_content );

		echo $after_widget;

	} //end widget()

	function update($new_instance, $old_instance) {

		$instance = $old_instance;
		$instance['hide_title'] = (bool) $new_instance['hide_title'] ? 1 : 0;
		$instance['gallery'] = intval( $new_instance['gallery'] );
		$instance['title'] = get_the_title( $instance['gallery'] );
		return $instance;

	} //end update()

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => 'Gallery', 'hide_title' => 0, 'gallery' => 0 ) );
		extract( $instance );
		?>
		<input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="hidden" value="<?php echo $title; ?>" />

		<p>
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('hide_title'); ?>" name="<?php echo $this->get_field_name('hide_title'); ?>"<?php checked( $hide_title ); ?> />
			<label for="<?php echo $this->get_field_id('hide_title'); ?>"><?php _e('Hide Title?' );?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'gallery' ); ?>"><?php _e( 'Gallery:' );?>
				<select id="<?php echo $this->get_field_id('gallery'); ?>" name="<?php echo $this->get_field_name('gallery'); ?>">
				<?php
				$allgalleries = get_posts( 'post_type=gallery&numberposts=-1' );
				foreach( $allgalleries as $g ) {
					$s = selected( $g->ID, $gallery, false );
					echo "<option value='{$g->ID}'$s>{$g->post_title}</option>";
				}
				?>
				</select>
			</label>
		</p>
		<?php

		// fell down a rabbit hole. might come back to this later.
		// http://core.trac.wordpress.org/ticket/23591
		/* wp_editor( '', 'ed_'.$this->id, array(
			'tinymce' => false,
			'quicktags' => false,
			'textarea_rows' => 1,
		) );
		?><script type="text/javascript">
jQuery(function($) {
	$(document.body).on( 'click', '.insert-media', function( event ) {
		wpActiveEditor = $(this).data('editor');
	});
});
</script><?php */

	} //end form()
}

class Albums_Widget extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'albums-widget', 'description' => __( 'Display Albums' ) );
		$control_ops = array( );
		parent::WP_Widget( 'albumswidget', __( 'Display Album' ), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {

		extract( $args, EXTR_SKIP );
		echo $before_widget;

		echo $instance['hide_title'] ? '' : $before_title . $instance['title'] . $after_title;

		echo do_shortcode( '[album id='.$instance['album'].' columns='.$instance['columns'].']' );

		echo $after_widget;

	} //end widget()

	function update($new_instance, $old_instance) {

		$instance = $old_instance;
		$instance['hide_title'] = (bool) $new_instance['hide_title'] ? 1 : 0;
		$instance['album'] = intval( $new_instance['album'] );
		$instance['title'] = get_the_title( $instance['album'] );
		$instance['columns'] = intval( $new_instance['columns'] );
		return $instance;

	} //end update()

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => 'Album', 'hide_title' => 0, 'album' => 0, 'columns' => 3 ) );
		extract( $instance );
		?>
		<input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="hidden" value="<?php echo $title; ?>" />

		<p>
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('hide_title'); ?>" name="<?php echo $this->get_field_name('hide_title'); ?>"<?php checked( $hide_title ); ?> />
			<label for="<?php echo $this->get_field_id('hide_title'); ?>"><?php _e('Hide Title?' );?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'album' ); ?>"><?php _e( 'Album:' );?>
				<select id="<?php echo $this->get_field_id('album'); ?>" name="<?php echo $this->get_field_name('album'); ?>">
				<?php
				$allalbums = get_posts( 'post_type=album&numberposts=-1' );
				foreach( $allalbums as $a ) {
					$s = selected( $a->ID, $album, false );
					$title = get_the_title( $a->ID );
					if ( empty( $title ) ) $title = 'no title';
					echo "<option value='{$a->ID}'>{$title}</option>";
				}
				?>
				</select>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'columns' ); ?>"><?php _e( 'Columns:' );?>
				<input type="text" id="<?php echo $this->get_field_id('columns'); ?>" name="<?php echo $this->get_field_name('columns'); ?>" value="<?php echo $columns; ?>" />
			</label>
		</p>
		<?php

	} //end form()
}

