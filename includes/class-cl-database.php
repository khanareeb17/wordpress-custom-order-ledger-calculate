<?php

class CL_Database
{
    public static function create_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'order_ledgers';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            customer_email varchar(35) NOT NULL,
            received_amount decimal(10,2) DEFAULT 0.00 NOT NULL,
            balance decimal(10,2) DEFAULT 0.00 NOT NULL,
            order_total decimal(10,2) DEFAULT 0.00 NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

    // Ledger history table
        $ledger_history_table = $wpdb->prefix . 'orders_ledger_history';
        $ledger_history_sql = "CREATE TABLE $ledger_history_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id mediumint(9) NOT NULL,
            user_email varchar(50) NOT NULL,
            payment_phase varchar(50) NOT NULL,
            previous_amount_paid decimal(10,2) DEFAULT 0.00,
            previous_balance_was decimal(10,2) NOT NULL,
            change_timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";


        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($ledger_history_sql);
    }

    public static function drop_table()
    {
        global $wpdb;
        $order_ledger_table = $wpdb->prefix . 'order_ledgers';
        $ledger_history_table = $wpdb->prefix . 'orders_ledger_history';
        $wpdb->query("DROP TABLE IF EXISTS $order_ledger_table;");
        $wpdb->query("DROP TABLE IF EXISTS $ledger_history_table;");
    }
}
