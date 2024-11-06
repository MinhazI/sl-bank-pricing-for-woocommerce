<?php

/*
 * Plugin Name:       SL WooCommerce Pricing
 * Plugin URI:        https://minhazimohamed.com/sl-woocommerce-pricing/
 * Description:       Enhance your WooCommerce store with SL WooCommerce Pricing—customize special pricing and installment plans for Sri Lankan bank customers. Boost sales with localized payment options tailored for Sri Lanka.
 * Version:           0.1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Minhaz Irphan Mohamed
 * Author URI:        https://minhazimohamed.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://minhazimohamed.com/sl-woocommerce-pricing/
 * Text Domain:       sl-woocommerce-pricing
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 */

register_activation_hook(__FILE__, 'slwc_plugin_activation');

function slwc_plugin_activation()
{
    if (!class_exists('woocommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('SL WooCommerce Pricing plugin requires WooCommerce to be installed and activated. Please install and activate WooCommerce first');
    }

    $default_options = [
        'enabled_banks' => [],
        'default_installment_plans' => ''
    ];

    add_option('slwc_plugin_options', $default_options);
}

add_action('admin_menu', 'slwc_add_admin_menu');

function slwc_add_admin_menu()
{
    add_submenu_page(
        'woocommerce',
        'SL Woocommerce Pricing',
        'SL Woocommerce Pricing',
        'manage_options',
        'sl-woocommerce_pricing',
        'slwc_settings_page'
    );
}


function slwc_settings_page()
{
?>
    <div class="wrap">
        <h1><?php esc_html_e('SL WooCommerce Pricing Settings', 'sl-woocommerce-pricing') ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('slwc_settings_group');
            do_settings_sections('sl-wocommerce-pricing');
            submit_button();
            ?>
        </form>
    </div>
<?php
}
