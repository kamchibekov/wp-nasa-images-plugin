<?php

add_action('add_option_nasa_images_option_name', function ($option_name, $option_value) {
    if ($option_value) {
        $response = Requests::request('https://api.nasa.gov/planetary/apod', [
            'api_key' => $option_value,
            'date' => date('Y-M-D', strtotime('today'))
        ]);

    }
}, 10, 2);

class NasaImages
{
    private $nasa_images_options;

    public function __construct()
    {
        add_action('admin_menu', array($this, 'nasa_images_add_plugin_page'));
        add_action('admin_init', array($this, 'nasa_images_page_init'));
    }

    public function nasa_images_add_plugin_page()
    {
        add_menu_page(
            'Nasa Images', // page_title
            'Nasa Images', // menu_title
            'manage_options', // capability
            'nasa-images', // menu_slug
            array($this, 'nasa_images_create_admin_page'), // function
            'dashicons-admin-generic', // icon_url
        );
    }

    public function nasa_images_create_admin_page()
    {
        $this->nasa_images_options = get_option('nasa_images_option_name'); ?>

        <div class="wrap">
            <h2>Nasa Images</h2>
            <p>Please upload your Nasa API key at first</p>
            <?php settings_errors(); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('nasa_images_option_group');
                do_settings_sections('nasa-images-admin');
                submit_button();
                ?>
            </form>
        </div>
<?php }

    public function nasa_images_page_init()
    {
        register_setting(
            'nasa_images_option_group', // option_group
            'nasa_images_option_name', // option_name
            array($this, 'nasa_images_sanitize') // sanitize_callback
        );

        add_settings_section(
            'nasa_images_setting_section', // id
            'Settings', // title
            array($this, 'nasa_images_section_info'), // callback
            'nasa-images-admin' // page
        );

        add_settings_field(
            'api_key_0', // id
            'API Key', // title
            array($this, 'api_key_0_callback'), // callback
            'nasa-images-admin', // page
            'nasa_images_setting_section' // section
        );
    }

    public function nasa_images_sanitize($input)
    {
        $sanitary_values = array();
        if (isset($input['api_key_0'])) {
            $sanitary_values['api_key_0'] = sanitize_text_field($input['api_key_0']);
        }

        return $sanitary_values;
    }

    public function nasa_images_section_info()
    {
    }

    public function api_key_0_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="nasa_images_option_name[api_key_0]" id="api_key_0" value="%s">',
            isset($this->nasa_images_options['api_key_0']) ? esc_attr($this->nasa_images_options['api_key_0']) : ''
        );
    }
}

$nasa_images = new NasaImages();

/* 
 * Retrieve this value with:
 * $nasa_images_options = get_option( 'nasa_images_option_name' ); // Array of All Options
 * $api_key_0 = $nasa_images_options['api_key_0']; // API Key
 */