=== WP-Subversion ===
Contributors: dancoulter
Tags: svn, post
Requires at least: 2.3
Tested up to: 2.7
Stable tag: 0.1.1

Automatically post commit messages from your public Subversion repository.

== Description ==

This is a very simple and straightforward plugin to let you automatically blog
the commit messages from your public Subversion repository.  For now, only one
project is supported.

Currently, it requires that PHP have shell access to the "svn" command.  This
means that it has to run on a *nix server with subversion installed.  If you're
not sure whether your server supports this, try typing "svn info" at your shell.
If it says that it cannot find the program "svn", you don't have it installed.

== Installation ==

Simply drop the files into a folder in your WordPress plugins folder.  Once
you've activated the plugin, go to Settings -> WP-Subversion in your WordPress
admin panel and set up your project. 