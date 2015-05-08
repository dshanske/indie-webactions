# Indie Webactions #
**Contributors:** dshanske  
**Tags:** indieweb, interaction, posts, webactions  
**Stable tag:** 0.20  
**Requires at least:** 4.2  
**Tested up to:** 4.2  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Adds a quick interface for simple notes and bookmarks. Enhanced by the Indieweb Post Kinds plugin.

## Description ##

A [web action](http://indiewebcamp.com/webactions) is the interface and user experience of taking a specific discrete action, across the web, from one site to another site or application. 

This adds the ability to create a post directly from the URL bar.

This requires you are logged into WordPress as a user who has permission to
create posts. The posts are private by default.

By default, it allows note and bookmark posts to be made. If you have the [Indieweb Post Kinds](https://wordpress.org/plugins/indieweb-post-kinds/) plugin enabled, it will also allow like, favorite, reply and repost.

It also supports a menu option to link to support options, and [indie-config](http://indiewebcamp.com/indie-config). 

**Example:** http://example.com?indie-action=like&url=http%3A%2F%2Fexample2.com%2Ftest%2F&title=Example  

* indie-action - such as bookmark, note, etc.
* url - the URL of what you are responding to
* name - the post title/the title of the URL
* excerpt - An excerpt of the content (optional)
* public - if you want the post public (optional)

If you don't add a URL, it will display a form you can fill in. Otherwise, it endeavors to pull information on the site you are linking to if you do 
not provide it.

## Frequently Asked Questions ##

# How does this differ from Press This? #

In WordPress 4.2, WordPress revealed a significant upgrade to the built-in
Press This functionality which is a significant change. 

This is an alternative to that, built with various Indieweb conventions and
built to work with the Indieweb Post Kinds plugin and is built for simplicity.

# What are web actions? #

Web actions allow you to wrap sharing buttons in markup that would allow others
to click like, reply, or repost on your site, and post a reply seamlessly on
theirs. 

 <indie-action do="post" with="permalink">
   <a href=twitter share link for example>..</a>
   ...
  </indie-action>

This plugin wraps the built-in comment replies in this markup(Credit to [Matthias Pfefferle](https://github.com/pfefferle) for the initial webaction code for 
this). You can optionally add additional buttons.

# What is indie-config? #

Indie-config is a method of using protocol handler to setup your website to bothnotify the browser that it can handle webactions and then do so.

This plugin uses javascript written by [Pelle Wessman](http://voxpelli.com) for the protocol handling.

## Other Notes ##

If you want to make this a bookmarklet to quickly save to your site, change to
your site's address and create a bookmark with below.

javascript:location.href='http://example.com/?indie-action=bookmark&url='+encodeURIComponent(location.href)+'&title='+encodeURIComponent(document.title)

## Changelog ##

* Version 0.2.0 - Supports Web Action handlers
* Version 0.1.0 - Initial release
