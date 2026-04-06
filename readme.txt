=== Virtual Try-On for WooCommerce – Preview AI ===
Contributors: previewai
Donate link: https://previewai.app/
Tags: virtual try-on, ai, woocommerce, ecommerce, conversion
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
WC requires at least: 8.0
WC tested up to: 10.5.1
Stable tag: 1.4.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Virtual try-on for WooCommerce that helps fashion stores increase conversions and reduce returns.

== Description ==

https://youtu.be/CvPFB16q24U

Preview AI is an AI-powered Virtual Try-On plugin for WooCommerce that helps fashion stores increase conversion rates and reduce returns by allowing customers to preview how a product may look on them before buying 👕✨

With a simple “Virtual Try-On” button on your product pages, shoppers can upload a photo and generate a realistic visual preview directly within the product experience — without leaving your store or disrupting the checkout flow.

Preview AI is built to remove hesitation at the most critical moment of the buying journey: the product page.

== Built to increase conversion on product pages 🚀 ==

Many fashion shoppers hesitate not because of price, but because they can’t picture themselves wearing the product.

Preview AI addresses this hesitation by adding a visual confirmation layer that helps shoppers feel more confident in their decision before adding to cart.

By giving customers a clearer expectation of how the product may look on them, Preview AI often leads to:
* Higher add-to-cart rates
* More confident purchases
* Improved product page engagement

== Reduce returns caused by uncertainty 📉 ==

Returns in fashion ecommerce are often caused by unmet expectations around style and appearance.

Preview AI helps reduce these returns by aligning expectations before purchase. Customers can visually assess how a garment may look on them, complementing your existing product photos and size charts.

This leads to fewer surprise purchases and more intentional buying decisions — without claiming perfect fit or exact sizing.

== Benefits for merchants ==

* 📈 Increase conversion rates on fashion product pages
* 🔄 Reduce returns related to style and appearance uncertainty
* 👀 Build shopper confidence without altering the checkout flow
* ⚡ Keep your store fast with external AI processing
* 🎨 Fully customize the Virtual Try-On button (color, text, placement)
* 📊 Track performance with built-in analytics and conversion insights

== Analytics & conversion tracking 📊 ==

Preview AI includes an analytics panel designed to help merchants measure real impact.

Track:
* How many shoppers use the Virtual Try-On feature
* How many previews are successfully generated
* Conversion performance of users who interacted with Virtual Try-On
* Feature adoption across your product catalog

This allows you to understand not just usage, but how Virtual Try-On contributes to conversions — helping you optimize product pages and justify ROI.

== Fully customizable & theme-friendly 🎨 ==

Preview AI is designed to work seamlessly with any WooCommerce theme.

Customize:
* Button color and styling to match your brand
* Button text and labels
* Button position on the product page
* Placement via WooCommerce hooks, shortcode, or Elementor widget

You can start with the default setup and progressively customize the experience without touching code.

== Features ==

– AI-powered virtual try-on from a single photo
– Supports:
  – Upper-body garments (t-shirts, shirts, jackets, hoodies)
  – Lower-body garments (pants, skirts)
  – Dresses and full-body outfits
  – Shoes
– Virtual Try-On button on WooCommerce product pages
– Elementor widget + shortcode support
– Customizable button (color, text, position)
– Analytics dashboard with usage & conversion insights
– Automatic product type classification
– Mobile-first, responsive design
– Performance-friendly external AI architecture

== External Service Disclosure ==

This plugin connects to an external service operated by Preview AI.

Service Provider: Preview AI
Service URL: https://previewai.app
Terms of Service: https://www.previewai.app/terms/terms-and-conditions
Privacy Policy: https://www.previewai.app/terms/privacy-policy

Data transmitted to the service:

1. **Virtual try-on generation** — Product images and a customer-uploaded photo are sent to generate the try-on preview. Customer photos are processed in real time and are not stored permanently.
2. **Conversion attribution (opt-in)** — When enabled in Settings > General > Advanced Analytics, anonymized order metadata (order ID, order total, currency, and product IDs) is sent when an order is completed or refunded to measure the impact of virtual try-on on sales. This feature is disabled by default and requires explicit activation by the store administrator. No customer personal data (name, email, address, or IP) is transmitted.

All communication is authenticated via the store's API key and transmitted over HTTPS. A free usage tier is included so the plugin is functional immediately after activation.

== Compatibility ==

– WooCommerce: Optimized for current versions
– Elementor: Includes a widget for custom product templates
– Themes: Compatible with Astra, Hello Elementor, OceanWP, and most WooCommerce themes
– Responsive across all modern devices

== Installation ==

1. Upload the `preview-ai` folder to the `/wp-content/plugins/` directory
2. Activate the plugin from the WordPress Plugins menu
3. Complete the onboarding wizard to connect your store
4. The Virtual Try-On button appears automatically on supported products

== Frequently Asked Questions ==

= Is the plugin free? =

Yes.
The plugin is free and includes a free tier of the Preview AI service.

Optional paid plans are available for stores with higher usage needs.

= Why does it use an external service? =

AI image generation requires specialized infrastructure that typical web hosting environments do not provide.

Using an external service allows Preview AI to deliver better results without affecting your site’s performance.

= Are customer photos stored? =

Customer images are processed in real time to generate the try-on preview.

They are not stored permanently and are handled according to the Preview AI privacy policy.

= Which products are supported? =

Preview AI currently supports:
– Tops
– Bottoms
– Dresses and full outfits
– Shoes

== Screenshots ==

1. Virtual Try-On button on the product page
2. AI virtual try-on generation flow
3. Admin settings and analytics overview
4. Product-level configuration options

== Changelog ==

= 1.4.0 =
– Stable release. Sends the WordPress site locale on registration so the Preview AI service can align transactional emails with the store language.

= 1.3.2 =
– Added smart plan recommendation: the settings page now shows a contextual upgrade suggestion when your monthly previews are running low, so your customers never miss a try-on.

= 1.3.1 =
– Fixed widget visibility: the try-on button no longer disappears due to account status changes. Errors are shown at generation time instead.

= 1.3.0 =
– Added automatic product analysis on publish: Preview AI now activates automatically for supported products when they are first published.
– Improved onboarding experience by reducing manual steps for new products.

= 1.2.1 =
– Renamed plugin to "Virtual Try-On for WooCommerce – Preview AI"
– Added WooCommerce as a required plugin dependency (Requires Plugins header)
– Added optional deactivation feedback survey to help us improve the plugin

= 1.2.0 =
– Added optional Advanced Analytics (opt-in): link try-on usage to purchases for accurate ROI measurement.
– Session attribution: each virtual try-on session can be tracked end-to-end, from preview to checkout.

= 1.1.1 =
– Fixed nonce validation failing on sites with page caching (WP Rocket, LiteSpeed Cache, W3 Total Cache, etc.), which caused "Something went wrong" errors on every try-on request.

= 1.1.0 =
– Added Bulk Actions to enable or disable Preview AI for multiple products at once.
– Added filtering by Preview AI status (Active, Disabled, Not Analyzed, Not Supported) in the product list.
– Added sorting capability for the Preview AI column in the product list.
– Improved scalability for large catalogs using background processing for bulk activation.

= 1.0.4 =
– Added full internationalization support (i18n)
– Complete translation catalog (.pot) with 130+ translatable strings
– Improved pose validation feedback messages

= 1.0.2 =
– Added weekly preview limit per visitor (configurable in Settings > General)
– Default limit: 8 previews per visitor per week

= 1.0.1 =
– Updated plugin name to "Virtual Try-On for WooCommerce – Preview AI"

= 1.0.0 =
– Initial release
– AI virtual try-on for WooCommerce
– Elementor and WooCommerce integration
– Basic analytics and email capture

== Upgrade Notice ==

= 1.4.0 =
Stable release: registration now includes site locale for service email language alignment.

= 1.3.2 =
New: contextual upgrade banner when previews are running low.

= 1.3.1 =
Fixed: try-on button no longer disappears silently when account status changes.

= 1.3.0 =
Automatic product analysis on publish: Preview AI now activates automatically for supported products when they are first published.

= 1.2.1 =
Plugin renamed, WooCommerce declared as required dependency, and added deactivation feedback survey.

= 1.2.0 =
Adds end-to-end conversion attribution: see exactly how virtual try-on impacts your sales.

= 1.1.1 =
Fixes "Something went wrong" errors on sites with page caching enabled.

= 1.1.0 =
New bulk actions and filtering options for easier catalog management.

= 1.0.0 =
Initial stable release.
