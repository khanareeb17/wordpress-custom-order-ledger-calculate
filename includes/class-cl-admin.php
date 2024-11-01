<?php

class CL_Admin {
    
    public static function add_menu() {
        add_menu_page(
            'Car Ledger',
            'Ledger',
            'manage_options',
            'car_ledger',
            array('CL_Admin', 'render_admin_page'),
            'dashicons-money',
            20
        );
    }

    public static function render_admin_page() {
        include(plugin_dir_path(__FILE__) . '../views/admin-view.php');
    }

    public static function enqueue_scripts($hook) {
        // Load the script only on the custom admin page
        if ($hook != 'toplevel_page_car_ledger') {
            return;
        }
    
         // Register and enqueue the DataTables CSS and JS
        wp_enqueue_style('datatable-css', 'https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css');
        wp_enqueue_script('datatable-js', 'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js', array('jquery'), '', true);
        wp_register_script('ajax-js', plugin_dir_url(__FILE__) . '../admin.js', array('jquery', 'datatable-js'), '', true);
        // Register and enqueue the script
        wp_register_script('ajax-js', plugin_dir_url(__FILE__) . '../admin.js', array('jquery'), '', true);
        wp_localize_script('ajax-js', 'ajaxJsData', array('ajax_url' => admin_url('admin-ajax.php')));
        wp_enqueue_script('ajax-js');
    }

    public static function enqueue_styles() {
        wp_enqueue_style('cl-admin-style', plugin_dir_url(__FILE__) . '../css/admin-style.css');
    }

    public static function update_ledger()
    {
        global $wpdb;
        global  $woocommerce;
        $table_name = $wpdb->prefix . 'order_ledgers';
        $table_name_ledger_history = $wpdb->prefix . 'orders_ledger_history';
        $paymentCounter = 0;
    
        // Retrieve and sanitize POST data
        $orderId = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $user_email =  $_POST['user_email'];
        $received_amount = isset($_POST['received_amount']) ? floatval($_POST['received_amount']) : 0.0;
        $orderTotal = isset($_POST['order_total']) ? floatval($_POST['order_total']) : 0.0;
        $balanceAmount = $orderTotal - $received_amount;
    
        // Query to check if the ledger entry exists
        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE order_id = %d", $orderId);
        $orderLedgerRow = $wpdb->get_row($query);
    
        if ($orderLedgerRow) {
            // Existing entry found, determine the next payment phase
            $currentPhase = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(payment_phase) FROM $table_name_ledger_history WHERE order_id = %d",
                $orderId
            ));
            
            $nextPhaseNumber = $currentPhase ? (intval(substr($currentPhase, -1)) + 1) : 1;
            $paymentPhase = 'Payment: ' . $nextPhaseNumber;
        } else {
            $paymentPhase = 'Payment: 1';
        }
        if (!$orderLedgerRow) {
            $ledgerHistoryInsert = $wpdb->insert(
                $table_name_ledger_history,
                array(
                    'order_id' => $orderId,
                    'user_email' => $user_email,
                    'payment_phase' => $paymentPhase,
                    'previous_amount_paid' => 0,
                    'previous_balance_was' => $orderTotal
                ),
                array(
                    '%d', // order_id is an integer
                    '%s', // user_email is a string
                    '%s', // payment_phase is a string
                    '%f', // previous_amount_paid is a float
                    '%f'
                )
            );

            $insertStatus = $wpdb->insert(
                $table_name,
                array(
                    'order_id' => $orderId,
                    'customer_email' => $user_email,
                    'received_amount' => $received_amount,
                    'balance' => $balanceAmount,
                    'order_total' => $orderTotal
                ),
                array(
                    '%d', // order_id is an integer
                    '%s', // Customer Email
                    '%f', // received_amount is a float
                    '%f'  // balance is a float
                )
            );
    
            if ($insertStatus === false) {
                wp_send_json_error(array('message' => 'Failed to add the ledger.'));
            } else {
                wp_send_json_success(array('message' => 'Ledger added successfully.'));
            }
        } else {
            // Update existing ledger entry
            $updatedBalance = $orderLedgerRow->balance - $received_amount;
            
            $ledgerHistoryInsert = $wpdb->insert(
                $table_name_ledger_history,
                array(
                    'order_id' => $orderId,
                    'user_email' => $user_email,
                    'payment_phase' => $paymentPhase,
                    'previous_amount_paid' => $orderLedgerRow->received_amount ?? 0,
                    'previous_balance_was' => $orderLedgerRow->balance ?? 0
                ),
                array(
                    '%d', // order_id is an integer
                    '%s', // user_email is a string
                    '%s', // payment_phase is a string
                    '%f', // previous_amount_paid is a float
                    '%f'  // previous_balance_was is a float
                )
            );
            $updateStatus = $wpdb->update(
                $table_name,
                array(
                    'received_amount' => $received_amount,
                    'balance' => $updatedBalance
                ),
                array('order_id' => $orderId),
                array(
                    '%f', // received_amount is a float
                    '%f'  // balance is a float
                ),
                array('%d') // order_id is an integer
            );
    
            if ($updateStatus === false && $ledgerHistoryInsert === false) {
                wp_send_json_error(array('message' => 'Failed to update the ledger OR its history.'));
            } else {
                wp_send_json_success(array('message' => 'Ledger updated successfully.'));
            }
        }
    }

    public static function fetch_ledger_history() {
        global $wpdb;
        global  $woocommerce;
        $table_name = $wpdb->prefix . 'orders_ledger_history';
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $orderCurrencyCode = $_POST['order_currency_code'];
        // Fetch ledger history
        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE order_id = %d ORDER BY change_timestamp DESC", $order_id);
        $results = $wpdb->get_results($query);
    
        if ($results) {
            ob_start();
            ?>
            <table class="widefat fixed">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Email</th>
                        <th>Payment Phase</th>
                        <th>Previously Paid</th>
                        <th>Previous Balance</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row) : ?>
                        <tr>
                            <td><?= esc_html($row->order_id); ?></td>
                            <td><?= esc_html($row->user_email); ?></td>
                            <td><?= esc_html($row->payment_phase); ?></td>
                            <td><?= get_woocommerce_currency_symbol($orderCurrencyCode) . esc_html($row->previous_amount_paid); ?></td>
                            <td><?= get_woocommerce_currency_symbol($orderCurrencyCode) . esc_html($row->previous_balance_was); ?></td>
                            <td><?= esc_html( date_i18n( get_option( 'date_format' ))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
            $content = ob_get_clean();
            wp_send_json_success($content);
        } else {
            ob_start();
            ?>
             <table class="widefat fixed">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Email</th>
                        <th>Payment Phase</th>
                        <th>Previously Paid</th>
                        <th>Previous Balance</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                   <span class="no-records-found">No records found</span>
                </tbody>
            </table>

            <?php
            $content = ob_get_clean();
            wp_send_json_error($content ?? 'No history found.');
        }
    }    
}
