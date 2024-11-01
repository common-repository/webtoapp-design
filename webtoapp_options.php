<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require_once( dirname( __FILE__ ) . '/media.php' );

/*
 * Handles only the webtoapp options page.
 */

class WtadOptions
{
    private $wtadMain;
    
    private $default_options = array();

    private $options_page_hook;
    
    private $options_name = "webtoapp";
    
    private $options_group;
    
    private $media;
    
    function __construct($wtadMain)
    {
        $this->wtadMain = $wtadMain;
        
        $this->media = new WtadMedia();
        
        $this->options_group = $this->options_name."_group";
        
        add_action('admin_menu',            array($this, 'callback_admin_menu') );
        
        add_action('admin_init',            array($this, 'callback_admin_init') );
        
        add_action('admin_enqueue_scripts', array($this, 'callback_admin_enqueue_scripts') );
    }

    function callback_admin_menu()
    {
        $page_title = "webtoapp.design";
        
        $menu_title = "webtoapp.design";          /* as it appears in menu */
        
        $unique_menu_slug = "webtoapp_options_page";
        
        $this->options_page_hook = add_options_page(
            $page_title,
            $menu_title,
            'manage_options',
            $unique_menu_slug,                    /*also forms slug of url for options page*/
            array($this, 'options_page_echo') );
    }

    function callback_admin_init()
    {
        register_setting($this->options_group, $this->options_name, array($this, 'callback_sanitize') ); // group name, then name passed to get_option
    }

    function callback_admin_enqueue_scripts($hook)
    {
        if($hook != $this->options_page_hook)
            return;

        wp_enqueue_style( 'webtoapp_admin_general_css',     plugins_url('back/webtoapp.css', __FILE__),                                                              array(), $this->wtadMain->version);
            
        wp_enqueue_script('webtoapp_admin_js',              plugins_url('back/webtoapp.js',  __FILE__), array(), $this->wtadMain->version, true);
    }
    
    function callback_sanitize($options)
    {
        return $options;
    }
    
    public function SetOptions($options)
    {
        if( ! update_option($this->options_name, $options) )
        {
            $a = 42; //debugging
        }
    }
    
    public function GetOptions()
    {
        return wp_parse_args( get_option( $this->options_name, $this->default_options ), $this->default_options );
    }
   
    function header($responseText, $responseOK)
    {
        $context = $responseOK? "success" : "danger";
        
        $resp = $responseText === null ? "" : "<div class='alert alert-" . esc_attr($context) . "' role='alert'>" . esc_html($responseText) . "</div>";
        
        $logo =  esc_url( plugins_url('back/logo.svg', __FILE__) );
        
        $out = "";
        $out .= '<nav class="navbar navbar-expand-md navbar-light bg-light fixed-top shadow-sm" id="navigation-bar">';
        $out .= '<a class="navbar-brand" href="https://webtoapp.design/?utm_source=wordpress&utm_medium=plugin&utm_campaign=header">';
        $out .= "<img decoding='async' src='{$logo}' style='height:2rem; width: auto' height='144' width='144' class='d-inline-block align-top' alt='Website to App Converter webtoapp.design Logo'>";
        $out .= "<span style='float: right; margin-left:15px; margin-top:5px; font-size: 20px;'> webtoapp.design </span>";
        $out .= '</a>';

        $out .= '<div class="collapse navbar-collapse" id="navbarResponsive">';

        $out .= '</div>';
        $out .= '</nav>';

        $out .=  wp_kses_post($resp);
        
        return $out;
    }
    
    function auto($on, $lastAutoResponse)
    {
        $turnOn  = $on? "btn btn-primary active" : "btn btn-secondary";
        $turnOff = $on? "btn btn-secondary" : "btn btn-primary active";
        
        $text = $on ? __('An automatic notification will be sent when a new page is made public. The notification will contain the post\'s title and, if available, it\'s featured image.', 'webtoapp-design')
                    : __('No automatic notification will be sent when a new page is made public.', 'webtoapp-design');

        $text = esc_html($text);
                    
        $last = $lastAutoResponse == null? "" : __('Last notification:', 'webtoapp-design') . " " . esc_html($lastAutoResponse);
                           
        $out = '';
        $out .= '<div class="card mt-3">';
        $out .= '  <div class="card-header">';
        $out .= '<h3 class="title">'  . esc_html__('New Page Publish Notification', 'webtoapp-design') . '</h3>';
        $out .= '</div>';
        $out .= '<div class="card-body">';
        $out .= '<form action="" method="post" enctype="multipart/form-data">';
        $out .= wp_nonce_field('wtad', '_wpnonce', true, false);
        $out .= '<div class="btn-group btn-group-toggle" data-toggle="buttons" >';
        $out .= '<input style="color:white" class="' . esc_attr($turnOn) . '"  type="submit" name="webapp-auto-on"  autocomplete="off" value="' . esc_attr__('On', 'webtoapp-design') . '"/>';
        $out .= '<input style="color:white" class="' . esc_attr($turnOff). '" type="submit" name="webapp-auto-off" autocomplete="off" value="' . esc_attr__('Off', 'webtoapp-design') . '"/>';
        $out .= '</div>';
        $out .= '<br/><br/>';
        $out .= '<p>' . wp_kses_post($text) . '</p>';
        $out .= '<p>' . wp_kses_post($last) . '</p>';
        $out .= '</form>';
        $out .= '</div>';
        $out .= '</div>';
        
        return $out;
    }
    
    function push()
    {
        $dd = WtadMedia::dropdownPages("dd_url", __('Select a page', 'webtoapp-design'), "url_to_open");        

        $out = '';
        $out .= '<div class="card mt-3">';
        $out .= '  <div class="card-header">';
        $out .= '<h3 class="title">' . esc_html__('Push Notifications', 'webtoapp-design') . '</h3>'; 
        $out .= '</div>';
        $out .= '<div class="card-body">';
        $out .= '<form action="" method="post" enctype="multipart/form-data">';
        $out .= wp_nonce_field('wtad', '_wpnonce', true, false);
        $out .= '<p>' . esc_html__('The title or main message of your push notification.', 'webtoapp-design') . '</p>';
        $out .= '<div class="form-group">';
        $out .= '<div class="input-group">';
        $out .= '<input name="title" class="form-control" placeholder="' . esc_attr__('Title', 'webtoapp-design') . '" id="title" required="" type="text" >';
        $out .= '<label for="title" class="sr-only">'  . esc_html__('Title', 'webtoapp-design') . '</label>';
        $out .= '<p class="text-danger" id="regex-error-title" hidden=""></p>';
        $out .= '</div>';
        $out .= '</div>';
        $out .= '<p>'  . esc_html__('An optional, longer message that is shown below the title.', 'webtoapp-design') . '</p>';
        $out .= '<div class="form-group">';
        $out .= '<div class="input-group">';
        $out .= '<input name="message" class="form-control" placeholder="' . esc_attr__('Message (optional)', 'webtoapp-design') . '" id="message" type="text" >';
        $out .= '<label for="message" class="sr-only">' . esc_html__('Message (optional)', 'webtoapp-design') . '</label>';
        $out .= '<p class="text-danger" id="regex-error-message" hidden=""></p>';
        $out .= '</div>';
        $out .= '</div>';
        $out .= '<p> ' . esc_html__('This link will be opened inside your app when the notification is clicked.', 'webtoapp-design') . ' <a href="https://webtoapp.design/blog/send-push-notification#tracking-notification-clicks"> ' . esc_html__('Here\'s how you can track how many users are opening your notifications.', 'webtoapp-design') . '</a>';
        $out .= '</p>';
        $out .= '<div class="form-group">';
        $out .= '<div class="input-group">';
        $out .= '<input name="url_to_open" class="form-control" placeholder="' . esc_attr__('Link to Open on Notification Click (optional)', 'webtoapp-design') . '" id="url_to_open" type="url" >';
        $out .= '<label for="url_to_open" class="sr-only">' . esc_html__('Link to Open on Notification Click (optional)', 'webtoapp-design') . '</label>';

        $out .= '<p class="text-danger" id="regex-error-url_to_open" hidden=""></p>';
                    
        $out .= "<div class='input-group-append'>{$dd}</div>"; 
         
        $out .= '</div>';

        $out .= '</div>';
        $out .= '<p>'  . esc_html__('A link to an image that will be attached to your push notification. Requirements:', 'webtoapp-design') . '</p>';
        $out .= '<ul>';
        $out .= '<li>'  . esc_html__('Image in PNG or JPG format', 'webtoapp-design') . '</li>';
        $out .= '<li>'  . esc_html__('Image size smaller than 300KB', 'webtoapp-design') . '</li>';
        
        $out .= '</ul>';
        $out .= '<div class="form-group">';
        $out .= '<div class="input-group">';
        $out .= '<input name="image_url" class="form-control" placeholder="'  . esc_attr__('Image Link (optional)', 'webtoapp-design') . '" id="image_url" type="url" >';
        $out .= '<label for="image_url" class="sr-only">'  . esc_html__('Image Link (optional)', 'webtoapp-design') . '</label>';
        $out .= '<p class="text-danger" id="regex-error-image_url" hidden=""></p>';
        $out .= '<div class="input-group-append">';
        $out .= '<button class="btn btn-primary" type="button" id="image_media_gallery" >'  . esc_html__('Media Gallery', 'webtoapp-design') . '</button>';
        $out .= '</div>';
        $out .= '</div>';
        $out .= '</div>';
        $out .= '<button type="submit" class="btn btn-primary mt-2 btn-block" name="send_notification">'  . esc_html__('Send Notification', 'webtoapp-design') . '</button>';
        $out .= '</form>';
        $out .= '<p class="mt-3">'  . esc_html__('Having difficulties?', 'webtoapp-design') . ' <a href="https://webtoapp.design/blog/send-push-notification"> '  . esc_html__('Here\'s our guide to sending push notifications', 'webtoapp-design') . '</a> '  . esc_html__('including a section about', 'webtoapp-design') . ' <a href="https://webtoapp.design/blog/send-push-notification#not-receiving-notifications"> '  . esc_html__('why users might not be receiving notifications.', 'webtoapp-design') . '</a>';
        $out .= '</p>';
        $out .= '</div>';
        $out .= '</div>';

        $out .= $this->media->getNecessaryJs("#image_url", "#image_media_gallery");
        return $out;
    }
    
    function echoSafe($content)
    {
        $permit = array( "class" => array(), "style" => array(), "id" => array(), "type" => array(), "value" => array(), "placeholder" => array(), 
                            "name" => array(), "aria-hidden" =>array(), "href" => array(), "target" => array(),
                        "decoding" =>array(), "src" => array(), "width" =>array(), "height" =>array(), "alt" => array(), "action" =>array(), "method" =>array(), "enctype" =>array()
                            
            
        );
       
       // echo $content;
       // return;
        
        echo wp_kses($content, array(
            "button" => $permit,
            "div"    => $permit, 
            "ul"     => $permit,
            "li"     => $permit,
            "form"   => $permit,
            "p"      => $permit,
            "input"  => $permit,
            "span"   => $permit,
            "h3"     => $permit,
            "select" => $permit,
            "option" => $permit,
            "a"      => $permit,
            "i"      => $permit,
            "label"  => $permit, 
            "img"    => $permit,
            "nav"    => $permit,
            "script" => $permit
        ));
    }
    
    function options_page_echo()
    {
        $options = $this->GetOptions();
        
        $responseText = null;
        $responseOK   = false;
        
        if ( ! empty( $_POST ) )
            check_admin_referer( 'wtad' );
        
        if( isset($_POST['webtoapp-key-delete']) )
        {
            unset($options["key"]);
            
            update_option($this->options_name, $options);
        }
        
        if( isset($_POST['webtoapp-key']) )
        {
            $options["key"] = sanitize_text_field($_POST['webtoapp-key']);
            
            update_option($this->options_name, $options);
        }
        
        if( isset($_POST['send_notification']) )
        {
            $r = $this->wtadMain->pushNotification(
                    sanitize_text_field( $options["key"]),
                    sanitize_text_field($_POST['title']),
                    sanitize_text_field($_POST['message']),
                    sanitize_url($_POST['url_to_open']),
                    sanitize_url($_POST['image_url']));
            
            $responseOK   = $this->wtadMain->getResponseOK($r);
            $responseText = $this->wtadMain->getResponseText($r);
        }
        
        if( isset($_POST['webapp-auto-on']) )
        {
            $options["autopublish"] = true;
            
            update_option($this->options_name, $options);
        }
        
        if( isset($_POST['webapp-auto-off']) )
        {
            $options["autopublish"] = false;
            
            update_option($this->options_name, $options);
        }
        
        ?>
        <div class="wrap webtoapp ">  
            <div id="icon-themes" class="icon32"></div>  
            
            <?php  $this->echoSafe( $this->header($responseText, $responseOK) )?> 
            
            <?php  if( ! isset($options["key"]) ) { ?>
            
            <form id="webtoapp-set-key" method="post" enctype="multipart/form-data" action="" class="webtoapp-dashboard" >  
            	
            	<?php wp_nonce_field('wtad') ?>

                <!-- <?php esc_html_e('', 'webtoapp-design'); ?> -->
                <!-- value="<?php echo esc_attr__('', 'webtoapp-design'); ?>" -->
            	
                <h1><?php esc_html_e('Enter your API key:', 'webtoapp-design'); ?></h1>

    			<div class="input-group">
                    <input name="webtoapp-key" class="form-control" placeholder="12345-abcdefghijklmnopqrstuvwxyz-0123456789" id="webtoapp-key" type="text" minlength="43" maxlength="43">
                    <label for="webtoapp-key" class="sr-only"><?php esc_html_e('Your API key', 'webtoapp-design'); ?></label>
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit" ><?php esc_html_e('Save', 'webtoapp-design'); ?></button>
                    </div>
                </div>

    			<br/><a href="https://webtoapp.design/?utm_source=wordpress&utm_medium=plugin&utm_campaign=login" target="_blank" ><?php esc_html_e('I have not created an app yet', 'webtoapp-design'); ?></a>
    			&nbsp;|&nbsp;
    			
    			<a href="https://webtoapp.design/redirector/app_id/dashboard_bp.developer_tools?utm_source=wordpress&utm_medium=plugin&utm_campaign=login" target="_blank" ><?php esc_html_e('Find my app\'s API key', 'webtoapp-design'); ?></a>

    			
    		</form>
    				    			
    		<?php  } else { ?>
    		
           <?php $this->echoSafe($this->push()) ?>
           
           <?php $this->echoSafe($this->auto( isset($options["autopublish"]) && $options["autopublish"] == true,
                                   isset($options["last_auto_response"])? $options["last_auto_response"] : null ) ) ?>

           <form id="webtoapp-set-key" method="post" enctype="multipart/form-data" action="" class="webtoapp-dashboard" >
           	    <?php wp_nonce_field('wtad') ?>
    			<br/>
    			<p>
    			<input class="btn btn-danger url-submit-button" type="submit" value="<?php echo esc_attr_e('Delete API Key', 'webtoapp-design'); ?>" name="webtoapp-key-delete" />
    			<?php echo esc_html("&nbsp;(" . $options["key"] .")")  ?>
    			</p>
           </form>
              
           <?php } ?> 
               
        </div> 
    	<?php 
    }
    
}
?>
