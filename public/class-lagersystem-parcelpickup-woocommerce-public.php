<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://lagersystem.dk
 * @since      1.0.0
 *
 * @package    Lagersystem_Parcelpickup_Woocommerce
 * @subpackage Lagersystem_Parcelpickup_Woocommerce/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Lagersystem_Parcelpickup_Woocommerce
 * @subpackage Lagersystem_Parcelpickup_Woocommerce/public
 * @author     Lagersystem <info@lagersystem.dk>
 */
class Lagersystem_Parcelpickup_Woocommerce_Public {

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

	private $apiHost = 'https://parcelapi.lagersystem.dk';

	private $supportedCarriers = ['dao','dhl','gls','postnord','bring','ups'];


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	private function is_lagersystem_shipping($method) {
        if (!function_exists('WC')) {
            return false;
        }
        $shipping_methods = WC()->session->get('shipping_for_package_0')['rates'];
        foreach ( $shipping_methods as $method_id => $shipping_rate ) {
            $method_id = $shipping_rate->method_id;
            $instance_id = $shipping_rate->instance_id;

            if ($method != $method_id.":".$instance_id) {
                continue;
            }

            //default woocommerce
            $options = get_option('woocommerce_' . $method_id . '_' . $instance_id . '_settings', array());
            if (isset($options['lagersystem_parcelpickup_supplier']) && $options['lagersystem_parcelpickup_supplier']) {
                return true;
            }

            //flexible shipping.
            $metaData = $shipping_rate->get_meta_data();
            if(isset($metaData['_fs_method']['lagersystem_parcelpickup_supplier']) && $metaData['_fs_method']['lagersystem_parcelpickup_supplier']) {
                $carrier = $metaData['_fs_method']['lagersystem_parcelpickup_supplier'];
                $combined_instance_id = $metaData['_fs_method']['id_for_shipping'];

                if ($combined_instance_id == $method_id) {
                    return true;
                }
            }

        }
        return false;
    }

    public function lagersystem_validate_pickup( $fields, $errors ){
        if ($this->is_lagersystem_shipping($fields['shipping_method'][0])) {
            if(empty( $_POST['lagersystem-parcelpickup-data'] ) ) {
                $errors->add('validation', __('Intet udleveringssted er valgt'));
            }
        }

    }

	public function transferShippingInfo($order){
        if( !empty( $_POST['lagersystem-parcelpickup-data'] ) ){

            $json = str_replace('\"', '"', sanitize_textarea_field($_POST['lagersystem-parcelpickup-data']));
            $data = json_decode($json);
            $billing = $order->get_address('billing');
            $address = array(
                'first_name' => $billing['first_name'],
                'last_name'  => $billing['last_name'],
                'company'    => get_option('lagersystemparcelpickup_removeprefix') ? $data->title : "Udleveringssted: {$data->title}",
                'email'      => $billing['email'],
                'phone'      => $billing['phone'],
                'address_1'  => $data->street,
                'address_2'  => '',
                'city'       => $data->city,
                'state'      => '',
                'postcode'   => $data->zip,
                'country'    => $billing['country']
            );
            $order->set_address( $address, 'shipping' );
            return $order;
        }
    }

	function saveLocationBack($order_id){
        if( !empty( $_POST['lagersystem-parcelpickup-id'] ) ){
            update_post_meta( $order_id, 'Pakkeshop', sanitize_text_field( $_POST['lagersystem-parcelpickup-id'] ) );
        }
    }

    function woocommerce_review_fragment($array) {
        $array[".lagersystem-parcelpickup-container"] = $this->generate_pickup_choooser();
	    return $array;
    }

    function show_pickup_choooser() {
	    echo $this->generate_pickup_choooser();
    }

    function generate_pickup_choooser() {
        if (!function_exists('WC')) {
            return '';
        }
        $shipping = WC()->session->get('shipping_for_package_0');
        if ($shipping == null || !is_array($shipping)) {
            return '';
        }
        $shipping_methods = $shipping['rates'];
        if (!is_array($shipping_methods)) {
            return '';
        }
        $content = '<div class="lagersystem-parcelpickup-container">';
        $content .= '<div class="lagersystem-parcelpickup-header">Udleveringssted</div>';
       //output IDs for the inputs that need, choose, then use JS to handle UI.
        $uniqueListOfInstanceIDs = [];

        foreach ( $shipping_methods as $method_id => $shipping_rate ){
            $method_id      = $shipping_rate->method_id;
            $instance_id    = $shipping_rate->instance_id;

            //default woocommerce
            $options = get_option( 'woocommerce_'.$method_id.'_'.$instance_id.'_settings', array() );
            if(isset($options['lagersystem_parcelpickup_supplier']) && $options['lagersystem_parcelpickup_supplier']){
                //we have a carrier active.
                $combined_instance_id = $method_id.':'.$instance_id;
                if(in_array($combined_instance_id, $uniqueListOfInstanceIDs)){
                    continue;
                }
                $uniqueListOfInstanceIDs[] = $combined_instance_id;
                $carrier = $options['lagersystem_parcelpickup_supplier'];
                $content .= '<div style="display:none;" class="lagersystem-parcelpickup" data-carrier="'.$carrier.'" data-methodid="'.$method_id.'" data-instanceid="'.$combined_instance_id.'"></div>';
            }

            //flexible shipping.
            $metaData = $shipping_rate->get_meta_data();
            if(isset($metaData['_fs_method']['lagersystem_parcelpickup_supplier']) && $metaData['_fs_method']['lagersystem_parcelpickup_supplier']){
                $carrier = $metaData['_fs_method']['lagersystem_parcelpickup_supplier'];
                $combined_instance_id = $metaData['_fs_method']['id_for_shipping'];

                if(in_array($combined_instance_id, $uniqueListOfInstanceIDs)){
                    continue;
                }
                $uniqueListOfInstanceIDs[] = $combined_instance_id;

                $content .= '<div style="display:none;" class="lagersystem-parcelpickup" data-carrier="'.$carrier.'" data-methodid="'.$method_id.'" data-instanceid="'.$combined_instance_id.'"></div>';
            }
        }

        //input holder.
        $content .= '<input type="hidden" name="lagersystem-parcelpickup-id" autocomplete="off" id="lagersystem-parcelpickup-id" value="">';
        $content .=  '<input type="hidden" name="lagersystem-parcelpickup-data" autocomplete="off" id="lagersystem-parcelpickup-data" value="">';
        $content .= $this->outputPopup();

        $content .=  '</div>';

        return $content;
    }


    public function outputPopup(){
        $imgPath = plugin_dir_url( __FILE__ ) . 'img/';

	    $content = '<div style="display:none;">';
        $content .= '<div id="lagersystem-parcelpickup-popup" class="white-popup" data-imgpath="'.$imgPath.'">';
        $content .= file_get_contents(__DIR__."/partials/lagersystem-parcelpickup-locator.html");
        $content .= '</div>';
        $content .= '</div>';
        return $content;
    }

    public function search_parcels(){
        $carrier	= isset($_POST['carrier'])?trim(sanitize_text_field($_POST['carrier'])):"";
        $carrier    = strtolower($carrier);
        if(!in_array($carrier, $this->supportedCarriers)){
            echo json_encode(['error' => 'Unknown carrier.']);
            exit;
        }

        $address	= isset($_POST['address'])?trim(sanitize_text_field($_POST['address'])):"";
        $zip	    = isset($_POST['zip'])?trim(sanitize_text_field($_POST['zip'])):""; //zipcodes can be text in some countries.
        $city	    = isset($_POST['city'])?trim(sanitize_text_field($_POST['city'])):"";
        $country	= isset($_POST['country'])?trim(strtoupper(sanitize_text_field($_POST['country']))):"DK";
        if ( strlen( $country ) > 2 ) {
            $country = substr( $country, 0, 2 ); //ISO codes.
        }

        $body = array(
            'street' => $address,
            'zip' => $zip,
            'city' => $city,
            'country' => $country
        );

        if($carrier == 'dao'){
            //requires sending login.
            $body['dao-username'] = get_option('lagersystemparcelpickup_daousername');
            $body['dao-password'] = get_option('lagersystemparcelpickup_daopassword');
        }

        if($carrier == 'dhl'){
            //requires sending key.
            $body['dhl-key'] = get_option('lagersystemparcelpickup_dhlkey');
        }

        if ($carrier == 'ups'){
            //requires sending licencekey, userid,password.
            $body['ups-licencekey'] = get_option('lagersystemparcelpickup_upslicencekey');
            $body['ups-userid'] = get_option('lagersystemparcelpickup_upsuserid');
            $body['ups-password'] = get_option('lagersystemparcelpickup_upspassword');
        }

        if($carrier == 'bring'){
            $body['bring-uid'] = get_option('lagersystemparcelpickup_bringuid');
            $body['bring-apikey'] = get_option('lagersystemparcelpickup_bringapikey');
        }

        $key = 'LS-PARCEL-'.strtoupper($carrier)."-".md5(serialize($body));
        if (false !== ($result = get_transient($key))){
            //result is in cache
            echo $result;
            exit;
        }

        $url = $this->apiHost."/api/search/{$carrier}";

        $response = wp_remote_post( $url, array(
                'method'      => 'POST',
                'timeout'     => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array(
                    'X-APIKEY' => get_option('lagersystemparcelpickup_apikey'),
                ),
                'body'        => $body,
            )
        );

        if ( is_wp_error( $response ) ) {
            echo json_encode(['error' => 'Cannot locate parcels.']);
            exit;
        }

        //add to cache
        set_transient($key, $response['body'], 5 * DAY_IN_SECONDS);

        //forward response.
        echo $response['body'];
        exit;
    }

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		if(is_checkout()) {
            wp_enqueue_style('magnificPopupCss', plugin_dir_url(__FILE__) . 'css/magnificpopup.css', [], $this->version, 'all');
            wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/lagersystem-parcelpickup-woocommerce-public.css', [], $this->version, 'all');
        }

    }

    /**
	 * Register the JavaScript for the public-facing side of the site.
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


        if(is_checkout()) {
            wp_register_script( 'lsadminajax', '' );
            wp_enqueue_script('lsadminajax');
            wp_add_inline_script('lsadminajax', 'var lsAdminAjax = "'.admin_url('admin-ajax.php').'";');

            wp_enqueue_script('magnificPopupJs', plugin_dir_url(__FILE__) . 'js/magnificpopup.jquery.min.js', ['jquery'], $this->version, true);
            wp_enqueue_script($this->plugin_name . 'locator', plugin_dir_url(__FILE__) . 'js/lagersystem-parcelpickup-locator.js', ['jquery'], $this->version, true);
            wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/lagersystem-parcelpickup-woocommerce-public.js', ['jquery'], $this->version, true);

            $gmapsApiKey = get_option('lagersystemparcelpickup_gmapsapikey');
            wp_enqueue_script('gmaps', 'https://maps.googleapis.com/maps/api/js?sensor=false&callback=lagersystemparcelpickupgmapinit&key=' . $gmapsApiKey, ['jquery'], $this->version, true);
        }
    }

}
