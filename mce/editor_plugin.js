// http://tinymce.moxiecode.com/wiki.php/API3:class.tinymce.Plugin

(function() {
	
	tinymce.create('tinymce.plugins.albums', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished its initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {

			//this command will be executed when the button in the toolbar is clicked
			ed.addCommand('mcealbums', function() {
				ed.windowManager.open({
				    // call content via admin-ajax, no need to know the full plugin path
					file : ajaxurl + '?action=albums_tinymce',
					width : 360 + ed.getLang('albums.delta_width', 0),
					height : 210 + ed.getLang('albums.delta_height', 0),
					inline : 1
				}, {
					plugin_url : url // Plugin absolute URL
				});
			});

			ed.addButton('albums', {
				title : 'albums.desc',
				cmd : 'mcealbums',
				image : url + '/icon.gif'
			});

		},

	});

	// Register plugin
	tinymce.PluginManager.add('albums', tinymce.plugins.albums);
})();