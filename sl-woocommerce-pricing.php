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
        'SL WooCommerce Pricing',
        'SL WooCommerce Pricing',
        'manage_options',
        'sl-woocommerce-pricing',
        'slwc_settings_page'
    );
}

add_action('admin_enqueue_scripts', 'slwc_enqueue_admin_style');
function slwc_enqueue_admin_style($hook)
{
    if ($hook != 'woocommerce_page_sl-woocommerce-pricing') {
        return;
    }
    wp_enqueue_style('slwc_admin_bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css', [], '5.0.2');
    wp_enqueue_style('slwc_admin', plugin_dir_url(__FILE__) . 'assets/css/admin.css', ['slwc_admin_bootstrap'], filemtime(plugin_dir_path(__FILE__) . 'assets/css/admin.css'));
    wp_enqueue_script('slwc_admin_bootstrap_bundle', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js', ['jquery'], '5.0.2', true);
    wp_enqueue_script('slwc_popper', 'https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js', ['slwc_admin_bootstrap_bundle'], '2.9.2', true);
}

add_action('wp_enqueue_scripts', 'slwc_enqueue_scripts');

function slwc_enqueue_scripts($hook)
{
    wp_enqueue_style('slwc', plugin_dir_url(__FILE__) . 'assets/css/style.css', [], filemtime(plugin_dir_path(__FILE__) . 'assets/css/style.css'));
}

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
    register_setting('slwc_settings_group', 'slwc_selected_banks', [
        'sanitize_callback' => function ($input) {
            return is_array($input) ? array_map('sanitize_text_field', $input) : [];
        }
    ]);
    register_setting('slwc_settings_group', 'slwc_payment_options', ['sanitize_callback' => function ($input) {
        if (is_array($input)) {
            foreach ($input as $key => $values) {
                if (isset($values['instalment']) && is_array($values['instalment'])) {
                    foreach ($values['instalment'] as $duration => $details) {
                        $surcharge = isset($details['surcharge']) ? floatval($details['surcharge']) : 0;
                        $enabled = isset($details['enabled']) ? filter_var($details['enabled'], FILTER_VALIDATE_BOOLEAN) : false;
                        $input[$key]['instalment'][$duration] = ['surcharge' => $surcharge, 'enabled' => $enabled];
                    }
                } else {
                    $input[$key]['instalment'] = [];
                }
                $input[$key]['instant'] = isset($values['instant']) ? floatval($values['instant']) : 0;
            }

            return $input;
        }
    }]);
    register_setting('slwc_settings_group', 'slwc_show_instant_prices');
    register_setting('slwc_settings_group', 'slwc_front_end_message', ['sanitize_callback' => function ($input) {
        return wp_kses_post($input);
    }]);

    add_settings_section('slwc_general_settings', __('General Settings', 'sl-woocommerce-pricing'), null, 'sl-woocommerce-pricing');
    add_settings_field('slwc_enable_special_pricing', __('Show pricing for all products?', 'sl-woocommerce-pricing'), 'slwc_enable_special_pricing_field', 'sl-woocommerce-pricing', 'slwc_general_settings');
    add_settings_field('slwc_selected_banks', __('Select banks for instalment plans', 'sl-woocommerce-pricing'), 'slwc_selected_banks_field', 'sl-woocommerce-pricing', 'slwc_general_settings');
    add_settings_field('slwc_show_instant_prices', __('Show instant pricing for all products?', 'sl-woocommerce-pricing'), 'slwc_show_instant_prices_field', 'sl-woocommerce-pricing', 'slwc_general_settings');
    add_settings_field('slwc_front_end_message', __('Show a message to your customers about these prices', 'sl-woocommerce-pricing'), 'slwc_front_end_message_field', 'sl-woocommerce-pricing', 'slwc_general_settings');
    add_settings_field('slwc_payment_options', __('Configure instalment plans and discounts for each bank', 'sl-woocommerce-pricing'), 'slwc_payment_options_field', 'sl-woocommerce-pricing', 'slwc_general_settings');

    function slwc_enable_special_pricing_field()
    {
        $value = get_option('slwc_enable_special_pricing');
        $checked = $value ? 'checked' : '';
    ?>
        <div class="container">
            <div class="row">
                <div class="col">
                    <?php echo '<input type="checkbox" value="1" name="slwc_enable_special_pricing"' . $checked . '/>';
                    ?>
                </div>
            </div>
        </div>
    <?php
    }

    function slwc_show_instant_prices_field()
    {
        $value = get_option('slwc_show_instant_prices');
        $checked = $value ? 'checked' : '';
    ?>
        <div class="container">
            <div class="row">
                <div class="col">
                    <?php echo '<input type="checkbox" value="1" name="slwc_show_instant_prices"' . $checked . '/>';
                    ?>
                </div>
            </div>
        </div>
    <?php
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

    ?>
        <div class="container">
            <div class="row">
                <?php foreach ($banks as $bank) { ?>
                    <div class="col-12">
                        <?php $checked = in_array($bank, $selected_banks) ? 'checked' : '';
                        echo '<label><input type="checkbox" name="slwc_selected_banks[]" value="' . esc_attr($bank) . '"' . $checked . ' />' . esc_html($bank) . '</label><br>';
                        ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php


    }

    function slwc_front_end_message_field()
    { ?>
        <div class="container">
            <div class="row">
                <div class="col">
                    <label for="slwc_front_end_message" class="form-label">Enter your message here. <i><small>You can use HTML if you require</small></i></label>
                    <textarea class="form-control" id="slwc_front_end_message" name="slwc_front_end_message" rows="3"><?php echo esc_textarea(get_option('slwc_front_end_message', 'Please <b>contact us</b> before placing an order if you want to use the special prices listed here.')) ?></textarea>
                    <div id="slwc_front_end_message_help" class="form-text">An example would be: Please contact us on 077123456 before placing your order if you want to use the special prices mentioned</div>
                </div>
            </div>
        </div>
    <?php
    }

    function slwc_payment_options_field()
    {
        $payment_options = get_option('slwc_payment_options', []);
        $selected_banks = get_option('slwc_selected_banks', []);
        $instalment_duration = [6, 12, 24, 36];

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
                    $instalment = isset($payment_options[$bank]['instalment']) ? $payment_options[$bank]['instalment'] : '';
                    $instant = isset($payment_options[$bank]['instant']) ? $payment_options[$bank]['instant'] : 0;
                    $hide_instant_price = get_option('slwc_show_instant_prices') ? '' : 'disabled';

                ?>
                    <div class="col-12 col-md-4">
                        <div class="slwc_bank_settings" name="<?php echo esc_attr($bank); ?>">
                            <div class="card" <?php echo $disabled ?>>
                                <div class="card-body">
                                    <div class="slwc-bank-image-holder">
                                        <img src="<?php echo plugin_dir_url(__FILE__) . '/assets/images/' . esc_attr($bank) . '.jpg' ?>" class="card-img-top slwc-bank-image" alt="<?php echo esc_attr($bank); ?>">
                                    </div>
                                    <p class="card-text mt-4">Offer instalments for <b><?php echo esc_html($bank) ?></b> customers</p>
                                    <hr />
                                    <div class="row mt-3">
                                        <?php foreach ($instalment_duration as $month) { ?>
                                            <div class="col-12">
                                                <div class="row">
                                                    <div class="col-2">
                                                        <input type="checkbox"
                                                            name="slwc_payment_options[<?php echo esc_attr($bank) ?>][instalment][<?php echo $month ?>][enabled]"
                                                            <?php echo isset($instalment[$month]['enabled']) && $instalment[$month]['enabled'] ? 'checked' : '' ?> />
                                                    </div>
                                                    <div class="col-10">
                                                        <label><small>Surcharge <?php echo $month ?> months instalment plans</small></label>
                                                    </div>
                                                </div>
                                                <div class="input-group mb-3 input-group-sm">
                                                    <input type="number"
                                                        step="1"
                                                        class="form-control"
                                                        placeholder="Instalment"
                                                        aria-label="Instalment Duration"
                                                        aria-describedby="instalment-duration"
                                                        name="slwc_payment_options[<?php echo esc_attr($bank) ?>][instalment][<?php echo $month ?>][surcharge]"
                                                        value="<?php echo isset($instalment[$month]['surcharge']) && esc_attr($instalment[$month]['surcharge']) ?? '' ?>"
                                                        <?php echo isset($instalment[$month]['enabled']) && $instalment[$month]['enabled'] ? '' : 'disabled';
                                                        echo $disabled ?>
                                                        min="0">
                                                    <span class="input-group-text" id="slwc_percentage">%</span>
                                                </div>
                                            </div>
                                        <?php } ?>

                                    </div>
                                    <p class="card-text mt-4">Offer a special discount for <b><?php echo esc_html($bank) ?></b> customers when they pay instantly. <br> <small><i>Set the field to zero (0) if you don't want to show a special price for this bank.</i></small></p>
                                    <hr />
                                    <div class="row mt-1">
                                        <div class="col-12"> <label><small>Instant Payment Discount</small></label>
                                            <div class="input-group mb-3 input-group-sm">
                                                <input type="number" step="0.1" class="form-control" placeholder="Instant" aria-label="Instant Payment Discount" aria-describedby="instant-payment-discount" name="slwc_payment_options[<?php echo esc_attr($bank) ?>][instant]" value="<?php echo esc_attr($instant) ?>"
                                                    <?php echo $hide_instant_price ?> max=" 100" min="0">
                                                <span class="input-group-text" id="slwc_percentage">%</span>
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

function slwc_display_banks_on_product_page()
{
    global $product;

    $banks = get_option('slwc_payment_options');
    $enabled = get_option('slwc_enable_special_pricing');
    $hide_instant_price = get_option('slwc_show_instant_prices') ? '' : 'disabled';

    if ($enabled) {

    ?>
        <div class="slwc-container slwc_mt_10">
            <div class="slwc-row">
                <h4 class="slwc-main-title">Bank-Specific Pricing Options</h4>
                <?php
                $front_end_message = get_option('slwc_front_end_message', '');

                if (!empty($front_end_message)) {
                    echo '<p class="slwc-custom-message">Note: ' . wp_kses_post($front_end_message) . '</p>';
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
                        <div class="slwc-col-sm-12 slwc-col-md-12 slwc-col-lg-5">
                            <div class="slwc-bank-plan slwc-bank-instant-price">
                                <div class="slwc-bank-discount-wrapper">
                                    <p class="slwc-bank-discount-percentage">
                                        <?php echo esc_html($bank_prices['instant'] . '% off'); ?>
                                    </p>
                                </div>
                                <div class="slwc-bank-image-holder">
                                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'assets/images/' . str_replace(' ', '_', esc_attr($bank)) . '.jpg'); ?>"
                                        class="slwc-bank-image"
                                        alt="<?php echo esc_attr($bank_prices['instant'] . '% off for ' . $bank . ' customers.'); ?>">
                                </div>
                                <p><b><?php echo wc_price($price); ?></b></p>
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

            <div class="slwc-row">
                <h5 class="slwc-bank-instalment-title">Bank Specific Instalment Rates</h5>
                <?php
                foreach ($banks as $bank => $bank_prices) {
                    if ($bank_prices && isset($bank_prices['instalment']) && is_array($bank_prices['instalment'])) {
                        $enabled_instalments = array_filter($bank_prices['instalment'], function ($details) {
                            return isset($details['enabled']) && $details['enabled'];
                        });

                        if (!empty($enabled_instalments)) {
                            $highest_duration = max(array_keys($enabled_instalments));
                            $highest_instalment = $enabled_instalments[$highest_duration];
                            $price = ($product->get_price() + ($product->get_price() * ($highest_instalment['surcharge'] / 100))) / $highest_duration;

                ?>
                            <div class="slwc-col-sm-12 slwc-col-md-5">
                                <div class="slwc-bank-plan slwc-bank-instalment-price">
                                    <div class="slwc-bank-image-holder">
                                        <img src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/' . str_replace(' ', '_', esc_attr($bank)) . '.jpg' ?>" class="card-img-top slwc-bank-image" alt="<?php echo esc_attr($bank); ?>">
                                    </div>
                                    <p>
                                        <b>
                                            <?php echo wc_price($price) ?>
                                        </b>
                                        <br />
                                        <span class="slwc-bank-instalment-month">per month for
                                            <b>
                                                <?php echo array_key_last($bank_prices['instalment']) ?> months
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
add_action('woocommerce_single_product_summary', 'slwc_display_banks_on_product_page', 25);
