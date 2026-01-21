=== Preview AI ===
Contributors: previewai
Donate link: https://previewai.app/
Tags: virtual try-on, woocommerce, ai, fashion, ecommerce
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
WC requires at least: 8.0
WC tested up to: 10.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

AI virtual try-on for WooCommerce. Let customers see themselves wearing your products before buying.

== Description ==

Preview AI is a powerful WooCommerce extension that enhances your store's user experience by letting customers see how they would look with your products. By using our specialized AI infrastructure, users can upload a photo and instantly see a high-quality preview of themselves wearing the selected garment.

This plugin is a "Serviceware" integration. It performs the heavy AI processing on our secure external servers (previewai.app) to ensure your WordPress site remains fast and performant. The virtual try-on functionality requires an active connection to our API.

**Service Requirements:**
This plugin connects to the Preview AI external service to perform AI image analysis and generation. 
* **Service Provider:** [Preview AI](https://previewai.app)
* **Terms of Service:** [https://www.previewai.app/terms/terms-and-conditions](https://www.previewai.app/terms/terms-and-conditions)
* **Privacy Policy:** [https://www.previewai.app/terms/privacy-policy](https://www.previewai.app/terms/privacy-policy)

The plugin includes a free tier for the Preview AI service, ensuring it is fully functional upon activation.

= Compatibility =
* **Elementor:** Fully compatible with a dedicated widget for your custom templates.
* **WooCommerce:** Optimized for the latest versions of WooCommerce.
* **Themes:** Compatible with Astra, Hello Elementor, OceanWP, and more.

= Features =
* AI-powered virtual try-on for tops, pants, and full-body clothing.
* Seamless WooCommerce integration.
* Secure image processing via Preview AI API.
* Automatic product classification.
* Fully customizable widget (text, icons, colors).

== Installation ==

1. Upload the `preview-ai` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Use the onboarding wizard to connect your store to the Preview AI service and analyze your catalog.

== Frequently Asked Questions ==

= Is it free? =
The plugin is free to use and includes a free tier for the Preview AI service. For high-volume stores, additional service plans are available on our website. All plugin features remain fully accessible regardless of your service plan level.

= Why does it use an external service? =
Generating high-quality AI try-ons requires significant GPU computing power that isn't available on standard web hosting. By offloading this to Preview AI's specialized servers, we provide a fast experience without slowing down your store.

= Does it store user photos? =
We take privacy seriously. User photos are processed in real-time by the Preview AI API to generate the preview. Images are not stored permanently on our servers unless required for the specific generation request.

= Does it support all products? =
Currently, the AI is optimized for clothing (upper body, lower body, and full body). Support for accessories and other categories is planned for future updates.

== Screenshots ==

1. The Preview AI button on a single product page.
2. The AI preview generation process.
3. The admin settings page with statistics.
4. Product-specific AI configuration.

== Changelog ==

= 1.0.0 =
* Initial release.
* Support for clothing virtual try-on.
* WooCommerce and Elementor integration.
* Automatic catalog analysis.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
