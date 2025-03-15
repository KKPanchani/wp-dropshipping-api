<?php
if (!class_exists('WPDropshippingAPISettings')) {

class WPDropshippingAPISettings{

    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
    }

    public function add_settings_page() {
        add_menu_page(
            'Dropshipping Supplier Settings',
            'Dropshipping API',
            'manage_options',
            'wp-dropshipping-api',
            [$this, 'create_admin_page'],
            'dashicons-admin-settings',
            90
        );
    }

    public function enqueue_admin_styles($hook_suffix) {
        // Only load the styles on your plugin's settings page
        if ($hook_suffix === 'toplevel_page_wp-dropshipping-api') {
            wp_enqueue_style(
                'wp-dropshipping-admin-styles',
                plugin_dir_url(__FILE__) . 'css/admin-styles.css',
                array(),
                '1.0.0'
            );
        }
    }

    public function register_settings() {
        register_setting(
            'wp_dropshipping_api_group', 
            'wp_dropshipping_suppliers'
        );

        add_settings_section(
            'wp_dropshipping_api_section',
            'Supplier Information',
            [$this, 'settings_section_callback'],
            'wp-dropshipping-api'
        );

        add_settings_field( 
            'wp_dropshipping_suppliers',
            'Suppliers :-',
            [$this, 'suppliers_field_callback'],
            'wp-dropshipping-api',
            'wp_dropshipping_api_section'
        );
    }

    public function settings_section_callback() {
        echo'<p>Manage your suppliers here. You can add multiple suppliers, each with their own API key, endpoint URL, and account number.</p>';
    }

    public function suppliers_field_callback() {
        $suppliers = get_option('wp_dropshipping_suppliers', []);
        ?>
        <div id="supplier-repeater">
            <?php foreach ($suppliers as $index => $supplier) : ?>
                <div class="supplier-item">
                    <input type="text" name="wp_dropshipping_suppliers[<?php echo $index; ?>][name]" value="<?php echo esc_attr($supplier['name']); ?>" placeholder="Supplier Name" />
                    <input type="text" name="wp_dropshipping_suppliers[<?php echo $index; ?>][account_number]" value="<?php echo esc_attr($supplier['account_number']); ?>" placeholder="Account Number" />
                    <input type="text" name="wp_dropshipping_suppliers[<?php echo $index; ?>][api_key]" value="<?php echo esc_attr($supplier['api_key']); ?>" placeholder="APIKey" />
                    <input type="text" name="wp_dropshipping_suppliers[<?php echo $index; ?>][api_url]" value="<?php echo esc_attr($supplier['api_url']); ?>" placeholder="API Endpoint URL" />
                    <button type="button" class="remove-supplierbutton">Remove</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="add-supplier" class="button">Add Supplier</button>

        <script>
            jQuery(document).ready(function($) {
                $('#add-supplier').on('click', function() {
                    var index = $('#supplier-repeater .supplier-item').length;
                    $('#supplier-repeater').append(`
                        <div class="supplier-item">
                            <input type="text" name="wp_dropshipping_suppliers[` + index + `][name]" placeholder="Supplier Name" />
                            <input type="text" name="wp_dropshipping_suppliers[` + index + `][api_key]" placeholder="API Key" />
                            <input type="text" name="wp_dropshipping_suppliers[` + index + `][api_url]" placeholder="API Endpoint URL" />
                            <input type="text" name="wp_dropshipping_suppliers[` + index + `][account_number]" placeholder="Account Number" />
                            <button type="button"class="remove-supplier button">Remove</button>
                        </div>
                    `);
                });

                $(document).on('click', '.remove-supplier', function() {
                    $(this).closest('.supplier-item').remove();
                });
            });
        </script>
        <?php
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>Dropshipping Supplier Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wp_dropshipping_api_group');
                do_settings_sections('wp-dropshipping-api');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
}