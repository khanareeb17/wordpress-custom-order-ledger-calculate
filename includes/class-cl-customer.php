<?php

class CL_Customer
{
    public static function display_ledger_history_customer_account()
    {
        if (!is_user_logged_in()) {
            return '<p>Please log in to view your ledger history.</p>';
        }
        global $wpdb;
        global  $woocommerce; 
        $user_email = wp_get_current_user()->user_email;
        $customerLedgerHistory = $wpdb->prefix . 'orders_ledger_history';
        $orderLedgersTable = $wpdb->prefix . 'order_ledgers';
        // Fetch all orders for the customer
        $customer_orders = wc_get_orders([
            'customer' => $user_email,
            'limit' => -1, // No limit to fetch all
        ]);

        // Fetch ledger history from the custom table
        $query = $wpdb->prepare("SELECT * FROM $orderLedgersTable WHERE customer_email = %s", $user_email);
        $ledger_data = $wpdb->get_results($query);

        // Create a map of orders in the ledger
        $ledger_orders = [];
        foreach ($ledger_data as $ledger_row) {
            $ledger_orders[$ledger_row->order_id] = $ledger_row;
        }
        $display_data = [];
        $grand_total_received = 0;  // Initialize total for Received Amount
        $grand_total_balance = 0;   // Initialize total for Current Balance Amount
        $grand_total_orders = 0;    // Initialize total for Orders Total
        foreach ($customer_orders as $order) {
            $order_id = $order->get_id();
            $order_total = $order->get_total();
            $currency_code = $order->get_currency();

            if (isset($ledger_orders[$order_id])) {
                // Order already exists in the ledger, use ledger data
                $ledger_row = $ledger_orders[$order_id];
                $display_data[] = [
                    'order_id' => $order_id,
                    'received_amount' => $ledger_row->received_amount,
                    'balance' => $ledger_row->balance,
                    'order_total' => $order_total,
                    'currency_code' => $currency_code
                ];
                $grand_total_received += $ledger_row->received_amount;
                $grand_total_balance += $ledger_row->balance;
            } else {
                // New order, set Received Amount to 0 and Balance to Order Total
                $display_data[] = [
                    'order_id' => $order_id,
                    'received_amount' => 0,
                    'balance' => $order_total,
                    'order_total' => $order_total,
                    'currency_code' => $currency_code
                ];
                $grand_total_balance += $order_total;
            }
              // Calculate Grand Totals
              $grand_total_orders += $order_total;
        }
        ob_start();
        ?>
        <h2>Last Payment & Current Balance</h2>
        <table class="ledger-history-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Received Amount</th>
                    <th>Current Balance Amount</th>
                    <th>Orders Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($display_data): ?>
                    <?php foreach ($display_data as $data): ?>
                        <tr>
                            <td data-label="Order ID"><?= esc_html($data['order_id']) ?></td>
                            <td data-label="Received Amount"><?= get_woocommerce_currency_symbol($data['currency_code']) . esc_html($data['received_amount']); ?></td>
                            <td data-label="New Balance"><?= get_woocommerce_currency_symbol($data['currency_code']) . esc_html($data['balance']); ?></td>
                            <td data-label="Orders Total"><?= get_woocommerce_currency_symbol($data['currency_code']) . esc_html($data['order_total']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="2">No ledger history found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>Grand Total</th>
                    <th><?= get_woocommerce_currency_symbol() . esc_html($grand_total_received); ?></th>
                    <th><?= get_woocommerce_currency_symbol() . esc_html($grand_total_balance); ?></th>
                    <th><?= get_woocommerce_currency_symbol() . esc_html($grand_total_orders); ?></th>
                </tr>
            </tfoot>
        </table>
        
        <?php
        $query = $wpdb->prepare("SELECT * FROM $customerLedgerHistory WHERE user_email = %s ORDER BY change_timestamp DESC", $user_email);
        $results = $wpdb->get_results($query);
        ?>
        <h2>Customer Ledger History</h2>
        <table class="ledger-history-table" id="ledger-history">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Email</th>
                    <th>Payment Phase</th>
                    <th>Previous Paid</th>
                    <th>Previous Balance</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($results): ?>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td data-label="Order ID"><?= esc_html($row->order_id); ?></td>
                            <td data-label="Customer Email"><?= esc_html($row->user_email); ?></td>
                            <td data-label="Payment Phase"><?= esc_html($row->payment_phase); ?></td>
                            <td data-label="Previous Paid"><?= get_woocommerce_currency_symbol() . esc_html($row->previous_amount_paid); ?></td>
                            <td data-label="Previous Balance"><?= get_woocommerce_currency_symbol() . esc_html($row->previous_balance_was); ?></td>
                            <td data-label="Date"><?= esc_html(date_i18n(get_option('date_format'), strtotime($row->change_timestamp))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No ledger history found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }

   public static function cl_enqueue_customer_styles() {
        if (is_account_page()) {
            wp_enqueue_style('cl-customer-style', plugin_dir_url(__FILE__) . '../css/customer-dashboard.css');
            wp_enqueue_style('datatable-css', 'https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css');
            wp_enqueue_script('jquery');
            wp_enqueue_script('datatable-js', 'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js', array('jquery'), '', true);
            wp_enqueue_script('cl-customer-script', plugin_dir_url(__FILE__) . '../customer-dashboard.js', array('jquery', 'datatable-js'), '', true);
        }
    }
}
