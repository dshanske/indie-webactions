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

* indie-action - such as bookmark, note, etc.
* url - the URL of what you are responding to
* title - the post title
* text - An excerpt of the content (optional)
* public - if you want the post public (optional)

If you don't add a URL, it will display a form you can fill in. Otherwise, it endeavors to pull information on the site you are linking to if you do 
not provide it.

By default, it allows note and bookmark posts to be made. If you have the [Indieweb Post Kinds](https://wordpress.org/plugins/indieweb-post-kinds/) plugin enabled, it will also allow like, favorite, reply and repost.

== Other Notes ==

If you want to make this a bookmarklet to quickly save to your site, change to
your site's address and create a bookmark with below.

javascript:location.href='http://example.com/?indie-action=like&url='+encodeURIComponent(location.href)+'&title='+encodeURIComponent(document.title)

== Changelog == 

* Version 0.2.0 - Supports Web Action handlers
* Version 0.1.0 - Initial release
