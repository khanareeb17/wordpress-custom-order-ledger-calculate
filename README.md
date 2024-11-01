# WordPress Custom Order Ledger Calculator

A custom WordPress plugin developed to calculate order ledgers for WooCommerce orders, providing an organized summary of order amounts manually. You will get a separate manu where all the upcoming orders placed with **'PayPal'**, **'Cash on Delivery'**, **'Direct bank transfer'** will be shown to the grid. 

## Features
- Calculates and displays order ledger summaries for WooCommerce orders.
- Simple integration with WordPress and WooCommerce.
- Lightweight and efficient with minimal impact on performance.

---

## Installation

There are two ways to install the plugin:

### Method 1: Install via WordPress Admin

1. Download the latest release of the plugin from the GitHub repository or zip the plugin folder.
2. In your WordPress admin dashboard, go to **Plugins > Add New**.
3. Click on **Upload Plugin**, then **Choose File** and select the plugin `.zip` file.
4. Click **Install Now** and activate the plugin once uploaded.

### Method 2: Install via `wp-content/plugins` Directory

1. Download the latest release of the plugin or clone this repository:
   ```bash
   git clone https://github.com/khanareeb17/wordpress-custom-order-ledger-calculate.git
   ```
## NOTES 1:
1. If the database table is not created after installation.
2. Open the **car-ledger.php** file inside the path **wordpress-custom-order-ledger-calculate/car-ledger.php** in the plugin's root directory
3. Uncomment the following lines
 register_activation_hook(__FILE__, 'cl_flush_rewrite_rules');
 register_deactivation_hook(__FILE__, 'cl_rewrite_flush');
4. After making these changes, deactivate the plugin and then activate it again. This should trigger the database table creation

## NOTES 2: 
### IF YOU WANT TO SHOW ORDERS FOR OTHER PAYMENT METHODS RATHER THAN **'PayPal'**, **'Cash on Delivery'**, **'Direct bank transfer'** 
1. You can go to the file **wordpress-custom-order-ledger-calculate/views/admin-view.php** and add the name of the payment method inside the $args array. 