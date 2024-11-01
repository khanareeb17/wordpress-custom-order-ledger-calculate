<?php
global $wpdb;
global  $woocommerce;
    
$table_name = $wpdb->prefix . 'order_ledgers';

// Fetch WooCommerce orders
$args = array(
    'payment_method_title' => array('PayPal', 'Cash on Delivery', 'Direct bank transfer'),
    'limit' => -1 // To Retrieve all the matching orders 
);
$orders = wc_get_orders($args);
?>
<div class="wrap">
    <h1>Order Ledgers</h1>
    <!-- Placeholder for loader -->
    <div id="loader" style="display: none;">
        <img src="<?= plugins_url('media/spinneradminledgerupdate.gif', __FILE__); ?>" alt="Loading...">
    </div>
    <div id="success-message" style="display: none; color: green; font-weight: bold;"></div>
    <table class="widefat fixed">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Name</th>
                <th>Email Address</th>
                <th>Total Price</th>
                <th>Received Amount</th>
                <th>Balance Amount</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order) : 
                $order_id = $order->get_id();
                
                // Fetch the ledger data for the current order
                $query = $wpdb->prepare("SELECT * FROM $table_name WHERE order_id = %d", $order_id);
                $orderLedgerRow = $wpdb->get_row($query);

                // Set default values if no data is found
                $received_amount = $orderLedgerRow ? get_woocommerce_currency_symbol($order->get_currency()) . $orderLedgerRow->received_amount : 'Not updated';
                $balance_amount = $orderLedgerRow ? get_woocommerce_currency_symbol($order->get_currency()) . $orderLedgerRow->balance : 'Not updated';
            ?>
                <tr>
                    <td><span><?= esc_html($order_id); ?></span></td>
                    <td><span><?= esc_html($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name()); ?></span></td>
                    <td><span><?= esc_html(($a = get_userdata($order->get_user_id())) ? $a->user_email : 'Guest User'); ?></span></td>
                    <td>
                        <span><?= get_woocommerce_currency_symbol($order->get_currency()) . wc_format_decimal($order->get_total(), 2); ?></span>
                    </td>
                    <td id="col-received-amount_<?= esc_attr($order_id); ?>"><?= esc_html($received_amount); ?></td>
                    <td id="col-balance-amount_<?= esc_attr($order_id); ?>"><?= esc_html($balance_amount); ?></td>
                    <td>
                    <form class="ledger-form" data-order-id="<?= esc_attr($order_id); ?>">
                        <input type="hidden" name="order_id" class="order_id" value="<?= esc_attr($order_id); ?>">
                        <input type="hidden" name="customer_email" class="customer_email" value="<?= esc_html(($a = get_userdata($order->get_user_id())) ? $a->user_email : 'Guest User'); ?>">
                        <input type="hidden" name="order_total" id="order_total_<?= esc_attr($order_id); ?>" value="<?= esc_attr(wc_format_decimal($order->get_total(), 2)); ?>">
                        <input type="hidden" name="order_currency_code" class="order_currency_code" value="<?= $order->get_currency() ?>">
                        <input type="number" name="received_amount" class="received_amount" required placeholder="Enter received amount" />
                        <button type="button" class="update-ledgers button button-primary">Update</button>
                        <button type="button" class="ledger-history button button-dark"><span class="button-ledger-text">Ledger History</span></button>
                    </form>
                    <div id="ledgerHistoryModal" class="modal">
                        <div class="modal-content">
                            <span class="close-button">&times;</span>
                            <h2>Ledger History</h2>
                            <button id="download-csv" class="button button-secondary">Download CSV</button>
                            <div id="ledger-history-content">
                                <!-- AJAX-loaded content will be inserted here -->
                            </div>
                        </div>
                    </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
