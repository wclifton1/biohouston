<?php

/**
 * Newsletter
 * 
 * To override this shortcode in a child theme, copy this file to your child theme's
 * theme_config/extensions/shortcodes/shortcodes/ folder.
 * 
 * Optional arguments:
 * title: e.g. Newsletter signup
 * text: e.g. Thank you for your subscribtion.
 * action: URL where to send the form data.
 * rss_feed:
 */

function tfuse_newsletter($atts, $content = null)
{
    extract(shortcode_atts(array('title' => '', 'text' => '', 'rss_feed' => ''), $atts));

    if (empty($title))
        $title = __('Newsletter', 'tfuse');
    if (empty($text))
        $text = __('', 'tfuse');

    $out = '

<!--End mc_embed_signup-->
    <div class="widget widget_newsletter newsletter_subscription_box">
        
        <h1 class="widget-title">' . $title . '</h1> 
                <div class="newsletter_subscription_messages before-text">
                    <div class="newsletter_subscription_message_initial">
                        '. __('','tfuse').' 
                    </div>
                    <div class="newsletter_subscription_message_success">
                        '.__('Thank you for your subscription.','tfuse').'
                    </div>
                    <div class="newsletter_subscription_message_wrong_email">
                        '.__('Your email format is wrong!','tfuse') .'
                    </div>
                    <div class="newsletter_subscription_message_failed">
                        '. __('Sorry, but we couldn\'t add you to our mailing list ATM.','tfuse') .'
                    </div>
                </div>

                <form action="//biohouston.us10.list-manage.com/subscribe/post?u=7484153945d7ea797bd3ef6a8&amp;id=9369b0c79e" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" class="newsletter_subscription_form" novalidate>
                    <div class="newsletter_text">'.$text.'</div>
                        <input type="email" value="" name="EMAIL" class="email newsletter_subscription_email inputtext" id="mce-EMAIL" placeholder="'. __('Your email adress here...', 'tfuse').'" required>
                        <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
                        <div style="position: absolute; left: -5000px;"><input type="text" name="b_7484153945d7ea797bd3ef6a8_9369b0c79e" tabindex="-1" value=""></div>
                        <button type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="btn btn-newsletter newsletter_subscription_submit"><span>'.__('Subscribe','tfuse').'</span></button>
                        <div class="newsletter_subscription_ajax">'. __('Loading...','tfuse') .'</div>
                        <div class="newsletter_text">';
                if ($rss_feed != 'false') {  
                    $out .= '<a class="newssetter_subscribe" href="'.tfuse_options('feedburner_url', get_bloginfo_rss('rss2_url')).'">'. __('I also want to subscribe to the RSS Feed', 'tfuse').'</a>';
                        } 
                        $out .= '
                        </div>
                </form>
        </div>';
        

    return $out;
}

$atts = array(
    'name' => __('Newsletter','tfuse'),
    'desc' => __('Here comes some lorem ipsum description for the box shortcode.','tfuse'),
    'category' => 11,
    'options' => array(
        array(
            'name' => __('Title','tfuse'),
            'desc' => __('Enter the title of the Newsletter form','tfuse'),
            'id' => 'tf_shc_newsletter_title',
            'value' => __('Newsletter','tfuse'),
            'type' => 'text'
        ),
        array(
            'name' => __('Text','tfuse'),
            'desc' => __('Specify the newsletter message','tfuse'),
            'id' => 'tf_shc_newsletter_text',
            'value' => __('Sign up for our weekly newsletter to receive updates, news, and promos:','tfuse'),
            'type' => 'textarea'
        ),
        array(
            'name' => __('RSS Feed','tfuse'),
            'desc' => __('Show RSS Feed link?','tfuse'),
            'id' => 'tf_shc_newsletter_rss_feed',
            'value' => 'false',
            'type' => 'checkbox'
        )
    )
);

tf_add_shortcode('newsletter', 'tfuse_newsletter', $atts);
