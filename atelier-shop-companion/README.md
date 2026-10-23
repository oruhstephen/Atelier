# Atelier Shop Companion (WordPress / WooCommerce Plugin)

A modular, lightweight companion plugin engineered specifically for the **Atelier Shop** WooCommerce block theme (`atelier-shop`). It fulfills the architecture principle in `atelier-shop/functions.php` (*"Keep this file small. Features (wishlist, swatches, etc.) belong in a companion plugin."*) while inheriting the theme's `theme.json` design tokens (`--wp--preset--color--*`, `--wp--preset--spacing--*`).

---

## Included Modern E-Commerce Modules

All modules can be individually toggled on or off and customized under **WooCommerce → Atelier Companion** in the WordPress admin dashboard:

1. **Wishlist & Saved Items (`Atelier_Companion_Wishlist`)**
   - Works seamlessly for both guests (`localStorage` + cookies) and logged-in customers (`user_meta`).
   - Automatically injects heart toggle buttons on WooCommerce Product Collection / Block product cards (`woocommerce/product-image`) and single product pages.
   - Adds a live Wishlist icon + counter badge next to the `woocommerce/mini-cart` block in the header that opens a slide-out **Saved Items Drawer**.
   - Includes the `[atelier_wishlist]` shortcode and **Customer Wishlist Grid** block pattern for a dedicated Wishlist page.

2. **Variation Color, Image & Label Swatches (`Atelier_Companion_Swatches`)**
   - Adds **Swatch Color (Hex)** and **Swatch Image URL** fields to WooCommerce attribute terms (**Products → Attributes → Configure terms**).
   - Automatically falls back to smart color detection for standard color slugs (`black`, `navy`, `cream`, `sage`, `terracotta`, etc.).
   - Converts frontend variable product `<select>` dropdowns into accessible radio-group swatches (`circle`, `rounded`, or `square`) synced with WooCommerce's native `wc-add-to-cart-variation.js`.

3. **Product Quick View Modal (`Atelier_Companion_Quick_View`)**
   - Adds a Quick View trigger button to product cards.
   - Opens an accessible modal with gallery thumbnail switching, sale badge, star rating, price, excerpt, variation swatches, and Add to Cart form.

4. **Dynamic Free Shipping Progress Bar (`Atelier_Companion_Shipping_Bar`)**
   - Tracks cart subtotal against your customizable Free Shipping goal (defaults to `£50` to match `patterns/usp-bar.php`).
   - Automatically renders on Cart, Checkout (both block & classic), Mini-Cart widget, and via `[atelier_shipping_bar]`, updating live with WooCommerce cart fragments.

5. **Single Product Accordions (`Atelier_Companion_Product_Enhancements`)**
   - Automatically upgrades the `woocommerce/product-details` block on `single-product.html` into 4 clean, accessible HTML5 `<details>` accordions:
     - **Product information**
     - **Specifications** (product attributes/dimensions + SKU)
     - **Delivery & returns**
     - **Reviews (N)**

6. **Interactive Size & Fit Guide Modal**
   - Adds a **Size & Fit Guide** button above the Add to Cart form that opens a responsive measurement table modal.
   - Includes a global default chart in **WooCommerce → Atelier Companion** plus a per-product **Atelier Size & Fit Guide Override** metabox on the Product Edit screen.

7. **Low-Stock Urgency Bar & Estimated Delivery / Trust Badges**
   - Displays a subtle stock progress bar when inventory drops at or below your customizable threshold (e.g., *"Only 4 left in stock — order soon"*).
   - Renders estimated delivery window and trust badges right below the Add to Cart button.

8. **Sticky Bottom Add-to-Cart Bar**
   - Smoothly slides in a compact product bar at the bottom of the viewport when scrolling past the primary Add to Cart form on single product pages.

9. **Instant AJAX Live Product Search (`Atelier_Companion_Live_Search`)**
   - Enhances the `core/search` block in `parts/header.html` with a debounced live dropdown displaying matching products, thumbnails, and prices by keyword or SKU.

---

## Installation

1. Copy or symlink the `atelier-shop-companion` folder into `wp-content/plugins/atelier-shop-companion`.
2. Activate **Atelier Shop Companion** in **Plugins → Installed Plugins**.
3. Visit **WooCommerce → Atelier Companion** to toggle modules, customize thresholds, and edit default copy.
