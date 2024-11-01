<?php
/*
 * Plugin Name:       webtoapp.design
 * Plugin URI:        https://webtoapp.design/?utm_source=wordpress&utm_medium=plugin&utm_campaign=homepage
 * Description:       Turn your website into an app and send push notifications to your users.
 * Version:           1.0.3
 * Requires at least: 5.8
 * Requires PHP:      7.2
 * License:           GPLv2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       webtoapp-design
 */
if ( ! defined( 'ABSPATH' ) ) exit;
require_once( dirname( __FILE__ ) . '/webtoapp_options.php' );

class WtadMain {
    public $version = "1.0.3";

    private $options;

    function __construct()
    {
        add_filter( 'wp_fatal_error_handler_enabled', '__return_false' );
        
        $this->options = new WtadOptions($this);
        
        add_action('transition_post_status', array($this, 'transition_post_status'), 10, 3 );

        add_action( 'plugins_loaded', array( $this, 'load_my_plugin_textdomain' ) ); //!
    }

    function load_my_plugin_textdomain() //!
    {
        load_plugin_textdomain( 'webtoapp-design', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }
    
    private $postPublishId = 0;
    
    function transition_post_status($new_status, $old_status, $post)    //detect when a new post is first published...
    {
        if ( $new_status == 'publish' && $old_status != 'publish' )
        {
            $this->postPublishId = $post->ID;
            
            add_action('shutdown', array($this, 'shutdown'), 1000, 0);  //but its metadata like featured image is not set yet.
        }
    }
    
    function selectPostImage($post_id)
    {
        $attachment_id = get_post_thumbnail_id($post_id);
        
        if( $attachment_id === 0 || $attachment_id === false )  //0 or false;
            return null;
        
        $out = wp_get_attachment_image_src($attachment_id, array(300, 300) );
            
        if( $out !== false )
            return $out[0];
            
        return null;
    }
    
    function shutdown()
    {
        if( $this->postPublishId != 0 )
        {
            $options = $this->options->GetOptions();
            
            if( isset($options["key"]) && isset($options["autopublish"]) && $options["autopublish"] == true )
            {
                $key = sanitize_text_field($options["key"]);
                
                $title = get_the_title($this->postPublishId);
                
                $post_url = get_permalink($this->postPublishId);
                
                $message = "";
                
                $image_url = $this->selectPostImage($this->postPublishId);
                
                $this->postPublishId = 0;
                
                $r = $this->pushNotification($key, $title, $message, $post_url, $image_url);
                
                $img = $image_url? " [" . $image_url .  "]" : "";
                
                $slug = $title . " (" . $this->getResponseText($r) . ")" . $img;    //purely for explanatory text on the options page.
                
                $options["last_auto_response"] = $slug;
                
                $this->options->SetOptions($options);
            }
            
        }
    }
    
    public function getResponseOK($response)
    {
        switch($response)
        {
            case WtadMain::$RESPONSE_OK:                    return true;
            case WtadMain::$RESPONSE_TRANSMISSION_FAILURE:  return false;
            case WtadMain::$RESPONSE_NO_KEY:                return false;
            default:                                        return false;
        }
    }
    
    public function getResponseText($response)
    {
        switch($response)
        {
            case WtadMain::$RESPONSE_OK:                    return __("Success: Notification Sent", "webtoapp-design");
            case WtadMain::$RESPONSE_TRANSMISSION_FAILURE:  return __("Server Error: Notification not sent", "webtoapp-design");
            case WtadMain::$RESPONSE_NO_KEY:                return __("API Key Error", "webtoapp-design");
            default:                                        return __("Unknown Error", "webtoapp-design");
        }
    }
    
    public static $RESPONSE_OK                   =  0;
    public static $RESPONSE_TRANSMISSION_FAILURE = -1;
    public static $RESPONSE_NO_KEY               = -2;
    public static $RESPONSE_INVALID_BODY         = -3;
    public static $RESPONSE_UNEXPECTED           = -4;
  
    function pushNotification($key, $title, $message, $url_to_open, $image_url)
    {
        $url = "https://webtoapp.design/api/global_push_notifications?key={$key}";

        $data = array(
             'title'        => $title
            ,'message'      => $message
            ,'url_to_open'  => $url_to_open
            ,'image_url'    => $image_url
        );

        $args = array(
            'body' => json_encode($data),
            'headers' => array(
                'accept' => 'application/json'
                ,'Content-Type' => 'application/json',
            ),
        );
        
        $response = wp_remote_post( sanitize_url($url), $args);
        
        if (is_wp_error($response))
        {
            //$err = $response->get_error_message();    //only for debugging
            
            return WtadMain::$RESPONSE_TRANSMISSION_FAILURE;
        }
        
        else
        {
            $code = wp_remote_retrieve_response_code($response);
            
            if( $code == 401 )
                return WtadMain::$RESPONSE_NO_KEY;
            
            else if( $code != 200 ) 
                return WtadMain::$RESPONSE_INVALID_BODY;
            
            $response_body = json_decode( wp_remote_retrieve_body($response), true);
            
            return isset($response_body["success"]) && $response_body["success"] == true? WtadMain::$RESPONSE_OK
                                                                                        : WtadMain::$RESPONSE_UNEXPECTED;
        }
    }
    
} 

$wtadMain = new WtadMain();

?>
