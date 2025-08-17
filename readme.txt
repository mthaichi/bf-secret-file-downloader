=== BF Secret File Downloader ===
Contributors: breadfish
Tags: download, basic auth, private files, file manager, security
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage and provide download functionality for files protected by Basic Authentication or located in private areas.

== Description ==

BF Secret File Downloader is a WordPress plugin that allows you to manage files located in directories protected by Basic Authentication or private areas. It provides comprehensive file management, directory management, and download functionality.

= Features =

* **File Management**: Browse and manage files in protected directories
* **Download Control**: Secure download functionality with access control
* **Access Control**: Multiple authentication methods including WordPress login and simple password
* **Directory Management**: Organize files in multiple protected directories
* **i18n Ready**: Translation ready with Japanese and English support

= Authentication Methods =

* WordPress user login (with role-based access)
* Simple password protection
* Combined authentication options

= Use Cases =

* Private document distribution
* Member-only file downloads
* Protected resource sharing
* Secure file access for clients

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/bf-secret-file-downloader` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Settings->BF Secret File Downloader screen to configure the plugin.
4. Set up your protected directories and authentication methods.

== Frequently Asked Questions ==

= What file types are supported? =

The plugin supports all common file types. You can configure allowed file extensions in the settings.

= How secure is the download functionality? =

The plugin implements multiple security layers including nonce verification, user authentication, and sanitized file paths to prevent unauthorized access.

= Can I use this with Basic Authentication? =

Yes, the plugin is specifically designed to work with directories protected by Basic Authentication or other server-level protections.

= Is it compatible with multisite? =

Currently, the plugin is designed for single-site installations. Multisite support is planned for future versions.

== Screenshots ==

1. Admin file list page showing protected files
2. Settings page with authentication options
3. Frontend download interface

== Changelog ==

= 1.0.0 =
* Initial release
* File management functionality
* Download control with authentication
* Multiple authentication methods
* i18n support for Japanese and English

== Upgrade Notice ==

= 1.0.0 =
Initial release of BF Secret File Downloader.

== Security ==

This plugin implements several security measures:

* Nonce verification for all admin actions
* Input sanitization and validation
* Path traversal protection
* Access control verification
* Direct file access prevention

== Support ==

For support and feature requests, please visit the plugin's support forum.