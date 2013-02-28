Easy-Albums
===========

An easy album manager for WordPress


Overview
--------
* In wp-admin/ go to Galleries in the admin menu.
* Create a gallery by using the Add Media button to select image and insert a [gallery] shortcode
* Save.
* Go to Albums in the admin menu
* Create an album by checking of the galleries you'd like to include
* Drag the galleries as needed to rearrange them
* Save.
* Copy the provided shortcode.
* In any post/page, paste in the shortcode where you want your gallery to appear.
* Or, use the new toolbar button (looks like 4 squares) to select from any available albums.

Widgets
-------
You can display any created gallery in a sidebar by using the Display Gallery widget.

You can display any created album in a sidebar by using the Display Album widget. There are quirks if the album in the widget is displayed elsewhere on the page.

Ajax
----
There is an optional, and still somewhat quirky, ajax layer available. To turn it on, uncomment the appropriate `require` line at the top of `galleries-albums.php`.