<?php
/**
 * Buttons
 *
 * To override this shortcode in a child theme, copy this file to your child theme's
 * theme_config/extensions/shortcodes/shortcodes/ folder.
 *
 * Optional arguments:
 * style: custom css style
 * link: the destination of a link e.g. http://themefuse.com/
 * class: css class
 * target: _blank, _self, _parent, _top
 */

function tfuse_button($atts, $content = null)
{
    extract( shortcode_atts(array('bgcolor' => '','textcolor' => '','bordercolor' => '', 'link' => '#', 'class' => '', 'target' => '_self'), $atts) );
    $style = '';
    $class = 'btn ' . $class;
    if ( !empty($bgcolor) || !empty($textcolor) || !empty($bordercolor))
    {
        $style = ' style="background-color:'.$bgcolor.';color:'.$textcolor.';border:1px solid '.$bordercolor.';"';
        return '<a href="' . $link . '" class="' . $class . '"  target="' . $target . '"><span ' . $style . '>' . $content . '</span></a>&nbsp;&nbsp;';
    }
    else{
        return '<a href="' . $link . '" class="' . $class . '"  target="' . $target . '"' . $style . '><span>' . $content . '</span></a>';
    }
}

$atts = array(
    'name' => __('Buttons','tfuse'),
    'desc' => __('Here comes some lorem ipsum description for the button shortcode.','tfuse'),
    'category' => 2,
    'options' => array(
        array(
            'name' => __('Class','tfuse'),
            'desc' => __('Specifies one or more class names for an shortcode, separated by space.<br /><b>predefined classes:</b> btn-transparent, btn-white, btn-white-transparent, btn-light, btn-light-transparent','tfuse'),
            'id' => 'tf_shc_button_class',
            'value' => '',
            'type' => 'text'
        ),
        array(
            'name' => __('Link','tfuse'),
            'desc' => __('Specifies the URL of the page the link goes to','tfuse'),
            'id' => 'tf_shc_button_link',
            'value' => '#',
            'type' => 'text'
        ),
        array(
            'name' => __('Content','tfuse'),
            'desc' => __('Enter shortcode content','tfuse'),
            'id' => 'tf_shc_button_content',
            'value' => 'button',
            'type' => 'textarea'
        ),
        array(
            'name' => __('Bg Color','tfuse'),
            'desc' => __('Specify the background color for button ex: #fff','tfuse'),
            'id' => 'tf_shc_button_bgcolor',
            'value' => '',
            'type' => 'colorpicker'
        ),
        array(
            'name' => __('Text Color','tfuse'),
            'desc' => __('Specify the text color for button ex: #ccc','tfuse'),
            'id' => 'tf_shc_button_textcolor',
            'value' => '',
            'type' => 'colorpicker'
        ),
        array(
            'name' => __('Border Color','tfuse'),
            'desc' => __('Specify the border color for button ex: #000','tfuse'),
            'id' => 'tf_shc_button_bordercolor',
            'value' => '',
            'type' => 'colorpicker'
        ),
        array(
            'name' => __('Target','tfuse'),
            'desc' => __('Specifies where to open the linked shortcode','tfuse'),
            'id' => 'tf_shc_button_target',
            'value' => '_self',
            'options' => array(
                '_blank' => __('Opens the link in a new window or tab','tfuse'),
                '_parent' => __('Opens the link in the parent frame','tfuse'),
                '_self' => __('Opens the link in the same frame as it was clicked (this is default)','tfuse'),
                '_top' => __('Opens the link in the full body of the window','tfuse'),
            ),
            'type' => 'select'
        ),
    )
);

tf_add_shortcode('button', 'tfuse_button', $atts);