<?php

// If this file is called directly, abort.
defined('WPINC') || die;

abstract class LNP_SettingsPage
{
    protected $settings_path;
    protected $template_html;
    protected $option_name;
    
    protected $tabs;
    protected $form_fields;

    public $page;
    public $options;
    public $plugin;

    protected $page_title;
    protected $menu_title;

    public function __construct($plugin = false, $page = '')
    {
        $this->page        = $page;
        $this->plugin      = $plugin;
        $this->options     = array();
        $this->form_fields = array();
        
        $this->set_translations();
        $this->set_options();
        
        add_action( 'admin_menu', array($this, 'init_page') );
        add_action( 'admin_init', array($this, 'init_fields') );
    }


    /**
     * Add Menu Item page
     */
    public function init_page()
    {
        add_submenu_page(
            $this->page,
            $this->page_title,
            $this->menu_title,
            'manage_options',
            $this->settings_path,
            array($this, 'renderer')
        );
    }


    /**
     * Add sections and fields to page
     */
    public function init_fields()
    {
        // Load fields for each page from array
        $this->set_form_fields();

        // Register option
        // This will register my_option array with WP
        // WP will then store all fields as array field_name => field_value
        register_setting(
            $this->settings_path,
            $this->option_name, 
            array($this, 'sanitize')
        );

        // Register Tabbed sections
        // This will register tabs as sections 
        if ( ! empty($this->tabs) )
        {
            // Create sections on the page
            foreach( $this->tabs as $id => $args )
            {
                add_settings_section(
                    "{$this->option_name}_section_{$id}",
                    $args['title'],
                    null,
                    $this->option_name,
                );
            }

            // Register fields for each tab/section
            // @param $args = field array that contains input field definition
            foreach( $this->form_fields as $args )
            {
                add_settings_field(
                    $args['field']['name'],
                    $args['field']['label'],
                    array($this, 'get_input'),
                    $this->option_name,
                    "{$this->option_name}_section_"  . $args['tab'],
                    array(
                        'args' => $args
                    ),
                );
            }
        }
    }


    /**
     * Load options page HTML template 
     */
    public function renderer()
    {

        if ( empty($this->template_html) )
            return;

        // Show admin notice bar above tabs
        settings_errors();

        // Include HTML file
        include $this->get_template_path($this->template_html);
    }


    public function sanitize($inputs)
    {
        $new_input = array();

        if ( is_array( $inputs) )
        {
            foreach ($inputs as $key => $input)
            {
                if (isset($input) && is_string($input)) {
                    $new_input[$key] = sanitize_text_field($input);
                }
            }
        }

        return $inputs;
    }


    public function get_field_value( $field )
    {
        $name = is_array($field)
            ? $field['field']['name']
            : $field;

        if ( ! isset($this->options[$name]) )
        {
            return '';
        }

        return esc_attr($this->options[$name]);
    }


    /**
     * Get currently active tab from $_GET
     * @return string or first tab
     */
    public function get_active_tab_id() {

        return isset($_GET['tab'])
            ? sanitize_text_field($_GET['tab'])
            : key($this->tabs);
    }


    /**
     * Path to template file which will render options page
     * 
     * @param  string $filename [description]
     * @return strnig path to file or error if does not exist
     */
    protected function get_template_path( $filename = '' ) {

        $path = sprintf(
            '%s/admin/templates/%s',
            WP_LN_ROOT_PATH,
            $filename
        );

        // Notice to developer
        if ( ! file_exists($path) )
        {
            _e('Error: Settings template does not exist', 'lnp-alby');
        }

        return $path;
    }


    protected function get_page_title()
    {
        return $this->page_title;
    }

    protected function get_menu_title()
    {
        return $this->menu_title;
    }


    protected function set_translations() {}
    protected function set_form_fields() {}


    /**
     * Load all options for current settings page from DB
     */
    protected function set_options() {

        $option_name = $this->option_name;

        // Load options for current tab only
        if ( $this->tabs )
        {
            $tab = isset($_GET['tab'])
                ? $_GET['tab']
                : key($this->tabs);

            $option_name = "{$this->option_name}_{$tab}";
        }

        $this->options = get_option($option_name);
    }


    /**
     * Check if user is on this settings page
     * Use this to fire page specific code
     * 
     * @return boolean [description]
     */
    public function is_current_page()
    {
        return (
            isset($_GET['page']) && strval($_GET['page']) == $this->settings_path
        );
    }



    public function add_admin_notice( $message = NULL, $type = 'info' ) {

        if ( NULL === $message )
            return;

        $class = 'notice notice-' . $type;
        printf(
            '<div class="%1$s"><p>%2$s</p></div>',
            esc_attr( $class ),
            esc_html( $message )
        );
    }




    /**
     * Display all sections from a page as tabs
     */
    public function do_tabs_settings_section_nav() {

        $active   = $this->get_active_tab_id();
        $output   = array();
        $output[] = '<h2 class="nav-tab-wrapper">';
        
        foreach ( $this->tabs as $id => $args)
        {
            $output[] = sprintf(
                '<a href="#%s" class="%s">%s</a>',
                $id,
                ($id == $active) ? 'nav-tab nav-tab-active' : 'nav-tab',
                $args['title']
            );
        }

        $output[] = '</h2>';

        echo join('', $output);
    }


    /**
     * Display all sections from a page as tabs
     */
    public function do_tabs_settings_section() {

        $active = $this->get_active_tab_id();

        foreach ( $this->tabs as $id => $args) 
        {
            // Open Tab
            printf(
                '<div id="%s" class="tab-content%s">',
                $id,
                ($id == $active)
                    ? ' tab-content-active'
                    : '',
            );

            if ( ! empty($args['description']) )
            {
                echo wpautop( $args['description'] );
            }

            do_action( "lnp_tab_before_{$id}" );

            // Open Table
            echo '<table class="form-table" role="presentation">';

            // Render fields
            $this->do_section_settings_fields( $id );

            // Cloase table and tab
            echo '</table></div>';

            do_action( "lnp_tab_after_{$id}" );
        }
    }


    /**
     * Display settings field from a page section in WP styled options table
     * 
     * @param  [type] $tab_id [description]
     * @return mixed         Markup for options page
     */
    public function do_section_settings_fields( $tab_id = '' ) {

        global $wp_settings_fields;
        
        $page    = $this->option_name;
        $section = "{$this->option_name}_section_{$tab_id}";

        if (!isset($wp_settings_fields[$page][$section])) {
            return;
        }

        echo '<table class="form-table" role="presentation">';

        // Generate markup for each field
        foreach ( (array) $wp_settings_fields[ $page ][ $section ] as $field )
        {
            $class = '';
     
            if ( ! empty( $field['args']['class'] ) )
            {
                $class = sprintf(
                    ' class="%s"',
                    esc_attr( $field['args']['class'] )
                );
            }
     
            echo "<tr{$class}>";
     
            if ( ! empty( $field['args']['label_for'] ) )
            {
                printf(
                    '<th scope="row"><label for="%s">%s</label></th>',
                    esc_attr( $field['args']['label_for'] ),
                    $field['title']
                );
            }
            else {

                printf(
                    '<th scope="row">%s</th>',
                    $field['title']
                );
            }
     
            echo '<td>';
            call_user_func( $field['callback'], $field['args'] );
            echo '</td></tr>';
        }

        echo '</table>';
    }


    /**
     * Generate markup output for <input> element
     * 
     * @param  string $option_name [description]
     * @param  array   $args        [description]
     * 
     * @return misc
     */
    public function get_input( $field_args = array() )
    {
        // error_log(print_r($option_name, true));
        // error_log(print_r($args, true));

        $args = $field_args['args'];
        $name = $args['field']['name'];

        /**
         * Deafult values that will be merged with $args
         */
        $defaults = array(
            'type'         => 'text',
            'class'        => 'regular-text',
            'name'         => '',
            'value'        => $this->get_field_value($args),
            'placeholder'  => '',
            'autocomplete' => 'off',
            'label'        => '',
            'description'  => ''
        );

        
        // Merge args with defaults and filter empty values
        $parsed_args = wp_parse_args($args['field'], $defaults);
        $parsed_args = array_filter($parsed_args);
        $value       = isset($parsed_args['value'])
            ? $parsed_args['value']
            : '';

        // We will save HTML markup in array
        // and join() later
        $output = array();

        
        // Checkbox specifc
        if ( 'checkbox' == $parsed_args['type'] )
        {
            // Don't add autocomplete arg to checkbox
            unset($parsed_args['autocomplete']);
        }
        
        // HTML output starts now
        $output[] = '<input';
        
        /**
         * Will append args as input element attributes
         * Eg: <input type="text" etc...
         *
         * $skip defines which parsed_args should not be added as input attributes
         */
        $skip = array('label', 'description');

        foreach ( $parsed_args as $arg => $val )
        {
            // Don't create markup for these args
            if ( in_array($arg, $skip) )
            {
                continue;
            }

            // Append name attribute into array
            if ( 'name' == $arg )
            {
                // Append name="my_name"
                $output[] = sprintf(
                    'name="%s[%s]"',
                    $this->option_name,
                    $val
                );

                // Append id="my-name"
                $output[] = sprintf(
                    'id="%s"',
                    $val
                );

                continue;
            }

            // Append all other input attributes
            // eg: placeholder="hi@me.com"
            $output[] = sprintf(
                '%s="%s"',
                $arg,
                $val
            );
        }

        // Mark checkbox checked
        if ( 'checkbox' == $parsed_args['type'] && 'on' == $value )
        {
            $output[] = 'checked';
        }

        // Close input
        $output[] = '/>';

        
        /**
         * For checkbox type we want to display label inline with input element
         */
        if ( 'checkbox' == $parsed_args['type'] )
        {
            // Append label
            $output[] = sprintf(
                '<label for="%s">%s</label>',
                $parsed_args['name'],
                esc_attr($parsed_args['label'])
            );
        }

        /**
         * Additional description if provided
         * Extra "help" instructions block below the field
         */
        if ( ! empty($parsed_args['description']) )
        {
            $output[] = sprintf(
                '<p class="description">%s</p>',
                $parsed_args['description']
            );
        }

        // Generate and return input field html output
        echo join(' ', $output);
    }
}
