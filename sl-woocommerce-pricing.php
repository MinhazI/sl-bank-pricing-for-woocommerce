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

function slwc_enqueue_admin_style($hook)
{
    if ($hook != 'woocommerce_page_sl_woocommerce_pricing') {
        return;
    }
    wp_enqueue_style('slwc_admin_bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css', [], '5.0.2');
    wp_enqueue_style('slwc_admin', plugin_dir_url(__FILE__) . 'css/admin.css', ['slwc_admin_bootstrap'], filemtime(plugin_dir_path(__FILE__) . 'css/admin.css'));
    wp_enqueue_script('slwc_admin_bootstrap_bundle', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js', ['jquery'], '5.0.2', true);
    wp_enqueue_script('slwc_popper', 'https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js', ['slwc_admin_bootstrap_bundle'], '2.9.2', true);
}

add_action('admin_enqueue_scripts', 'slwc_enqueue_admin_style');

function slwc_settings_page()
{
?>
    <div class="wrap">
        <h1><?php esc_html_e('SL WooCommerce Pricing Settings', 'sl-woocommerce-pricing'); ?></h1>
        <?php settings_errors(); ?>
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

    add_settings_field('slwc_enable_special_pricing', __('Show pricing for all product', 'sl-woocommerce-pricing'), 'slwc_enable_special_pricing_field', 'sl-woocommerce-pricing', 'slwc_general_settings');
    add_settings_field('slwc_selected_banks', __('Select banks for instalment plans', 'sl-woocommerce-pricing'), 'slwc_selected_banks_field', 'sl-woocommerce-pricing', 'slwc_general_settings');
    add_settings_field('slwc_payment_options', __('Add the % of discount for each bank', 'sl-woocommerce-pricing'), 'slwc_payment_options_field', 'sl-woocommerce-pricing', 'slwc_general_settings');

    function slwc_enable_special_pricing_field()
    {
        $value = get_option('slwc_enable_special_pricing');
        $checked = $value ? 'checked' : '';
        echo '<input type="checkbox" value="1" name="slwc_enable_special_pricing"' . $checked . '/>';
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

    ?>
        <div class="container">
            <div class="row">

                <?php
                foreach ($banks as $bank) {
                    $instalment = isset($payment_options[$bank]['instalment']) ? $payment_options[$bank]['instalment'] : '';
                    $instant = isset($payment_options[$bank]['instant']) ? $payment_options[$bank]['instant'] : '';

                ?>
                    <div class="col-4">
                        <div class="slwc_bank_settings" name="<?php echo esc_attr($bank); ?>">
                            <div class="card" style="width: 18rem;">
                                <div class="card-body">
                                    <img src="<?php echo plugin_dir_url(__FILE__) . 'images/' . esc_attr($bank) . '.jpg' ?>" class="card-img-top" alt="<?php echo esc_attr($bank); ?>">
                                    <!-- <h5 class="card-title"><?php echo esc_html($bank) ?></h5> -->
                                    <p class="card-text mt-2">Offer a special discount for <b><?php echo esc_html($bank) ?></b> customers</p>
                                    <label>Instalment: <input type="text" name="slwc_payment_options[ <?php echo esc_attr($bank) ?>][instalment]" value=<?php echo esc_attr($instalment) ?>></label><br>
                                    <label>Instant: <input type="text" name="slwc_payment_options[<?php echo esc_attr($bank) ?>][instant]" value=<?php esc_attr($instant) ?>> </label><br>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php
                }
                ?>
                <p><small>Disclosure: The logos of the banks featured in this plugin are trademarks of their respective owners and are used solely for illustrative purposes within the plugin to represent available payment options.</small></p>
            </div>
        </div>
<?php
    }
}
