=== Beautiful Stone Walls ===
Version: 1.2.0
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
License: GNU General Public License v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: beautiful-stone-walls

== Description ==

A responsive English/Latvian stonework portfolio using native WordPress pages,
posts, categories, media and the block editor. No page builder or build step.

This is a complete standard WordPress theme package. It does not include the
database, uploaded project photographs, plugins or an UpdraftPlus backup.
The existing bilingual content and media must already be present. Polylang is
needed for bilingual content and matching language links; Contact Form 7 powers
the existing enquiry forms. Yoast SEO integration is optional. Installed plugin
versions may require newer WordPress/PHP versions than the theme minimums above.

The complete repository, editing and release instructions are in README.md.
Repository: https://github.com/raivis-kalnins/wp-beautiful-stone-walls--theme

== Installation / replacement ==

1. Keep a current backup of the site, including its database and uploads.
2. Open Appearance > Themes > Add New Theme > Upload Theme.
3. Select beautiful-stone-walls-1.2.0.zip and click Install Now.
4. Choose Replace current with uploaded, or activate after a first installation.
5. Visit WordPress Admin as an administrator to run the one-time contact update.
   Resolve any update notice, clear site/hosting/CDN/browser caches, and check EN/LV.

Keep the theme folder named beautiful-stone-walls. No database restore is needed
for this update. Do not import this theme ZIP as an UpdraftPlus component.
There is no automatic GitHub updater; replace future releases through Admin.

== Version 1.2.0 ==

- Plain flag images without visible EN/LV text, circles, borders or backgrounds.
  Accessible labels, keyboard focus and 44-pixel tap targets remain.
- Visible header contact buttons in both languages: Contact us / Sazināties.
- Requested telephone 0000 and email hello@dev4.click; country Ireland in English
  and Latvija in Latvian, editable after the one-time contact update.
- Optional website password under Appearance > Site Privacy.
- Retained centered SVG gallery plus icons, image/caption alignment, clean
  gallery gradients, filters, load-more controls and keyboard lightbox behavior.
- Retained responsive images, eager main hero, deferred JavaScript, system fonts,
  conditional Contact Form 7 assets and native Polylang/Yoast integration.
- Includes screenshot.png (1200 x 900), original logo and fallback brand favicons.

== One-time contact update ==

An administrator's next WordPress Admin visit triggers the versioned admin_init
migration. It applies the requested contact defaults once and clears old address
and service-area overrides so each language displays its country.

Known legacy contact/location wording is updated only in the current home,
about and contact summaries, their EN/LV translations and associated Yoast fields.
Known old values in the configured enquiry form and its translation are replaced;
known legacy Yoast organization contacts are updated when Yoast is active.
Existing projects, historical descriptions and review attributions are preserved.

An administrator notice reports an unfinished update so it can retry later.
A checkpoint prevents retries from resetting newly edited contact settings.
See inc/contact-update.php for the exact migration scope.

== Editing and languages ==

Edit each language's native Pages/Posts and menus using WordPress and Polylang.
The existing setup uses English at the root and Latvian under /lv/; this theme
does not import content or reset language configuration.

Use Appearance > Customize > Stone Walls · Contact & homepage for countries,
telephone, email, address, service area and homepage text. Blank address/service
area fields display the current language's country. Registered translations and
review excerpts are under Languages > Translations > Beautiful Stone Walls.
Static theme translations are in languages/lv.po and languages/lv.mo.

Review recipients separately in Contact > Contact Forms > [form] > Mail.
Later Customizer email changes do not automatically change form recipients.

== Site Privacy ==

Open Appearance > Site Privacy as an administrator. On first use, protection is
off and no password is set. Set your own website password before enabling it.
Leave the password field blank on later saves to keep the stored password.
Leading/trailing spaces are ignored; the saved password is never displayed.

Visitors unlock dynamic WordPress content with a signed browser cookie valid
for 7 days. Changing the password or protection setting invalidates old access.
Passwords are stored as hashes. Settings survive theme replacements, but the
protection operates only while this theme is active. WordPress login and native
password recovery remain reachable; authorized administration/editing continues.

Before enabling protection, disable or bypass full-page caching and clear any
hosting, cache-plugin and CDN caches. Responses served before WordPress may
bypass the gate. Direct static uploads, image URLs and other web-server files
are outside its scope. Use hosting/server protection if these must be private.
Check the signed-out experience in a private browser after changing settings.

== SEO and favicon ==

Yoast owns its titles, descriptions, canonicals, social tags and schema graph;
the theme extends its organization node. Per-page metadata remains editable in
Yoast. The placeholder 0000 is omitted from business telephone schema.
Search visibility follows WordPress settings; installing the theme does not
enable indexing. Privacy mode requests no indexing/caching for protected routes.

The fallback favicon uses the original brand. A custom WordPress Site Icon takes
precedence. Browser favicon caches may temporarily show the previous icon.

== Development ==

Put theme files directly at the Git repository root, not inside a second theme
folder. Review and commit changes before packaging; HEAD excludes uncommitted work.
From the repository root:

git archive --format=zip --prefix=beautiful-stone-walls/ --output=../beautiful-stone-walls-1.2.0.zip HEAD

Keep the prefix for future versions; change the ZIP filename as appropriate.
Do not commit databases, uploads, local configuration or credentials.
