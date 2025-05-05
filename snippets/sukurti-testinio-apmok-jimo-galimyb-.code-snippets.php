<?php

/**
 * Sukurti testinio apmokėjimo galimybę
 */
// Sukuriam paprastą testavimo mokėjimo būdą
add_filter('woocommerce_payment_gateways', function($gateways) {
    $gateways[] = 'WC_Gateway_Test';
    return $gateways;
});

add_action('plugins_loaded', function() {
    if (!class_exists('WC_Payment_Gateway')) return;

    class WC_Gateway_Test extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = 'test_gateway';
            $this->method_title = 'Testavimo mokėjimas';
            $this->method_description = 'Naudojama testavimui. Užsakymai automatiškai pažymimi kaip apmokėti.';
            $this->has_fields = false;

            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option('title', 'Testavimo mokėjimas');
            $this->enabled = $this->get_option('enabled');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        }

        public function init_form_fields() {
            $this->form_fields = [
                'enabled' => [
                    'title' => 'Įjungti',
                    'type' => 'checkbox',
                    'label' => 'Įjungti šį testavimo būdą',
                    'default' => 'yes'
                ],
                'title' => [
                    'title' => 'Mokėjimo būdo pavadinimas',
                    'type' => 'text',
                    'default' => 'Testavimo mokėjimas'
                ]
            ];
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            $order->payment_complete();
            $order->reduce_order_stock();

            return [
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            ];
        }
    }
});
