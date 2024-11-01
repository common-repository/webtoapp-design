<?php 

if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Helper class to open the media gallery, and enumerate all pages.
 */

class WtadMedia
{
    function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts') );
    }
    
    function admin_enqueue_scripts()
    {
        wp_enqueue_media();
    }
    
    function getNecessaryJs($selectorInput, $selectorButton)
    {
        $selectorButton = esc_js($selectorButton);
        $selectorInput  = esc_js($selectorInput);
        
        $out = "";
        $out .= "<script>";
        $out .= "jQuery(document).ready(function($){";
        $out .= "$('{$selectorButton}').click(function(e) {";
        $out .= "e.preventDefault();";
        $out .= "var custom_media_frame = wp.media.frames.custom_media_frame = wp.media({";
        $out .= "title: '" . esc_js(__('Choose or Upload Media', 'webtoapp-design')) . "',";
        $out .= "button: {";
        $out .= "text: '" . esc_js(__('Use this media', 'webtoapp-design')) . "'";
        $out .= "},";
        $out .= "});";
        $out .= "custom_media_frame.on('select', function() {";
        $out .= "var attachment = custom_media_frame.state().get('selection').first().toJSON();";
        $out .= "$('{$selectorInput}').val(attachment.url);";
        $out .= "});";
        $out .= "custom_media_frame.open();";
        $out .= "});";
        $out .= "});";
        $out .= "</script>";

        return $out;
    }
    
    public static function dropdownPages($idDropdown, $defaultText, $idCopyToInput = null)
    {        
        $idDropdownAtt = esc_attr($idDropdown);
        $idDropdownStr = esc_js($idDropdown);
        $idCopyToInput = $idCopyToInput != null? esc_js($idCopyToInput) : null;
        
        $out = "<select id='{$idDropdownAtt}' class='btn dropdown-toggle' style='width:1em'>";

        foreach ( get_pages( array('post_status' => 'publish') ) as $page )
        {
            $val = esc_attr( get_permalink($page) );
            $title = esc_html($page->post_title);
            
            $out .= "<option value='{$val}'>{$title}</option>";
        }
    
        if( $idCopyToInput != null )
        {
            $out .= "<script>";
            $out .= "jQuery(document).ready(function($){";
            $out .= "$('#{$idDropdownStr}').change(function(e)";
            $out .= "{";
            $out .= "$('#{$idCopyToInput}').val( this.value );";
            $out .= "});";
            $out .= "});";
            $out .= "</script>";
        }
        
        $out .= "</select>";
        
        return $out;
              
    }
}

?>
