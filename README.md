# Astra Sites - Lite #
**Contributors:** [brainstormforce](https://profiles.wordpress.org/brainstormforce)  
**Donate link:** https://wpastra.com/pro/  
**Tags:** demo, theme demos, one click import  
**Requires at least:** 4.4  
**Tested up to:** 4.8.1  
**Stable tag:** 1.0.13  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Import Astra Sites - Lite with just one click.

## Description ##

This plugin is an add-on for the Astra WordPress Theme. It offers a library of ready sites that can be imported for your website easily. Here is how it works:

1. Browse through the library of ready sites right from your WordPress backend.
2. Pick a site you like.
3. Install required plugins in one click
4. Import the site data.
5. Done!

Use this imported site as a base for your project and don't waste time starting from scratch.

Some of the currently available sites for import:

https://sites.wpastra.com/gardener-free/
https://sites.wpastra.com/hotel-free/
https://sites.wpastra.com/agency-free/
https://sites.wpastra.com/restaurant-free/
https://sites.wpastra.com/construction-free/
https://sites.wpastra.com/makeup-artist-free/
https://sites.wpastra.com/electrician-free
https://sites.wpastra.com/law-free/

## Installation ##

1. Upload the plugin files to the `/wp-content/plugins/astra-sites` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Appearance->Astra->Astra Free Sites to select the page to be displayed as header and footer.

## Screenshots ##
1. Select the demo you want to import.
2. Install and activate the required plugins.
3. Import the demo.

## Changelog ##

v1.0.14 - Unreleased
* New: Categorized AJAX calls while importing Astra Site.
* Improvement: Removed LazyLoad which is not useful in admin back-end for showing Astra Sites.

v1.0.13 - 9-Oct-2017
* New: Browsing the Astra Sites in the Admin panel is not faster with JS rendering.

v1.0.12 - 29-Sept-2017
* New: Added White Label support from <a href="https://wpastra.com/pro/">Astra Pro</a>.
* Improvement: Don't display sites from both the page builders in the same view.
* Fix: Astra Sites admin area not working in the Firefox.

v1.0.11 - 22-Sept-2017
* New: Single click Install & activate required plugins.
* New: Added filter `astra_sites_menu_item` for adding extra tabs in admin page.
* New: Added back image import feature for `elementor` page builder. In batch image import we import all images from astra site into client site.
* Improvement: Updated JS code with object prototype.
* Fix: Screen bounce on retina devices.

v1.0.10 - 11-Sept-2017
* Improvement: Added support for retina logo import.
* Fix: Site logo image not displayed in customizer.
* Fix: Updated `Astra Agency` purchase link.

v1.0.9 - 8-Sept-2017
* New: Added page builder categories for listing sites as per page builder.

v1.0.8 - 6-Sept-2017
* Fix: Beaver Builder option import.
* Enhancement: Disabled dismiss-able notice visible once for each user.
* Enhancement: Showing error message for for user who have not `manage_plugins` capability.

v1.0.7 - 1-Sept-2017
* Fix: Custom Menu widget not setting imported widget.

v1.0.6 - 30-Aug-2017
* New: Addd custom menu for Astra Sites.
* Fix: Validate site options before storing in database.

v1.0.5 - 29-Aug-2017
* New: Added filter `astra_sites_api_args` for adding extra arguments in api call.
* Enhancement: Plugin name updated from `Astra Sites` with `Astra Free Sites`.
* Fix: PHP error while ignoring users.

v1.0.4 - 21-Aug-2017
* New: Added filter `astra_sites_api_params` for adding extra params in api call.
* New: Added filter `astra_sites_api_args` for adding extra arguments in api call.
* New: Added filter `astra_sites_category_hide_empty` for showing categories which are not set for any site.

v1.0.3 - 11-Aug-2017
* Fix: Avoided Astra users from site import process.

v1.0.2 - 09-Aug-2017
* Fix: Listing appropriate next and previous Astra sites.
* Enhancement: Listing Astra sites though AJAX API call.

v1.0.1 - 04-Aug-2017
* New: Added Elementor plugin options support.
* New: Added Customizer CSS support.
* Enhancement: Avoided Lite Plugin version if Pro version is Installed. Now added support for Beaver Builder Plugin (Lite Version).
* Enhancement: Astra sites API call validated before import.
* Enhancement: Site logo imported from Astra sites.
* Fix: Bug where widgets created with SiteOrigin plugin were not being imported.

v1.0.0
* Initial release

