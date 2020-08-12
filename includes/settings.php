<?php

namespace CustomQuery;

class SettingsPage {

    private $options;

    public function __construct() {

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));

    }

    public function add_admin_menu() {

        add_options_page(
            __('Custom Query', 'custom-query'),
            __('Custom Query', 'custom-query'),
            'manage_options',
            'custom_query_settings',
            array($this, 'admin_page')
        );

    }

    public function admin_page() {

        $this->options = get_option('custom_query_options', array(
            'redis_host' => '127.0.0.1',
            'redis_port' => 6379,
            'redis_db' => 0
        ));

        ?>

        <div class="wrap">
            <h1><?php echo __('Instellingen Custom Query', 'custom-query'); ?></h1>
            <form method="post" action="options.php">
            <?php
                settings_fields('custom_query_options_group');
                do_settings_sections('custom_query_settings');
                submit_button();
            ?>
            </form>
        </div>

        <?php

    }

    public function settings_init() {

        register_setting(
            'custom_query_options_group',
            'custom_query_options',
            array($this, 'sanitize')
        );

        add_settings_section(
            'custom_query_settings_redis',
            __('Redis', 'youtube-search'),
            array($this, 'print_section_info_redis'),
            'custom_query_settings'
        );

        add_settings_field(
            'redis_host',
            __('Host', 'custom-query'),
            array($this, 'redis_host_callback'),
            'custom_query_settings',
            'custom_query_settings_redis'
        );

        add_settings_field(
            'redis_port',
            __('Port', 'custom-query'),
            array($this, 'redis_port_callback'),
            'custom_query_settings',
            'custom_query_settings_redis'
        );

        add_settings_field(
            'redis_db',
            __('Database', 'custom-query'),
            array($this, 'redis_db_callback'),
            'custom_query_settings',
            'custom_query_settings_redis'
        );

    }

    public function sanitize($input) {

        $new_input = array();

        if(isset( $input['redis_host']))
            $new_input['redis_host'] = sanitize_text_field($input['redis_host']);

        if(isset( $input['redis_port']))
            $new_input['redis_port'] = intval($input['redis_port']);

        if(isset( $input['redis_db']))
            $new_input['redis_db'] = intval($input['redis_db']);

        return $new_input;
    }

    public function print_section_info_redis() {

        print __('Instellingen voor de Redis-connectie', 'custom-query');

    }

    public function redis_host_callback() {

        printf(
            '<input type="text" class="regular-text" id="redis_host" name="custom_query_options[redis_host]" value="%s" />',
            isset($this->options['redis_host']) ? esc_attr($this->options['redis_host']) : ''
        );

    }

    public function redis_port_callback() {

        printf(
            '<input type="number" class="small-text" id="redis_port" name="custom_query_options[redis_port]" value="%d" />',
            isset($this->options['redis_port']) ? $this->options['redis_port'] : 6379
        );

    }

    public function redis_db_callback() {

        printf(
            '<input type="number" min="0" max="15" class="small-text" id="redis_db" name="custom_query_options[redis_db]" value="%d" />',
            isset($this->options['redis_db']) ? $this->options['redis_db'] : 0
        );

    }

}

class Settings {

    public static function register() {

        $settings_page = new SettingsPage();

    }

}
