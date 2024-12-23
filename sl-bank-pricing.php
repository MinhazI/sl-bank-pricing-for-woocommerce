<?php

/*
 * Plugin Name:       SL Bank Pricing for WooCommerce
 * Plugin URI:        https://minhazimohamed.com/sl-bank-pricing-for-woocommerce/
 * Description:       Enhance your WooCommerce store with SL Bank Pricing for WooCommerce special pricing and installment plans for Sri Lankan bank customers. Boost sales with localized payment options tailored for Sri Lanka.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Minhaz Irphan Mohamed
 * Author URI:        https://minhazimohamed.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sl-bank-pricing-for-woocommerce
 * Requires Plugins:  woocommerce
 */

if (! defined('ABSPATH')) exit;

register_activation_hook(__FILE__, 'slbp_plugin_activation');

function slbp_plugin_activation()
{
    if (!class_exists('woocommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('SL Bank Pricing for WooCommerce plugin requires WooCommerce to be installed and activated. Please install and activate WooCommerce first');
    }

    $default_options = [
        'enabled_banks' => [],
        'default_installment_plans' => ''
    ];

    add_option('slbp_plugin_options', $default_options);
}

add_action('admin_menu', 'slbp_add_admin_menu');

function slbp_add_admin_menu()
{
    add_submenu_page(
        'woocommerce',
        'SL Bank Pricing for WooCommerce',
        'SL Bank Pricing for WooCommerce',
        'manage_options',
        'sl-bank-pricing',
        'slbp_settings_page'
    );
}

add_action('admin_enqueue_scripts', 'slbp_enqueue_admin_style');
function slbp_enqueue_admin_style($hook)
{
    if ($hook != 'woocommerce_page_sl-bank-pricing') {
        return;
    }
    wp_enqueue_style('slbp_admin_bootstrap', plugin_dir_url(__FILE__) . 'assets/css/bootstrap.min.css', [], '5.0.2');
    wp_enqueue_style('slbp_admin', plugin_dir_url(__FILE__) . 'assets/css/admin.css', ['slbp_admin_bootstrap'], filemtime(plugin_dir_path(__FILE__) . 'assets/css/admin.css'));
    wp_enqueue_script('slbp_admin_bootstrap_bundle', plugin_dir_url(__FILE__) . 'assets/js/bootstrap.bundle.min.js', ['jquery'], '5.0.2', true);
}

add_action('wp_enqueue_scripts', 'slbp_enqueue_scripts');

function slbp_enqueue_scripts($hook)
{
    wp_enqueue_style('slbp', plugin_dir_url(__FILE__) . 'assets/css/style.css', [], filemtime(plugin_dir_path(__FILE__) . 'assets/css/style.css'));
}

function slbp_settings_page()
{
?>
    <div class="wrap">
        <h1><?php esc_html_e('SL Bank Pricing for WooCommerce Settings', 'sl-bank-pricing-for-woocommerce'); ?></h1>
        <?php settings_errors(); ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('slbp_settings_group');
            do_settings_sections('sl-bank-pricing');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'slbp_register_settings');
function slbp_register_settings()
{
    register_setting('slbp_settings_group', 'slbp_enable_special_pricing', [
        'sanitize_callback' => 'rest_sanitize_boolean'
    ]);
    register_setting('slbp_settings_group', 'slbp_selected_banks', [
        'sanitize_callback' => function ($input) {
            return is_array($input) ? array_map('sanitize_text_field', $input) : [];
        }
    ]);
    register_setting('slbp_settings_group', 'slbp_payment_options', ['sanitize_callback' => function ($input) {
        if (is_array($input)) {
            foreach ($input as $key => $values) {
                if (isset($values['installment']) && is_array($values['installment'])) {
                    foreach ($values['installment'] as $duration => $details) {
                        $surcharge = isset($details['surcharge']) ? floatval($details['surcharge']) : 0;
                        $enabled = isset($details['enabled']) ? filter_var($details['enabled'], FILTER_VALIDATE_BOOLEAN) : false;
                        $input[$key]['installment'][$duration] = ['surcharge' => $surcharge, 'enabled' => $enabled];
                    }
                } else {
                    $input[$key]['installment'] = [];
                }
                $input[$key]['instant'] = isset($values['instant']) ? floatval($values['instant']) : 0;
            }

            return $input;
        }
    }]);
    register_setting('slbp_settings_group', 'slbp_show_instant_prices', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('slbp_settings_group', 'slbp_front_end_message', ['sanitize_callback' => function ($input) {
        return wp_kses_post($input);
    }]);

    add_settings_section('slbp_general_settings', __('General Settings', 'sl-bank-pricing-for-woocommerce'), null, 'sl-bank-pricing');
    add_settings_field('slbp_enable_special_pricing', __('Show pricing for all products?', 'sl-bank-pricing-for-woocommerce'), 'slbp_enable_special_pricing_field', 'sl-bank-pricing', 'slbp_general_settings');
    add_settings_field('slbp_selected_banks', __('Select banks for installment plans', 'sl-bank-pricing-for-woocommerce'), 'slbp_selected_banks_field', 'sl-bank-pricing', 'slbp_general_settings');
    add_settings_field('slbp_show_instant_prices', __('Show instant pricing for all products?', 'sl-bank-pricing-for-woocommerce'), 'slbp_show_instant_prices_field', 'sl-bank-pricing', 'slbp_general_settings');
    add_settings_field('slbp_front_end_message', __('Show a message to your customers about these prices', 'sl-bank-pricing-for-woocommerce'), 'slbp_front_end_message_field', 'sl-bank-pricing', 'slbp_general_settings');
    add_settings_field('slbp_payment_options', __('Configure installment plans and discounts for each bank', 'sl-bank-pricing-for-woocommerce'), 'slbp_payment_options_field', 'sl-bank-pricing', 'slbp_general_settings');

    function slbp_enable_special_pricing_field()
    {
        $value = get_option('slbp_enable_special_pricing');
        $checked = $value ? 'checked' : '';
    ?>
        <div class="container">
            <div class="row">
                <div class="col">
                    <?php echo '<input type="checkbox" value="1" name="slbp_enable_special_pricing"' . esc_attr($checked) . '/>';
                    ?>
                </div>
            </div>
        </div>
    <?php
    }

    function slbp_show_instant_prices_field()
    {
        $value = get_option('slbp_show_instant_prices');
        $checked = $value ? 'checked' : '';
    ?>
        <div class="container">
            <div class="row">
                <div class="col">
                    <?php echo '<input type="checkbox" value="1" name="slbp_show_instant_prices"' . esc_attr($checked) . '/>';
                    ?>
                </div>
            </div>
        </div>
    <?php
    }

    function slbp_selected_banks_field()
    {
        $banks = [
            'Nations Trust Bank',
            'Commercial Bank',
            'Hatton National Bank',
            'Sampath Bank',
            'Seylan Bank',
            'Bank of Ceylon'
        ];

        $selected_banks = get_option('slbp_selected_banks', []);

    ?>
        <div class="container">
            <div class="row">
                <?php foreach ($banks as $bank) { ?>
                    <div class="col-12">
                        <?php $checked = in_array($bank, $selected_banks) ? 'checked' : '';
                        echo '<label><input type="checkbox" name="slbp_selected_banks[]" value="' . esc_attr($bank) . '"' . esc_attr($checked) . ' />' . esc_html($bank) . '</label><br>';
                        ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php


    }

    function slbp_front_end_message_field()
    { ?>
        <div class="container">
            <div class="row">
                <div class="col">
                    <label for="slbp_front_end_message" class="form-label">Enter your message here. <i><small>You can use HTML if you require</small></i></label>
                    <textarea class="form-control" id="slbp_front_end_message" name="slbp_front_end_message" rows="3"><?php echo esc_textarea(get_option('slbp_front_end_message', 'Please <b>contact us</b> before placing an order if you want to use the special prices listed here.')) ?></textarea>
                    <div id="slbp_front_end_message_help" class="form-text">An example would be: Please contact us on 077123456 before placing your order if you want to use the special prices mentioned</div>
                </div>
            </div>
        </div>
    <?php
    }

    function slbp_payment_options_field()
    {
        $payment_options = get_option('slbp_payment_options', []);
        $selected_banks = get_option('slbp_selected_banks', []);
        $installment_duration = [6, 12, 24, 36];

    ?>
        <div class="container">
            <div class="row">

                <?php

                if (!$selected_banks) {
                    echo '<p>No banks selected. Please select banks in the above section.</p>';
                    return;
                }

                foreach ($selected_banks as $bank) {
                    $disabled = in_array($bank, $selected_banks) ? '' : 'disabled';
                    $installment = isset($payment_options[$bank]['installment']) ? $payment_options[$bank]['installment'] : '';
                    $instant = isset($payment_options[$bank]['instant']) ? $payment_options[$bank]['instant'] : 0;
                    $hide_instant_price = get_option('slbp_show_instant_prices') ? '' : 'disabled';

                ?>
                    <div class="col-12 col-md-4">
                        <div class="slbp_bank_settings" name="<?php echo esc_attr($bank); ?>">
                            <div class="card" <?php echo esc_attr($disabled) ?>>
                                <div class="card-body">
                                    <div class="slbp-bank-image-holder">
                                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'assets/images/' . str_replace(' ', '_', esc_attr($bank)) . '.jpg') ?>" class="card-img-top slbp-bank-image" alt="<?php echo esc_attr($bank); ?>">
                                    </div>
                                    <p class="card-text mt-4">Offer installments for <b><?php echo esc_html($bank) ?></b> customers</p>
                                    <hr />
                                    <div class="row mt-3">
                                        <?php foreach ($installment_duration as $month) { ?>
                                            <div class="col-12">
                                                <div class="row">
                                                    <div class="col-2">
                                                        <input type="checkbox"
                                                            name="slbp_payment_options[<?php echo esc_attr($bank) ?>][installment][<?php echo esc_attr($month) ?>][enabled]"
                                                            <?php echo isset($installment[$month]['enabled']) && $installment[$month]['enabled'] ? 'checked' : '' ?> />
                                                    </div>
                                                    <div class="col-10">
                                                        <label><small>Surcharge <?php echo esc_attr($month) ?> months installment plans</small></label>
                                                    </div>
                                                </div>
                                                <div class="input-group mb-3 input-group-sm">
                                                    <input type="number"
                                                        step="1"
                                                        class="form-control"
                                                        placeholder="Installment"
                                                        aria-label="Installment Duration"
                                                        aria-describedby="installment-duration"
                                                        name="slbp_payment_options[<?php echo esc_attr($bank) ?>][installment][<?php echo esc_attr($month) ?>][surcharge]"
                                                        value="<?php echo isset($installment[$month]['surcharge']) ? esc_attr($installment[$month]['surcharge']) : '' ?>"
                                                        <?php echo isset($installment[$month]['enabled']) && $installment[$month]['enabled'] ? '' : 'disabled';
                                                        echo esc_attr($disabled) ?>
                                                        min="0">
                                                    <span class="input-group-text" id="slbp_percentage">%</span>
                                                </div>
                                            </div>
                                        <?php } ?>

                                    </div>
                                    <p class="card-text mt-4">Offer a special discount for <b><?php echo esc_html($bank) ?></b> customers when they pay instantly. <br> <small><i>Set the field to zero (0) if you don't want to show a special price for this bank.</i></small></p>
                                    <hr />
                                    <div class="row mt-1">
                                        <div class="col-12"> <label><small>Instant Payment Discount</small></label>
                                            <div class="input-group mb-3 input-group-sm">
                                                <input type="number" step="0.1" class="form-control" placeholder="Instant" aria-label="Instant Payment Discount" aria-describedby="instant-payment-discount" name="slbp_payment_options[<?php echo esc_attr($bank) ?>][instant]" value="<?php echo esc_attr($instant) ?>"
                                                    <?php echo esc_attr($hide_instant_price) ?> max=" 100" min="0">
                                                <span class="input-group-text" id="slbp_percentage">%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php
                }
                ?>

            </div>
            <p class="mt-5"><small>Disclosure: The logos of the banks featured in this plugin are trademarks of their respective owners and are used solely for illustrative purposes within the plugin to represent available payment options.</small></p>
        </div>
    <?php
    }
}

function slbp_display_banks_on_product_page()
{
    global $product;

    $banks = get_option('slbp_payment_options');
    $enabled = get_option('slbp_enable_special_pricing');
    $hide_instant_price = get_option('slbp_show_instant_prices') ? '' : 'disabled';

    if ($enabled) {

    ?>
        <div class="slbp-container slbp_mt_10">
            <div class="slbp-row">
                <h4 class="slbp-main-title">Bank-Specific Pricing Options</h4>
                <?php
                $front_end_message = get_option('slbp_front_end_message', '');

                if (!empty($front_end_message)) {
                    echo '<p class="slbp-custom-message">Note: ' . wp_kses_post($front_end_message) . '</p>';
                }
                if (!$hide_instant_price):
                    $has_banks_with_instant_pricing = false;

                    foreach ($banks as $bank => $bank_prices) {
                        if (!isset($bank_prices['instant']) || $bank_prices['instant'] == 0) {
                            continue;
                        }

                        $has_banks_with_instant_pricing = true;
                        $price = $product->get_price() - ($product->get_price() * ($bank_prices['instant'] / 100));
                ?>
                        <div class="slbp-col-sm-12 slbp-col-md-12 slbp-col-lg-5">
                            <div class="slbp-bank-plan slbp-bank-instant-price">
                                <div class="slbp-bank-discount-wrapper">
                                    <p class="slbp-bank-discount-percentage">
                                        <?php echo esc_html($bank_prices['instant'] . '% off'); ?>
                                    </p>
                                </div>
                                <div class="slbp-bank-image-holder">
                                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'assets/images/' . str_replace(' ', '_', esc_attr($bank)) . '.jpg'); ?>"
                                        class="slbp-bank-image"
                                        alt="<?php echo esc_attr($bank_prices['instant'] . '% off for ' . $bank . ' customers.'); ?>">
                                </div>
                                <p><b><?php echo wp_kses_post(wc_price($price)); ?></b></p>
                            </div>
                        </div>
                    <?php
                    }
                    if (!$has_banks_with_instant_pricing):
                    ?>
                    <?php endif; ?>

                <?php else: ?>
                <?php endif; ?>
            </div>

            <div class="slbp-row">
                <h5 class="slbp-bank-installment-title">Bank Specific Installment Rates</h5>
                <?php
                foreach ($banks as $bank => $bank_prices) {
                    if ($bank_prices && isset($bank_prices['installment']) && is_array($bank_prices['installment'])) {
                        $enabled_installments = array_filter($bank_prices['installment'], function ($details) {
                            return isset($details['enabled']) && $details['enabled'];
                        });

                        if (!empty($enabled_installments)) {
                            $highest_duration = max(array_keys($enabled_installments));
                            $highest_installment = $enabled_installments[$highest_duration];
                            $price = ($product->get_price() + ($product->get_price() * ($highest_installment['surcharge'] / 100))) / $highest_duration;

                ?>
                            <div class="slbp-col-sm-12 slbp-col-md-5">
                                <div class="slbp-bank-plan slbp-bank-installment-price">
                                    <div class="slbp-bank-image-holder">
                                        <img src="<?php echo esc_attr(plugin_dir_url(__FILE__) . 'assets/images/' . str_replace(' ', '_', esc_attr($bank)) . '.jpg') ?>" class="card-img-top slbp-bank-image" alt="<?php echo esc_attr($bank); ?>">
                                    </div>
                                    <p>
                                        <b>
                                            <?php echo wp_kses_post(wc_price($price)) ?>
                                        </b>
                                        <br />
                                        <span class="slbp-bank-installment-month">per month for
                                            <b>
                                                <?php echo esc_attr(array_key_last($bank_prices['installment'])) ?> months
                                            </b>
                                        </span>
                                    </p>
                                </div>
                            </div>
            <?php
                        }
                    }
                }
            } else {
                return '';
            }
            ?>
            </div>
        </div>
    <?php
}
add_action('woocommerce_single_product_summary', 'slbp_display_banks_on_product_page', 25);
