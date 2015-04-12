=== Indie Webactions ===
Contributors: dshanske
Tags: indieweb, interaction, posts, webmention
Stable tag: 0.1.0
Requires at least: 4.0
Tested up to: 4.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A

== Description == 

A web action is the interface and user experience of taking a specific discrete action, across the web, from one site to another site or application. 

This adds the ability to create a post directly from the URL bar.

This requires you are logged into WordPress as a user who has permission to
create posts. The posts are private by default.

Example: http://example.com?indie-action=like&url=http%3A%2F%2Fexample2.com%2Ftest%2F&title=Example

* indie-action - reply, bookmark, like, favorite
* url - the URL of what you are responding to
* title - the post title
* text - An excerpt of the content (optional)
* public - if you want the post public (optional)

If you don't add a URL, it will display a form you can fill in.

If you want to make this a bookmarklet to quickly save to your site, change to
your site's address and create a bookmark with below.

javascript:location.href='http://example.com/?indie-action=like&url='+encodeURIComponent(location.href)+'&title='+encodeURIComponent(document.title)

== Changelog == 

* Version 0.1.0 - Initial release
