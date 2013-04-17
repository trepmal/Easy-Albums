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
			var t = this;

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
				//title : 'Insert Album',
				title: ed.getLang( 'easyalbums.insertalbum' ),
				cmd : 'mcealbums',
				image : url + '/icon.gif'
			});

			ed.onBeforeSetContent.add(function(ed, o) {
				o.content = t._do_gallery(o.content);
			});

			ed.onPostProcess.add(function(ed, o) {
				if (o.get)
					o.content = t._get_gallery(o.content);
			});

		},

		_do_gallery : function(co) {
			return co.replace(/\[album([^\]]*)\]/g, function(a,b){
				return '<img src="'+tinymce.baseURL+'/plugins/wpgallery/img/t.gif" style="border: 1px dashed #888;background: #f2fff8 no-repeat scroll center center;width: 99%;height: 250px" class="wpAlbum mceItem" title="album'+tinymce.DOM.encode(b)+'" />';
			});
		},

		_get_gallery : function(co) {

			function getAttr(s, n) {
				n = new RegExp(n + '=\"([^\"]+)\"', 'g').exec(s);
				return n ? tinymce.DOM.decode(n[1]) : '';
			};

			return co.replace(/(?:<p[^>]*>)*(<img[^>]+>)(?:<\/p>)*/g, function(a,im) {
				var cls = getAttr(im, 'class');

				if ( cls.indexOf('wpAlbum') != -1 )
					return '<p>['+tinymce.trim(getAttr(im, 'title'))+']</p>';

				return a;
			});
		},

	});

	// Register plugin
	tinymce.PluginManager.add('albums', tinymce.plugins.albums);
})();