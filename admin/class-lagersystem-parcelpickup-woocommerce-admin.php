<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://lagersystem.dk
 * @since      1.0.0
 *
 * @package    Lagersystem_Parcelpickup_Woocommerce
 * @subpackage Lagersystem_Parcelpickup_Woocommerce/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Lagersystem_Parcelpickup_Woocommerce
 * @subpackage Lagersystem_Parcelpickup_Woocommerce/admin
 * @author     Lagersystem <info@lagersystem.dk>
 */
class Lagersystem_Parcelpickup_Woocommerce_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

    private $supportedCarriers = ['','dhl','dao','gls','postnord','bring', 'ups'];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	public function settings_link($links){
        $url = "/wp-admin/options-general.php?page=lagersysten-parcelpickup-admin-page";
        $settings_link = "<a href='$url'>" . __( 'Settings' ) . '</a>';
        // Adds the link to the end of the array.
        array_unshift(
            $links,
            $settings_link
        );
        return $links;
    }


    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Lagersystem.dk Parcelpickup - Indstillinger',
            'LS Parcelpickup',
            'manage_options',
            'lagersysten-parcelpickup-admin-page',
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        ?>
        <div class="wrap">
            <h1>Lagersystem.dk Parcelpickup - Indstillinger</h1>
            Gratis plugin til at vælge udleveringsteder fra PostNord, GLS, DAO, DHL & Bring.<br/>
            Kræver en gratis API nøgle fra lagersystem.dk som kan oprettes <a href="https://lagersystem.dk/udleveringssted-modul"  target="_blank">her</a>.<br/>
            Samt en Google Maps API nøgle, læs evt. deres <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">oprettelseguide</a>.
            <br/><br/>

            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields( 'lagersystemparcelpickup_optiongroup' );
                do_settings_sections( 'lagersysten-parcelpickup-admin-page' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting('lagersystemparcelpickup_optiongroup', 'lagersystemparcelpickup_apikey');
        register_setting('lagersystemparcelpickup_optiongroup', 'lagersystemparcelpickup_gmapsapikey');
        register_setting('lagersystemparcelpickup_optiongroup', 'lagersystemparcelpickup_removeprefix');
        register_setting('lagersystemparcelpickup_optiongroup', 'lagersystemparcelpickup_daousername');
        register_setting('lagersystemparcelpickup_optiongroup', 'lagersystemparcelpickup_daopassword');
        register_setting('lagersystemparcelpickup_optiongroup', 'lagersystemparcelpickup_dhlkey');

        // UPS
        register_setting('lagersystemparcelpickup_optiongroup', 'lagersystemparcelpickup_upslicencekey');
        register_setting('lagersystemparcelpickup_optiongroup', 'lagersystemparcelpickup_upsuserid');
        register_setting('lagersystemparcelpickup_optiongroup', 'lagersystemparcelpickup_upspassword');

        // Bring
        register_setting('lagersystemparcelpickup_optiongroup', 'lagersystemparcelpickup_bringuid');
        register_setting('lagersystemparcelpickup_optiongroup', 'lagersystemparcelpickup_bringapikey');

        // register a new section in the "reading" page
        add_settings_section(
            'lagersystemparcelpickup_settings_section',
            'Parcelpickup Indstillinger',
            [$this, 'settings_section_cb'],
            'lagersysten-parcelpickup-admin-page'
        );


        // register a new field in the "wporg_settings_section" section, inside the "reading" page
        add_settings_field(
            'lagersystemparcelpickup_settings_field',
            'API Key',
            [$this, 'settings_field_cb'],
            'lagersysten-parcelpickup-admin-page',
            'lagersystemparcelpickup_settings_section'
        );

        add_settings_field(
            'lagersystemparcelpickup_settings_field_gmaps',
            'Google Maps API Key',
            [$this, 'settings_field_cb_gmaps'],
            'lagersysten-parcelpickup-admin-page',
            'lagersystemparcelpickup_settings_section'
        );

        add_settings_field(
            'lagersystemparcelpickup_settings_removeprefix',
            'Fjern "Udleveringssted" fra adresse',
            [$this, 'settings_field_cb_removeprefix'],
            'lagersysten-parcelpickup-admin-page',
            'lagersystemparcelpickup_settings_section'
        );

        // register DAO Settings.
        add_settings_section(
            'lagersystemparcelpickup_dhl_section',
            'DHL Api',
            [$this, 'settings_section_dhl'],
            'lagersysten-parcelpickup-admin-page'
        );

        add_settings_field(
            'lagersystemparcelpickup_settings_field_dhlkey',
            'DHL Consumer Key',
            [$this, 'settings_field_cb_dhlkey'],
            'lagersysten-parcelpickup-admin-page',
            'lagersystemparcelpickup_dhl_section'
        );

        // register DAO Settings.
        add_settings_section(
            'lagersystemparcelpickup_dao_section',
            'DAO Api',
            [$this, 'settings_section_dao'],
            'lagersysten-parcelpickup-admin-page'
        );

        add_settings_field(
            'lagersystemparcelpickup_settings_field_daousername',
            'DAO Login',
            [$this, 'settings_field_cb_daousername'],
            'lagersysten-parcelpickup-admin-page',
            'lagersystemparcelpickup_dao_section'
        );

        add_settings_field(
            'lagersystemparcelpickup_settings_field_daopassword',
            'DAO Password',
            [$this, 'settings_field_cb_daopassword'],
            'lagersysten-parcelpickup-admin-page',
            'lagersystemparcelpickup_dao_section'
        );


        // register UPS settings
        add_settings_section(
            'lagersystemparcelpickup_ups_section',
            'UPS Api',
            [$this, 'settings_section_ups'],
            'lagersysten-parcelpickup-admin-page'
        );

        add_settings_field(
            'lagersystemparcelpickup_settings_field_upslicencekey',
            'UPS License key',
            [$this, 'settings_field_cb_upslicencekey'],
            'lagersysten-parcelpickup-admin-page',
            'lagersystemparcelpickup_ups_section'
        );

        add_settings_field(
            'lagersystemparcelpickup_settings_field_upsuserid',
            'UPS User id',
            [$this, 'settings_field_cb_upsuserid'],
            'lagersysten-parcelpickup-admin-page',
            'lagersystemparcelpickup_ups_section'
        );
        add_settings_field(
            'lagersystemparcelpickup_settings_field_upspassword',
            'UPS Password',
            [$this, 'settings_field_cb_upspassword'],
            'lagersysten-parcelpickup-admin-page',
            'lagersystemparcelpickup_ups_section'
        );

        // register Bring settings
        add_settings_section(
            'lagersystemparcelpickup_bring_section',
            'Bring Api',
            [$this, 'settings_section_bring'],
            'lagersysten-parcelpickup-admin-page'
        );


        add_settings_field(
            'lagersystemparcelpickup_settings_field_bringuid',
            'Bring UID',
            [$this, 'settings_field_cb_bringuid'],
            'lagersysten-parcelpickup-admin-page',
            'lagersystemparcelpickup_bring_section'
        );
        add_settings_field(
            'lagersystemparcelpickup_settings_field_bringapikey',
            'Bring API key',
            [$this, 'settings_field_cb_bringapikey'],
            'lagersysten-parcelpickup-admin-page',
            'lagersystemparcelpickup_bring_section'
        );
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        echo '<p>Du kan få en gratis API nøgle ved at registrere dig på lagersystem.dk - det tager 1 minut og du modtager nøglen med det samme!</p>';
    }

    function settings_section_cb(){ }
    function settings_section_dao(){ }
    function settings_section_dhl(){ }
    function settings_section_ups(){ }
    function settings_section_bring(){ }

    // field content cb
    function settings_field_cb()
    {
        // get the value of the setting we've registered with register_setting()
        $setting = get_option('lagersystemparcelpickup_apikey');
        // output the field
        ?>
        <input type="text" name="lagersystemparcelpickup_apikey" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
        <?php
    }

    // field content cb
    function settings_field_cb_gmaps()
    {
        // get the value of the setting we've registered with register_setting()
        $setting = get_option('lagersystemparcelpickup_gmapsapikey');
        // output the field
        ?>
        <input type="text" name="lagersystemparcelpickup_gmapsapikey" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
        <?php
    }
    // field content cb
    function settings_field_cb_removeprefix()
    {
        // get the value of the setting we've registered with register_setting()
        $setting = get_option('lagersystemparcelpickup_removeprefix');
        // output the field
        ?>
        <input type="checkbox" name="lagersystemparcelpickup_removeprefix" value="1" <?php echo isset( $setting ) && $setting ? 'checked="checked"' : ''; ?>>
        <?php
    }

    // field content cb
    function settings_field_cb_dhlkey()
    {
        // get the value of the setting we've registered with register_setting()
        $setting = get_option('lagersystemparcelpickup_dhlkey');
        // output the field
        ?>
        <input type="text" name="lagersystemparcelpickup_dhlkey" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
        <?php
    }


    // field content cb
    function settings_field_cb_daousername()
    {
        // get the value of the setting we've registered with register_setting()
        $setting = get_option('lagersystemparcelpickup_daousername');
        // output the field
        ?>
        <input type="text" name="lagersystemparcelpickup_daousername" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
        <?php
    }

    // field content cb
    function settings_field_cb_daopassword()
    {
        // get the value of the setting we've registered with register_setting()
        $setting = get_option('lagersystemparcelpickup_daopassword');
        // output the field
        ?>
        <input type="text" name="lagersystemparcelpickup_daopassword" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
        <?php
    }

    // field content cb
    function settings_field_cb_upslicencekey()
    {
        // get the value of the setting we've registered with register_setting()
        $setting = get_option('lagersystemparcelpickup_upslicencekey');
        // output the field
        ?>
        <input type="text" name="lagersystemparcelpickup_upslicencekey" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
        <?php
    }

    // field content cb
    function settings_field_cb_upsuserid()
    {
        // get the value of the setting we've registered with register_setting()
        $setting = get_option('lagersystemparcelpickup_upsuserid');
        // output the field
        ?>
        <input type="text" name="lagersystemparcelpickup_upsuserid" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
        <?php
    }

    // field content cb
    function settings_field_cb_upspassword()
    {
        // get the value of the setting we've registered with register_setting()
        $setting = get_option('lagersystemparcelpickup_upspassword');
        // output the field
        ?>
        <input type="text" name="lagersystemparcelpickup_upspassword" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
        <?php
    }

    // field content cb
    function settings_field_cb_bringuid()
    {
        // get the value of the setting we've registered with register_setting()
        $setting = get_option('lagersystemparcelpickup_bringuid');
        // output the field
        ?>
        <input type="text" name="lagersystemparcelpickup_bringuid" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
        <?php
    }

    // field content cb
    function settings_field_cb_bringapikey()
    {
        // get the value of the setting we've registered with register_setting()
        $setting = get_option('lagersystemparcelpickup_bringapikey');
        // output the field
        ?>
        <input type="text" name="lagersystemparcelpickup_bringapikey" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
        <?php
    }

    public function showPrettyParcelIdInAdmin($order){
	    $deliveryId = get_post_meta( $order->id, 'Pakkeshop', true );
	    if($deliveryId) {
            echo '<p><strong>' . __('Udleverings ID') . ':</strong> ' . $deliveryId . '</p>';
        }

    }

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Lagersystem_Parcelpickup_Woocommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Lagersystem_Parcelpickup_Woocommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/lagersystem-parcelpickup-woocommerce-admin.css', array(), $this->version, 'all' );

	}

	public function register_shippingoptions(){
        if (!function_exists('WC')) {
            return;
        }
	    //loop all types, and attach settings for pickup or not.
        foreach ( WC()->shipping()->get_shipping_methods() as $method ) {
            if ( ! $method->supports( 'shipping-zones' ) ) {
                continue;
            }
            switch ( $method->id ) {
                case 'flexible_shipping':
                    add_filter( 'flexible_shipping_method_settings', [$this, 'add_flexible_shipping_field'], 10, 2 );
                    add_filter( 'flexible_shipping_process_admin_options', [$this, 'save_flexible_shipping_field'] );
                    break;
                default:
                    add_filter( 'woocommerce_shipping_instance_form_fields_'.$method->id, [$this, 'add_shipping_field'] );
                    break;
            }
        }
    }

    public function add_flexible_shipping_field($settings, $shipping_method){
        $settings['lagersystem_parcelpickup_supplier'] = array(
            'title'         => 'Parcelpickup Carrier',
            'type' 	        => 'select',
            'description'	=> 'Vælg leverandør',
            'default'       => isset($shipping_method['lagersystem_parcelpickup_supplier']) ? $shipping_method['lagersystem_parcelpickup_supplier'] : '',
            'options'		=> array(
                ''	            => '- Ingen pickup -',
                'postnord'	    => 'Post Nord',
                'gls'	        => 'GLS',
                'bring'	        => 'Bring',
                'dao'	        => 'DAO',
                'dhl'	        => 'DHL',
                'ups'	        => 'UPS',
            ),
            'desc_tip'		=> true,
        );
        return $settings;
    }

    public function save_flexible_shipping_field($shipping_method){
        $carrier = isset($_POST['woocommerce_flexible_shipping_lagersystem_parcelpickup_supplier']) ? sanitize_text_field($_POST['woocommerce_flexible_shipping_lagersystem_parcelpickup_supplier']) : '';
        $carrier    = strtolower($carrier);
        if(!in_array($carrier, $this->supportedCarriers)){
            return $shipping_method; //do nothin.
        }

        $shipping_method['lagersystem_parcelpickup_supplier'] = $carrier;
        return $shipping_method;
    }

    public function add_shipping_field($settings){
        if ( !is_array( $settings ) ) $settings = array();
        $settings['lagersystem_parcelpickup_supplier'] = array(
            'title'			=> 'Parcelpickup Carrier',
            'type'			=> 'select',
            'description'	=> 'Vælg leverandør',
            'default'       => '',
            'options'		=> array(
                ''	            => '- Ingen pickup -',
                'postnord'	    => 'Post Nord',
                'gls'	        => 'GLS',
                'bring'	        => 'Bring',
                'dao'	        => 'DAO',
                'dhl'	        => 'DHL',
                'ups'	        => 'UPS',
            ),
            'desc_tip'		=> true,
        );
        return $settings;
    }

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Lagersystem_Parcelpickup_Woocommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Lagersystem_Parcelpickup_Woocommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/lagersystem-parcelpickup-woocommerce-admin.js', array( 'jquery' ), $this->version, false );

	}

}
