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
        'sl_woocommerce_pricing',
        'slwc_settings_page'
    );
}


function slwc_settings_page()
{
?>
    <div class="wrap">
        <h1><?php esc_html_e('SL WooCommerce Pricing Settings', 'sl-woocommerce-pricing'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('slwc_settings_group');
            do_settings_sections('sl-woocommerce-pricing');
            submit_button();
            ?>
        </form>
    </div>
<?php
}

add_action('admin_init', 'slwc_register_settings');
function slwc_register_settings()
{
    register_setting('slwc_settings_group', 'slwc_enable_special_pricing');
    register_setting('slwc_settings_group', 'slwc_selected_banks');
    register_setting('slwc_settings_group', 'slwc_payment_options');

    add_settings_section('slwc_general_settings', __('General Settings', 'sl-woocommerce-pricing'), null, 'sl-woocommerce-pricing');

    add_settings_field('slwc_enable_special_pricing', __('Enable Special Pricing', 'sl-woocommerce-pricing'), 'slwc_enable_special_pricing_field', 'sl-woocommerce-pricing', 'slwc_general_settings');
    add_settings_field('slwc_selected_banks', __('Select Banks for Instalment Plans', 'sl-woocommerce-pricing'), 'slwc_selected_banks_field', 'sl-woocommerce-pricing', 'slwc_general_settings');
    add_settings_field('slwc_payment_options', __('Select Payment Option for Each Bank', 'sl-woocommerce-pricing'), 'slwc_payment_options_field', 'sl-woocommerce-pricing', 'slwc_general_settings');

    function slwc_enable_special_pricing_field()
    {
        $value = get_option('slwc_enable_special_pricing');
        echo '<input type="checkbox" value="1"' . checked(1, $value, false) . '/>';
    }

    function slwc_selected_banks_field()
    {
        $banks = [
            'Nations Trust Bank',
            'Commercial Bank',
            'Hatton National Bank',
            'Sampath Bank',
            'Seylan Bank',
            'Bank of Ceylon'
        ];

        $selected_banks = get_option('slwc_selected_banks', []);

        foreach ($banks as $bank) {
            $checked = in_array($bank, $selected_banks) ? 'checked' : '';
            echo '<label><input type="checkbox" name="slwc_selected_banks[]" value="' . esc_attr($bank) . '"' . $checked . '/>' . esc_html($bank) . '</label><br>';
        }
    }

    function slwc_payment_options_field()
    {
        $payment_options = get_option('slwc_payment_options', []);

        $banks = [
            'Nations Trust Bank',
            'Commercial Bank',
            'Hatton National Bank',
            'Sampath Bank',
            'Seylan Bank',
            'Bank of Ceylon'
        ];

        foreach ($banks as $bank) {
            $instalment = isset($payment_options[$bank]['instalment']) ? $payment_options[$bank]['instalment'] : '';
            $instant = isset($payment_options[$bank]['instant']) ? $payment_options[$bank]['instant'] : '';

            echo '<strong>' . esc_html($bank) . '</strong><br>';
            echo '<label>Instalment: <input type="text" name="slwc_payment_options[' . esc_attr($bank) . '][instalment]" value="' . esc_attr($instalment) . '"</label><br>';
            echo '<label>Instant: <input type="text" name="slwc_payment_options[' . esc_attr($bank) . '][instalment]" value="' . esc_attr($instant) . '"</label><br>';
        }
    }
}
