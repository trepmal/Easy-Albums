<?php
if ( ! defined('ABSPATH') )
	die('-1');

@header( 'Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset') );
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php _e( 'Select Album', 'easy-albums' ); ?></title>
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
	<script language="javascript" type="text/javascript" src="<?php echo includes_url('js/tinymce/tiny_mce_popup.js'); ?>"></script>
	<base target="_self" />
</head>
<script type="text/javascript">
function insertLink(evt) {

	var tagtext;

	//get the form values
	var album_id = document.getElementById('albums').value;

	//double-check that our album id is set and setup shortcode
	if (album_id != 0 )
		tagtext = '[album id='+ album_id +']';
	else
		tinyMCEPopup.close();

	if(window.tinyMCE) {
		//send the shortcode to the editor
		window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, tagtext);
		//Peforms a clean up of the current editor HTML.
		tinyMCEPopup.editor.execCommand('mceCleanup');
		//Repaints the editor. Sometimes the browser has graphic glitches.
		tinyMCEPopup.editor.execCommand('mceRepaint');
		//close the popup window
		tinyMCEPopup.close();
	}
	return;
}
</script>
<body id="link" style="display: none">
	<form action="#">
		<div class="tabs">
			<ul>
				<li class="current"><span><?php _e( 'Select an album', 'easy-albums'); ?></span></li>
			</ul>
		</div>

		<div class="panel_wrapper">
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td>
				<?php
				$allalbums = get_posts( 'post_type=easy_album&numberposts=-1' );
				if ( ! empty( $allalbums ) ) {
					echo '<select id="albums" name="albums">';

					foreach( $allalbums as $a ) {
						$title = get_the_title( $a->ID );
						if ( empty( $title ) ) $title = __( 'no title', 'easy-albums' );
						echo "<option value='{$a->ID}'>{$title}</option>";
					}

					echo '</select>';
				} else {
					echo '<p>';
					_e( 'You haven\'t created any albums yet', 'easy-albums' );
					echo '</p>';
				}
				?>
				</td>
			</tr>
		</table>

		</div>

		<div class="mceActionPanel">
			<div style="float: left">
				<input type="button" id="cancel" name="cancel" value="<?php _e( 'Cancel', 'easy-albums'); ?>" onclick="tinyMCEPopup.close();" />
			</div>

			<div style="float: right">
				<input type="submit" id="insert" name="insert" value="<?php _e('Insert', 'easy-albums'); ?>" onclick="insertLink(event);" />
			</div>
		</div>
	</form>
</body>
</html>