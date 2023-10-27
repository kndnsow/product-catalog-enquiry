<?php

class Woocommerce_Catalog_Enquiry_Frontend {

    public $available_for;
    public $settings = '';
    public $settings_button = '';
    public $settings_form = '';
    public $exclusion = '';

    public function __construct() {
        global $Woocommerce_Catalog_Enquiry;
        $this->settings = $Woocommerce_Catalog_Enquiry->options_general_settings;
        $this->settings_button = $Woocommerce_Catalog_Enquiry->options_button_appearence_settings;
        $this->settings_form = $Woocommerce_Catalog_Enquiry->options_form_settings;
        $this->exclusion = $Woocommerce_Catalog_Enquiry->options_exclusion_settings;
        //enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        //enqueue styles
        add_action('wp_enqueue_scripts', array($this, 'frontend_styles'));
        add_action('template_redirect', array($this, 'redirect_cart_checkout_on_conditions'));

        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $this->available_for = '';
        if (isset($this->exclusion['woocommerce_userroles_list']) && mvx_catalog_get_settings_value($this->exclusion['woocommerce_userroles_list'], 'multiselect')) {
            if (is_array(mvx_catalog_get_settings_value($this->exclusion['woocommerce_userroles_list'], 'multiselect'))) {
                foreach (mvx_catalog_get_settings_value($this->exclusion['woocommerce_userroles_list'], 'multiselect') as $user_list_key) {
                    $user_role_list[] = array_key_exists( $user_list_key, array_keys( wp_roles()->roles ) ) ? array_keys( wp_roles()->roles )[$user_list_key] : '';
                }
                if ( !empty( $current_user->roles ) && in_array($current_user->roles[0], $user_role_list ) ) {
                    $this->available_for = $current_user->ID;
                }
            }
        }
        if (isset($this->exclusion['woocommerce_user_list']) && mvx_catalog_get_settings_value($this->exclusion['woocommerce_user_list'], 'multiselect')) {
            if (is_array(mvx_catalog_get_settings_value($this->exclusion['woocommerce_user_list'], 'multiselect'))) {
                if (in_array($current_user->ID, mvx_catalog_get_settings_value($this->exclusion['woocommerce_user_list'], 'multiselect'))) {
                    $this->available_for = $current_user->ID;
                }
            }
        }
        

        $for_user_type = isset($this->settings['for_user_type']) ? mvx_catalog_get_settings_value($this->settings['for_user_type'], 'select') : '';
        if ($for_user_type == 0 || $for_user_type == 3 || $for_user_type == '') {
            $this->init_catalog();
        } else if ($for_user_type == 1) {
            if ($current_user->ID == 0) {
                $this->init_catalog();
            }
        } else if ($for_user_type == 2) {
            if ($current_user->ID != 0) {
                $this->init_catalog();
            }
        }

        if (isset($this->settings['is_enable']) && mvx_catalog_get_settings_value($this->settings['is_enable'], 'checkbox') == "Enable" && ($this->available_for == '' || $this->available_for == 0)) {
            if (isset($this->settings_button['button_type'])) {
                add_filter('woocommerce_loop_add_to_cart_link', array($this, 'woocommerce_loop_add_to_cart_link'), 99, 3);
            }
        }
        // Enquiry button shortcode
        add_shortcode('wce_enquiry_button', array($this, 'wce_enquiry_button_shortcode'));
    }

    public function redirect_cart_checkout_on_conditions() {
        global $Woocommerce_Catalog_Enquiry, $post;
        $exclusion = $Woocommerce_Catalog_Enquiry->options_exclusion_settings;
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;

        $count1 = 0;
        $count2 = 0;

        if (isset($this->settings['is_enable']) && mvx_catalog_get_settings_value($this->settings['is_enable'], 'checkbox') == "Enable") {
            if (isset($this->settings['is_hide_cart_checkout']) && mvx_catalog_get_settings_value($this->settings['is_hide_cart_checkout'], 'checkbox') == "Enable") {

                if (isset($this->exclusion['woocommerce_userroles_list']) && mvx_catalog_get_settings_value($this->exclusion['woocommerce_userroles_list'], 'multiselect')) {
                    if (is_array(mvx_catalog_get_settings_value($this->exclusion['woocommerce_userroles_list'], 'multiselect'))) {
                        $count1 = count(mvx_catalog_get_settings_value($this->exclusion['woocommerce_userroles_list'], 'multiselect'));
                    }
                }
                if (mvx_catalog_get_settings_value($this->exclusion['woocommerce_user_list'], 'multiselect')) {
                    if (is_array(mvx_catalog_get_settings_value($this->exclusion['woocommerce_user_list'], 'multiselect'))) {
                        $count2 = count(mvx_catalog_get_settings_value($this->exclusion['woocommerce_user_list'], 'multiselect'));
                    }
                }
                    
                
                $cart_page_id = wc_get_page_id('cart');
                $checkout_page_id = wc_get_page_id('checkout');
                $home_url_link = apply_filters( 'woocommerce_redirect_to_home_url', home_url() );
                if ($count2 == 0 && $count1 == 0) {

                    if (is_page($cart_page_id) || is_page($checkout_page_id)) {
                        wp_redirect($home_url_link);
                        exit;
                    }
                } else {
                    if ( isset($this->exclusion['woocommerce_userroles_list']) && mvx_catalog_get_settings_value($this->exclusion['woocommerce_userroles_list'], 'multiselect')  && !in_array($current_user->roles[0], mvx_catalog_get_settings_value($this->exclusion['woocommerce_userroles_list'], 'multiselect') ) ) {
                        if (is_page((int) $cart_page_id) || is_page($checkout_page_id)) {
                            wp_redirect($home_url_link);
                            exit;
                        }
                    }
                    if (mvx_catalog_get_settings_value($this->exclusion['woocommerce_user_list'], 'multiselect') && !in_array($current_user->ID, mvx_catalog_get_settings_value($this->exclusion['woocommerce_user_list'], 'multiselect'))) {
                        if (is_page((int) $cart_page_id) || is_page($checkout_page_id)) {
                            wp_redirect($home_url_link);
                            exit;
                        }
                    }
                }
            }
        }
    }
    
    public function woocommerce_loop_add_to_cart_link($add_to_cart_button, $product, $args = array()) {
        global $Woocommerce_Catalog_Enquiry;
        $settings = $Woocommerce_Catalog_Enquiry->options_general_settings;
        // button option

        $labels = __('Add to cart', 'woocommerce-catalog-enquiry');
        $link_add_to_cart = $product ? get_permalink($product->get_id()) : '';

        if (isset($this->settings['is_enable']) && mvx_catalog_get_settings_value($this->settings['is_enable'], 'checkbox') == "Enable") {
            $pro_link = '';
            if (isset($this->settings_button['button_type'])) {
                switch ($this->settings_button['button_type']) {
                    case 2:
                        $link = isset($this->settings_button['button_link']) && !empty($this->settings_button['button_link']) ? $this->settings_button['button_link'] : '#';
                        $label = isset($this->settings_button['enquiry_button_text']) && !empty($this->settings_button['enquiry_button_text']) ? $this->settings_button['enquiry_button_text'] : $product->add_to_cart_text();
                        $classes = implode( ' ', array('button','product_type_' . $product->get_type()));
                        
                        if (isset($this->settings['is_enable_out_of_stock']) && mvx_catalog_get_settings_value($this->settings['is_enable_out_of_stock'], 'checkbox') == "Enable") {
                            if (!$product->managing_stock() && !$product->is_in_stock()) {
                                $pro_link = sprintf( '<a id="%s" href="%s" data-quantity="%s" class="%s" %s>%s</a>',
                                    esc_attr('woocommerce-catalog-enquiry-custom-button'),
                                    esc_url( $link ),
                                    esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
                                    esc_attr( $classes ),
                                    isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
                                    esc_html( $label )
                                );
                            } else {
                                $pro_link = sprintf( '<a id="%s" href="%s" data-quantity="%s" class="%s" %s>%s</a>',
                                    esc_attr('woocommerce-catalog-enquiry-custom-button'),
                                    esc_url( $link_add_to_cart ),
                                    esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
                                    esc_attr( $classes ),
                                    isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
                                    esc_html( $labels )
                                );
                            }
                        } else {
                            $pro_link = sprintf( '<a id="%s" href="%s" data-quantity="%s" class="%s" %s>%s</a>',
                                esc_attr('woocommerce-catalog-enquiry-custom-button'),
                                esc_url( $link ),
                                esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
                                esc_attr( $classes ),
                                isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
                                esc_html( $label )
                            );
                        }

                        break;
                    
                    case 3:
                        $product_link = get_post_meta($product->get_id(), 'woocommerce_catalog_enquiry_product_link', true);
                        $link = !empty($product_link) ? $product_link : '#';
                        $label = isset($this->settings_button['enquiry_button_text']) && !empty($this->settings_button['enquiry_button_text']) ? $this->settings_button['enquiry_button_text'] : $product->add_to_cart_text();
                        $classes = implode( ' ', array('button','product_type_' . $product->get_type()));
                       
                        if (isset($this->settings['is_enable_out_of_stock']) && mvx_catalog_get_settings_value($this->settings['is_enable_out_of_stock'], 'checkbox') == "Enable") {
                            if (!$product->managing_stock() && !$product->is_in_stock()) {
                                $pro_link = sprintf( '<a id="%s" href="%s" data-quantity="%s" class="%s" %s>%s</a>',
                                    esc_attr('woocommerce-catalog-enquiry-custom-button'),
                                    esc_url( $link ),
                                    esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
                                    esc_attr( $classes ),
                                    isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
                                    esc_html( $label )
                                );
                            } else {
                                $pro_link = sprintf( '<a id="%s" href="%s" data-quantity="%s" class="%s" %s>%s</a>',
                                    esc_attr('woocommerce-catalog-enquiry-custom-button'),
                                    esc_url( $link_add_to_cart ),
                                    esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
                                    esc_attr( $classes ),
                                    isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
                                    esc_html( $labels )
                                );
                            }
                        } else {
                            $pro_link = sprintf( '<a id="%s" href="%s" data-quantity="%s" class="%s" %s>%s</a>',
                                esc_attr('woocommerce-catalog-enquiry-custom-button'),
                                esc_url( $link ),
                                esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
                                esc_attr( $classes ),
                                isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
                                esc_html( $label )
                            );
                        }
                        break;
                    
                    case 4:
                        $link = '#';
                        $label = isset($this->settings_button['enquiry_button_text']) && !empty($this->settings_button['enquiry_button_text']) ? $this->settings_button['enquiry_button_text'] : $product->add_to_cart_text();
                        $classes = implode( ' ', array('button','product_type_' . $product->get_type()));
                        if (isset($this->settings['is_enable_out_of_stock']) && mvx_catalog_get_settings_value($this->settings['is_enable_out_of_stock'], 'checkbox') == "Enable") {
                            if (!$product->managing_stock() && !$product->is_in_stock()) {
                                $pro_link = sprintf( '<a id="%s" href="%s" data-quantity="%s" class="%s" %s>%s</a>',
                                    esc_attr('woocommerce-catalog-enquiry-custom-button'),
                                    $link,
                                    esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
                                    esc_attr( $classes ),
                                    isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
                                    esc_html( $label )
                                );
                            } else {
                                $pro_link = sprintf( '<a id="%s" href="%s" data-quantity="%s" class="%s" %s>%s</a>',
                                    esc_attr('woocommerce-catalog-enquiry-custom-button'),
                                    esc_url($link_add_to_cart),
                                    esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
                                    esc_attr( $classes ),
                                    isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
                                    esc_html( $labels )
                                );
                            }
                        } else {
                            $pro_link = sprintf( '<a id="%s" href="%s" data-quantity="%s" class="%s" %s>%s</a>',
                                esc_attr('woocommerce-catalog-enquiry-custom-button'),
                                $link,
                                esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
                                esc_attr( $classes ),
                                isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
                                esc_html( $label )
                            );
                        }
                        break;

                    default:
                        $link = get_permalink($product->get_id());
                        $label = isset($this->settings_button['enquiry_button_text']) && !empty($this->settings_button['enquiry_button_text']) ? $this->settings_button['enquiry_button_text'] : __('Read More', 'woocommerce-catalog-enquiry');
                        $classes = implode( ' ', array('button','product_type_' . $product->get_type()));
                        if (isset($this->settings['is_enable_out_of_stock']) && mvx_catalog_get_settings_value($this->settings['is_enable_out_of_stock'], 'checkbox') == "Enable") {
                            if (!$product->managing_stock() && !$product->is_in_stock()) {
                                $pro_link = sprintf( '<a id="%s" href="%s" data-quantity="%s" class="%s" %s>%s</a>',
                                    esc_attr('woocommerce-catalog-enquiry-custom-button'),
                                    $link,
                                    esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
                                    esc_attr( $classes ),
                                    isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
                                    esc_html( $label )
                                );
                            } else {
                                $pro_link = sprintf( '<a id="%s" href="%s" data-quantity="%s" class="%s" %s>%s</a>',
                                    esc_attr('woocommerce-catalog-enquiry-custom-button'),
                                    $link,
                                    esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
                                    esc_attr( $classes ),
                                    isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
                                    esc_html( $labels )
                                );
                            }
                        } else {
                            $pro_link = sprintf( '<a id="%s" href="%s" data-quantity="%s" class="%s" %s>%s</a>',
                                esc_attr('woocommerce-catalog-enquiry-custom-button'),
                                $link,
                                esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
                                esc_attr( $classes ),
                                isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
                                esc_html( $label )
                            );
                        }
                        break;
                }
            }
            return apply_filters('woocommerce_catalog_enquiry_custom_product_link', $pro_link, $product, $this->settings, $this->settings_button);
        } else {
            return $add_to_cart_button;
        }
        
    }

    public function init_catalog() {
        global $Woocommerce_Catalog_Enquiry;

        if (isset($this->settings['is_enable']) && mvx_catalog_get_settings_value($this->settings['is_enable'], 'checkbox') == "Enable" && ($this->available_for == '' || $this->available_for == 0) && apply_filters( 'woocommerce_catalog_enquiry_free_active', true )) {
            add_action('init', array($this, 'remove_add_to_cart_button'));
            if (isset($this->settings['is_enable_enquiry']) && mvx_catalog_get_settings_value($this->settings['is_enable_enquiry'], 'checkbox') == "Enable") {
                $piority = apply_filters('woocommerce_catalog_enquiry_button_possition_piority', 100);
                if (isset($this->settings['is_disable_popup']) && mvx_catalog_get_settings_value($this->settings['is_disable_popup'], 'checkbox') == "Enable") {
                    add_action('woocommerce_single_product_summary', array($this, 'add_form_for_enquiry_without_popup'), $piority);
                } else {
                    add_action('woocommerce_single_product_summary', array($this, 'add_form_for_enquiry'), $piority);
                }
            }
            if (isset($this->settings['is_enable_out_of_stock']) && mvx_catalog_get_settings_value($this->settings['is_enable_out_of_stock'], 'checkbox') == "Enable") {
                $piority = apply_filters('woocommerce_catalog_enquiry_button_possition_piority', 100);
                if (isset($this->settings['is_disable_popup']) && mvx_catalog_get_settings_value($this->settings['is_disable_popup'], 'checkbox') == "Enable") {
                    add_action('woocommerce_single_product_summary', array($this, 'add_form_for_enquiry_without_popup'), $piority);
                } else {
                    add_action('woocommerce_single_product_summary', array($this, 'add_form_for_enquiry'), $piority);
                }
            }
            if (isset($this->settings['is_remove_price_free']) && mvx_catalog_get_settings_value($this->settings['is_remove_price_free'], 'checkbox') == "Enable") {
                add_action('init', array($this, 'remove_price_from_product_list_loop'), 10);
                add_action('woocommerce_single_product_summary', array($this, 'remove_price_from_product_list_single'), 5);
                add_filter( 'woocommerce_catalog_orderby', array($this, 'remove_pricing_from_catalog_orderby'), 99 );
            }

            add_filter('woocommerce_loop_add_to_cart_link', array($this, 'woocommerce_loop_add_to_cart_link'), 99, 3);
            
            add_action('woocommerce_after_shop_loop_item_title', array($this, 'price_for_selected_product'), 5);
            add_action('woocommerce_after_shop_loop_item', array($this, 'add_to_cart_button_for_selected_product'), 5);
            add_action('woocommerce_before_shop_loop_item', array($this, 'change_permalink_url_for_selected_product'), 5);
            add_action('woocommerce_single_product_summary', array($this, 'catalog_woocommerce_template_single'), 5);
        }
    }

    public function change_permalink_url_for_selected_product() {
        global $Woocommerce_Catalog_Enquiry, $post, $product;
        $exclusion = $Woocommerce_Catalog_Enquiry->options_exclusion_settings;
        $product_for = '';

        if (isset($this->exclusion['woocommerce_product_list']) && mvx_catalog_get_settings_value($this->exclusion['woocommerce_product_list'], 'multiselect')) {
            if (is_array(mvx_catalog_get_settings_value($this->exclusion['woocommerce_product_list'], 'multiselect')) && isset($post->ID)) {
                if (in_array($post->ID, mvx_catalog_get_settings_value($this->exclusion['woocommerce_product_list'], 'multiselect'))) {
                    $product_for = $post->ID;
                } else {
                    $product_for = '';
                }
            }
        }
        
        $category_for = '';
        if (isset($this->exclusion['woocommerce_category_list']) && mvx_catalog_get_settings_value($this->exclusion['woocommerce_category_list'], 'multiselect')) {
            if (is_array(mvx_catalog_get_settings_value($this->exclusion['woocommerce_category_list'], 'multiselect'))) {
                if (isset($product)) {
                    $term_list = wp_get_post_terms($post->ID, 'product_cat', array('fields' => 'ids'));

                    if (count(array_intersect($term_list, mvx_catalog_get_settings_value($this->exclusion['woocommerce_category_list'], 'multiselect'))) > 0) {
                        $category_for = $post->ID;
                    } else {
                        $category_for = '';
                    }
                } else {
                    $category_for = '';
                }
            } else {
                $category_for = '';
            }
        } else {
            $category_for = '';
        }
        
        
        if ($product_for == $post->ID || $category_for == $post->ID) {
            add_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
            remove_filter('woocommerce_loop_add_to_cart_link', array($this, 'woocommerce_loop_add_to_cart_link'), 99, 3);
        } else {
            if (isset($this->settings_button['button_type'])) {
                add_filter('woocommerce_loop_add_to_cart_link', array($this, 'woocommerce_loop_add_to_cart_link'), 99, 3);
            }else{
                remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
            }
            
        }
    }

    public function catalog_woocommerce_template_single() {
        global $Woocommerce_Catalog_Enquiry, $post, $product;
        $settings = $Woocommerce_Catalog_Enquiry->options_general_settings;
        $exclusion = $Woocommerce_Catalog_Enquiry->options_exclusion_settings;
        $product_for = '';

        if (isset($this->exclusion['woocommerce_product_list']) && mvx_catalog_get_settings_value($this->exclusion['woocommerce_product_list'], 'multiselect')) {
            if (is_array(mvx_catalog_get_settings_value($this->exclusion['woocommerce_product_list'], 'multiselect')) && isset($post->ID)) {
                if (in_array($post->ID, mvx_catalog_get_settings_value($this->exclusion['woocommerce_product_list'], 'multiselect'))) {

                    $product_for = $post->ID;
                } else {
                    $product_for = '';
                }
            } else {
                $product_for = '';
            }
        } else {
            $product_for = '';
        }
        

        $category_for = '';
        if (isset($this->exclusion['woocommerce_category_list']) && mvx_catalog_get_settings_value($this->exclusion['woocommerce_category_list'], 'multiselect')) {
            if (is_array(mvx_catalog_get_settings_value($this->exclusion['woocommerce_category_list'], 'multiselect'))) {
                if (isset($product)) {
                    $term_list = wp_get_post_terms($post->ID, 'product_cat', array('fields' => 'ids'));

                    if (count(array_intersect($term_list, mvx_catalog_get_settings_value($this->exclusion['woocommerce_category_list'], 'multiselect'))) > 0) {
                        $category_for = $post->ID;
                    } else {
                        $category_for = '';
                    }
                } else {
                    $category_for = '';
                }
            } else {
                $category_for = '';
            }
        } else {
            $category_for = '';
        }
       

        if ($product_for == $post->ID || $category_for == $post->ID) {
            remove_action('woocommerce_single_product_summary', array($this, 'add_form_for_enquiry'), 100);
            remove_action('woocommerce_single_product_summary', array($this, 'add_form_for_enquiry_without_popup'), 100);
            add_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
            add_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
            add_action('woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20);
            remove_action('woocommerce_single_product_summary', array($this, 'add_variation_product'), 29);
        }else{
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
            remove_action('woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20);
        }
    }

    public function add_form_for_enquiry_without_popup() {
        global $Woocommerce_Catalog_Enquiry, $woocommerce, $post, $product;
        if (isset($this->settings_button)) {
            $enquiry_button_text = isset($this->settings_button['enquiry_button_text']) ? $this->settings_button['enquiry_button_text'] : __('Send an enquiry', 'woocommerce-catalog-enquiry');
            if ($enquiry_button_text == '') {
                $enquiry_button_text = __('Send an enquiry', 'woocommerce-catalog-enquiry');
            }
        }
        $productid = $post->ID;
        $current_user = wp_get_current_user();
        $product_name = get_post_field('post_title', $productid);
        $product_url = get_permalink($productid);

        $enquiry_form_fileds = [];
        if (isset($this->settings_form['enquiry_form_fileds']) && !empty($this->settings_form['enquiry_form_fileds'])) {
            foreach ($this->settings_form['enquiry_form_fileds'] as $key_e => $value_e) {
                $enquiry_form_fileds[$value_e[0]] = $value_e[1];
            }
        }
        //print_r($enquiry_form_fileds);die;
        ?>    
        <div id="woocommerce-catalog" name="woocommerce_catalog" >	
            <?php if (isset($this->settings['is_enable_out_of_stock']) && mvx_catalog_get_settings_value($this->settings['is_enable_out_of_stock'], 'checkbox') == "Enable") {
                if (!$product->managing_stock() && !$product->is_in_stock()) {
                    ?>
                    <br/>
                    <button class="woocommerce-catalog-enquiry-btn button woocommerce-catalog-enquiry-custom-button-enquiry <?php if ($this->settings_button && isset($this->settings_button['is_button']) && mvx_catalog_get_settings_value($this->settings_button['is_button'], 'checkbox') == 'Enable') echo 'custom_enquiry_buttons_css_new'; else echo ''; ?>" href="#responsive"><?php echo esc_html($enquiry_button_text); ?></button>
                    <?php
                    
                } else {
                    $product_object = wc_get_product( $productid );
                    echo '<a href="' . esc_url( $product_object->add_to_cart_url() ) . '" class="add-to-cart button">' . esc_html__( 'Add to Cart', 'woocommerce-catalog-enquiry' ) . '</a>';
                }
            } else {
                ?>
                <br/>
                <button class="woocommerce-catalog-enquiry-btn button demo btn btn-primary btn-large <?php if ($this->settings_button && isset($this->settings_button['is_button']) && mvx_catalog_get_settings_value($this->settings_button['is_button'], 'checkbox') == 'Enable') echo 'custom_enquiry_buttons_css_new'; else echo ''; ?>" style="margin-top:15px;" href="#responsive"><?php echo esc_html($enquiry_button_text); ?></button>
                <?php
            } ?>
            <input type="hidden" name="product_name_for_enquiry" id="product-name-for-enquiry" value="<?php echo get_post_field('post_title', $post->ID); ?>" />
            <input type="hidden" name="product_url_for_enquiry" id="product-url-for-enquiry" value="<?php echo get_permalink($post->ID); ?>" />
            <input type="hidden" name="product_id_for_enquiry" id="product-id-for-enquiry" value="<?php echo $post->ID; ?>" />
            <input type="hidden" name="enquiry_product_type" id="enquiry-product-type" value="<?php
            if ($product->is_type('variable')) {
                echo 'variable';
            }
            ?>" />
            <div id="responsive"  class="catalog_enquiry_form" tabindex="-1">
                <div class="modal-header">
                    <?php if (mvx_catalog_get_settings_value($this->settings_form['is_override_form_heading'], 'checkbox')) { ?>
                        <?php if (isset($this->settings_form['custom_static_heading'])) { ?>
                            <h2><?php echo str_replace( "PRODUCT_NAME",$product_name, $this->settings_form['custom_static_heading'] ); ?></h2>
                        <?php } ?>
                    <?php } else { ?>
                        <h2><?php echo __('Enquiry about ', 'woocommerce-catalog-enquiry') ?> <?php echo $product_name; ?></h2>
                    <?php } ?>
                </div>
                <div class="modal-body">  
                    <?php
                    if (isset($this->settings_form['top_content_form']) && !empty($this->settings_form['top_content_form'])) {
                        echo '<p class="catalog-enquiry-top-content">' . $this->settings_form['top_content_form'] . '</p>';
                    }
                    ?>
                    <p id="msg-for-enquiry-error" ></p>
                    <p id="msg-for-enquiry-sucesss" ></p>
                    <p id="loader-after-sumitting-the-form" ><img src="<?php echo $Woocommerce_Catalog_Enquiry->plugin_url; ?>assets/images/loader.gif" ></p>
                            <?php wp_nonce_field('wc_catalog_enquiry_mail_form', 'wc_catalog_enq'); ?>

                    <div class="cat-form-row">
                        <?php if (array_key_exists('name-label_checkbox', $enquiry_form_fileds)) { ?>
                        <label><?php
                            if (array_key_exists('name-label', $enquiry_form_fileds)) {
                                echo $enquiry_form_fileds['name-label'];
                            } else {
                                echo __('Enter your name : ', 'woocommerce-catalog-enquiry');
                            }
                        ?></label>	
                        <input name="woocommerce_user_name" id="woocommerce-user-name"  type="text" value="<?php echo $current_user->display_name; ?>" class="span12" />
                        <?php } ?>
                    </div>

                    <div class="cat-form-row">	
                        <?php if (array_key_exists('email-label_checkbox', $enquiry_form_fileds)) { ?>					
                        <label><?php
                        if (array_key_exists('email-label', $enquiry_form_fileds)) {
                            echo $enquiry_form_fileds['email-label'];
                        } else {
                            echo __('Enter your Email Id : ', 'woocommerce-catalog-enquiry');
                        }
                        ?></label>	
                        <input name="woocommerce_user_email" id="woocommerce-user-email"  type="email" value="<?php echo $current_user->user_email; ?>" class="span12" />
                        <?php } ?>
                    </div>

                    <div class="cat-form-row">	
                        <?php if (array_key_exists('is-subject_checkbox', $enquiry_form_fileds)) { ?>
                            <label><?php
                            if (array_key_exists('is-subject', $enquiry_form_fileds)) {
                                echo $enquiry_form_fileds['is-subject'];
                            } else {
                                echo __('Enter enquiry subject : ', 'woocommerce-catalog-enquiry');
                            }
                            ?></label>	
                            <input name="woocommerce_user_subject" id="woocommerce-user-subject"  type="text" value="<?php echo __('Enquiry about', 'woocommerce-catalog-enquiry'); ?> <?php echo $product_name; ?>" class="span12" />
                        <?php } ?>
                    </div>

                    <div class="cat-form-row">	
                        <?php if (array_key_exists('is-phone_checkbox', $enquiry_form_fileds)) { ?>
                            <label><?php
                                if (array_key_exists('is-phone', $enquiry_form_fileds)) {
                                    echo $enquiry_form_fileds['is-phone'];
                                } else {
                                    echo __('Enter your phone no : ', 'woocommerce-catalog-enquiry');
                                }
                                ?></label>	
                            <input name="woocommerce_user_phone" id="woocommerce-user-phone"  type="text" value="" class="span12" />
                        <?php } ?>
                    </div>

                    <div class="cat-form-row">	
                        <?php if (array_key_exists('is-address_checkbox', $enquiry_form_fileds)) { ?>
                        <label><?php
                            if (array_key_exists('is-address', $enquiry_form_fileds)) {
                                echo $enquiry_form_fileds['is-address'];
                            } else {
                                echo __('Enter your address : ', 'woocommerce-catalog-enquiry');
                            }
                            ?></label>	
                        <input name="woocommerce_user_address" id="woocommerce-user-address"  type="text" value="" class="span12" />
                        <?php } ?>
                    </div>

                    <div class="cat-form-row">	
                        <?php if (array_key_exists('is-comment_checkbox', $enquiry_form_fileds)) { ?>
                            <label><?php
                            if (array_key_exists('is-comment', $enquiry_form_fileds)) {
                                echo $enquiry_form_fileds['is-comment'];
                            } else {
                                echo __('Enter your Message : ', 'woocommerce-catalog-enquiry');
                            }
                            ?></label>	
                            <textarea name="woocommerce_user_comment" id="woocommerce-user-comment"  rows="5" class="span12"></textarea>
                        <?php } ?>
                    </div>


                    <div class="cat-form-row">	
                        <?php if (array_key_exists('is-fileupload_checkbox', $enquiry_form_fileds)) { ?>
                            <label><?php
                            if (array_key_exists('is-fileupload', $enquiry_form_fileds)) {
                                echo $enquiry_form_fileds['is-fileupload'];
                            } else {
                                echo __('Upload your File : ', 'woocommerce-catalog-enquiry');
                            }
                            ?></label>	
                            <input type="file" name="woocommerce_user_fileupload" id="woocommerce-user-fileupload" class="span12" />
                        <?php } ?>
                    </div>


                    <div class="cat-form-row">							
                    <?php do_action('woocommerce_catalog_enquiry_form_extra_fileds'); ?> 
                    <?php if (array_key_exists('is-captcha_checkbox', $enquiry_form_fileds)) { ?>
                        <label><?php
                        if (array_key_exists('is-captcha', $enquiry_form_fileds)) {
                            echo $enquiry_form_fileds['is-captcha'];
                        } else {
                            echo __('Security Code', 'woocommerce-catalog-enquiry');
                        }
                        ?> <span class="noselect captcha-wrap"><i><?php echo get_transient('woocaptcha'); ?></i></span></p>
                        <p><?php
                        echo __('Enter the security code shown above', 'woocommerce-catalog-enquiry');
                        ?> </p>
                        <input type="text" id="woocommerce-catalog-captcha" name="woocommerce_captcha" class="span12" />
                    <?php } ?>
                    </div>


                    <?php
                    if (isset($this->settings_form['bottom_content_form']) && !empty($this->settings_form['bottom_content_form'])) {
                        echo '<p class="catalog-enquiry-bottom-content">' . $this->settings_form['bottom_content_form'] . '</p>';
                    }
                    ?> 

                </div>
                <div class="modal-footer">		
                    <button type="button" id="woocommerce-submit-enquiry" class="btn btn-primary"><?php echo __('Send', 'woocommerce-catalog-enquiry'); ?></button>
                </div>
            </div>				
        </div>	
        <?php
    }

    public function add_form_for_enquiry() {
        global $Woocommerce_Catalog_Enquiry, $woocommerce, $post, $product, $wp_version;

        if (isset($this->settings_button)) {
            $custom_design_for_button = isset($this->settings_button['is_button']) ? mvx_catalog_get_settings_value($this->settings_button['is_button'], 'checkbox') : '';
            $enquiry_button_text = isset($this->settings_button['enquiry_button_text']) ? $this->settings_button['enquiry_button_text'] : __('Send an enquiry', 'woocommerce-catalog-enquiry');
            if ($enquiry_button_text == '') {
                $enquiry_button_text = __('Send an enquiry', 'woocommerce-catalog-enquiry');
            }
        }

        $productid = $post->ID;
        $current_user = wp_get_current_user();
        $product_name = get_post_field('post_title', $productid);
        $product_url = get_permalink($productid);

        $enquiry_form_fileds = [];
        if (isset($this->settings_form['enquiry_form_fileds']) && !empty($this->settings_form['enquiry_form_fileds'])) {
            foreach ($this->settings_form['enquiry_form_fileds'] as $key_e => $value_e) {
                $enquiry_form_fileds[$value_e[0]] = $value_e[1];
            }
        }
        ?>
        <div id="woocommerce-catalog" name="woocommerce_catalog" >
                        
            <?php if (isset($this->settings['is_enable_out_of_stock']) && mvx_catalog_get_settings_value($this->settings['is_enable_out_of_stock'], 'checkbox') == "Enable") {
                if (!$product->managing_stock() && !$product->is_in_stock()) {
                    if ($custom_design_for_button == "Enable") {
                        ?>
                        <br/>
                        <button class="woocommerce-catalog-enquiry-btn button woocommerce-catalog-enquiry-custom-button-enquiry <?php if ($this->settings_button && isset($this->settings_button['is_button']) && mvx_catalog_get_settings_value($this->settings_button['is_button'], 'checkbox') == 'Enable') echo 'custom_enquiry_buttons_css_new'; else echo ''; ?>" href="#responsive"><?php echo esc_html($enquiry_button_text); ?></button>
                        <?php
                    } else {
                        ?>
                        <button class="woocommerce-catalog-enquiry-btn button demo btn btn-primary btn-large <?php if ($this->settings_button && isset($this->settings_button['is_button']) && mvx_catalog_get_settings_value($this->settings_button['is_button'], 'checkbox') == 'Enable') echo 'custom_enquiry_buttons_css_new'; else echo ''; ?>" style="margin-top:15px;" href="#responsive"><?php esc_html_e('Send an enquiry', 'woocommerce-catalog-enquiry') ?></button>
                        <?php
                    }
                } else {
                    $product_object = wc_get_product( $productid );
                    echo '<a href="' . esc_url( $product_object->add_to_cart_url() ) . '" class="add-to-cart button">' . esc_html__( 'Add to Cart', 'woocommerce-catalog-enquiry' ) . '</a>';
                }
            } else {
                if ($custom_design_for_button == "Enable") { 
                    ?>
                    <br/>
                    <button class="woocommerce-catalog-enquiry-btn button woocommerce-catalog-enquiry-custom-button-enquiry <?php if ($this->settings_button && isset($this->settings_button['is_button']) && mvx_catalog_get_settings_value($this->settings_button['is_button'], 'checkbox') == 'Enable') echo 'custom_enquiry_buttons_css_new'; else echo ''; ?>" href="#responsive"><?php echo esc_html($enquiry_button_text); ?></button>
                    <?php
                } else {
                    ?>
                    <button class="woocommerce-catalog-enquiry-btn button demo btn btn-primary btn-large <?php if ($this->settings_button && isset($this->settings_button['is_button']) && mvx_catalog_get_settings_value($this->settings_button['is_button'], 'checkbox') == 'Enable') echo 'custom_enquiry_buttons_css_new'; else echo ''; ?>" style="margin-top:15px;" href="#responsive"><?php esc_html_e('Send an enquiry', 'woocommerce-catalog-enquiry') ?></button>
                    <?php
                }
            } ?>

            <input type="hidden" name="product_name_for_enquiry" id="product-name-for-enquiry" value="<?php echo get_post_field('post_title', $post->ID); ?>" />
            <input type="hidden" name="product_url_for_enquiry" id="product-url-for-enquiry" value="<?php echo get_permalink($post->ID); ?>" />
            <input type="hidden" name="product_id_for_enquiry" id="product-id-for-enquiry" value="<?php echo $post->ID; ?>" />
            <input type="hidden" name="enquiry_product_type" id="enquiry-product-type" value="<?php
                                if ($product->is_type('variable')) {
                                    echo 'variable';
                                }
                                ?>" />
            <div id="responsive"  class="catalog-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close">&times;</button>
                                <?php if (isset($this->settings_form['is_override_form_heading']) && mvx_catalog_get_settings_value($this->settings_form['is_override_form_heading'], 'checkbox')) { ?>
                                    <?php if (isset($this->settings_form['custom_static_heading'])) { ?>
                                <h2><?php echo str_replace( "PRODUCT_NAME", $product_name, $this->settings_form['custom_static_heading'] ); ?></h2>
                                    <?php } ?>
                                <?php } else { ?>
                            <h2><?php echo __('Enquiry about ', 'woocommerce-catalog-enquiry') ?> <?php echo $product_name; ?></h2>
                            <?php } ?>
                    </div>
                    <div class="modal-body">  
                        <?php
                        if (isset($this->settings_form['top_content_form'])) {
                            echo '<p class="catalog-enquiry-top-content">' . $this->settings_form['top_content_form'] . '</p>';
                        }
                        ?>
                        <p id="msg-for-enquiry-error"></p>
                        <p id="msg-for-enquiry-sucesss"></p>
                        <p id="loader-after-sumitting-the-form"><img src="<?php echo $Woocommerce_Catalog_Enquiry->plugin_url; ?>assets/images/loader.gif" ></p>
                        <?php wp_nonce_field('wc_catalog_enquiry_mail_form', 'wc_catalog_enq'); ?>



                    <div class="cat-form-row">
                        <?php if (array_key_exists('name-label_checkbox', $enquiry_form_fileds)) { ?>
                        <label><?php
                            if (array_key_exists('name-label', $enquiry_form_fileds)) {
                                echo $enquiry_form_fileds['name-label'];
                            } else {
                                echo __('Enter your name : ', 'woocommerce-catalog-enquiry');
                            }
                        ?></label>  
                        <input name="woocommerce_user_name" id="woocommerce-user-name"  type="text" value="<?php echo $current_user->display_name; ?>" class="span12" />
                        <?php } ?>
                    </div>

                    <div class="cat-form-row">  
                        <?php if (array_key_exists('email-label_checkbox', $enquiry_form_fileds)) { ?>                  
                        <label><?php
                        if (array_key_exists('email-label', $enquiry_form_fileds)) {
                            echo $enquiry_form_fileds['email-label'];
                        } else {
                            echo __('Enter your Email Id : ', 'woocommerce-catalog-enquiry');
                        }
                        ?></label>  
                        <input name="woocommerce_user_email" id="woocommerce-user-email"  type="email" value="<?php echo $current_user->user_email; ?>" class="span12" />
                        <?php } ?>
                    </div>


                    <div class="cat-form-row">  
                        <?php if (array_key_exists('is-subject_checkbox', $enquiry_form_fileds)) { ?>
                            <label><?php
                            if (array_key_exists('is-subject', $enquiry_form_fileds)) {
                                echo $enquiry_form_fileds['is-subject'];
                            } else {
                                echo __('Enter enquiry subject : ', 'woocommerce-catalog-enquiry');
                            }
                            ?></label>  
                            <input name="woocommerce_user_subject" id="woocommerce-user-subject"  type="text" value="<?php echo __('Enquiry about', 'woocommerce-catalog-enquiry'); ?> <?php echo $product_name; ?>" class="span12" />
                        <?php } ?>
                    </div>

                    <div class="cat-form-row">  
                        <?php if (array_key_exists('is-phone_checkbox', $enquiry_form_fileds)) { ?>
                            <label><?php
                                if (array_key_exists('is-phone', $enquiry_form_fileds)) {
                                    echo $enquiry_form_fileds['is-phone'];
                                } else {
                                    echo __('Enter your phone no : ', 'woocommerce-catalog-enquiry');
                                }
                                ?></label>  
                            <input name="woocommerce_user_phone" id="woocommerce-user-phone"  type="text" value="" class="span12" />
                        <?php } ?>
                    </div>

                    <div class="cat-form-row">  
                        <?php if (array_key_exists('is-address_checkbox', $enquiry_form_fileds)) { ?>
                        <label><?php
                            if (array_key_exists('is-address', $enquiry_form_fileds)) {
                                echo $enquiry_form_fileds['is-address'];
                            } else {
                                echo __('Enter your address : ', 'woocommerce-catalog-enquiry');
                            }
                            ?></label>  
                        <input name="woocommerce_user_address" id="woocommerce-user-address"  type="text" value="" class="span12" />
                        <?php } ?>
                    </div>

                    <div class="cat-form-row">  
                        <?php if (array_key_exists('is-comment_checkbox', $enquiry_form_fileds)) { ?>
                            <label><?php
                            if (array_key_exists('is-comment', $enquiry_form_fileds)) {
                                echo $enquiry_form_fileds['is-comment'];
                            } else {
                                echo __('Enter your Message : ', 'woocommerce-catalog-enquiry');
                            }
                            ?></label>  
                            <textarea name="woocommerce_user_comment" id="woocommerce-user-comment"  rows="5" class="span12"></textarea>
                        <?php } ?>
                    </div>


                    <div class="cat-form-row">  
                        <?php if (array_key_exists('is-fileupload_checkbox', $enquiry_form_fileds)) { ?>
                            <label><?php
                            if (array_key_exists('is-fileupload', $enquiry_form_fileds)) {
                                echo $enquiry_form_fileds['is-fileupload'];
                            } else {
                                echo __('Upload your File : ', 'woocommerce-catalog-enquiry');
                            }
                            ?></label>  
                            <input type="file" name="woocommerce_user_fileupload" id="woocommerce-user-fileupload" class="span12" />
                        <?php } ?>
                    </div>

                    <div class="cat-form-row">                          
                    <?php do_action('woocommerce_catalog_enquiry_form_extra_fileds'); ?> 
                    <?php if (array_key_exists('is-captcha_checkbox', $enquiry_form_fileds)) { ?>
                        <label><?php
                        if (array_key_exists('is-captcha', $enquiry_form_fileds)) {
                            echo $enquiry_form_fileds['is-captcha'];
                        } else {
                            echo __('Security Code', 'woocommerce-catalog-enquiry');
                        }
                        ?> <span class="noselect captcha-wrap"><i><?php echo get_transient('woocaptcha'); ?></i></span></p>
                        <p><?php
                        echo __('Enter the security code shown above', 'woocommerce-catalog-enquiry');
                        ?> </p>
                        <input type="text" id="woocommerce-catalog-captcha" name="woocommerce_captcha" class="span12" />
                    <?php } ?>
                    </div>




                    <?php
                    if (isset($this->settings_form['bottom_content_form']) && !empty($this->settings_form['bottom_content_form'])) {
                        echo '<p class="catalog-enquiry-bottom-content">' . $this->settings_form['bottom_content_form'] . '</p>';
                    }
                    ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default"><?php echo __('Close', 'woocommerce-catalog-enquiry'); ?></button>
                        <button type="button" id="woocommerce-submit-enquiry" class="btn btn-primary"><?php echo __('Send', 'woocommerce-catalog-enquiry'); ?></button>
                    </div>
                </div>
            </div>			
        </div>		
        <?php
    }

    public function price_for_selected_product() {
        global $post, $product;
        $product_for = '';

        if (isset($this->exclusion['woocommerce_product_list']) && mvx_catalog_get_settings_value($this->exclusion['woocommerce_product_list'], 'multiselect')) {
            if (is_array(mvx_catalog_get_settings_value($this->exclusion['woocommerce_product_list'], 'multiselect')) && isset($post->ID)) {
                if (in_array($post->ID, mvx_catalog_get_settings_value($this->exclusion['woocommerce_product_list'], 'multiselect'))) {
                    $product_for = $post->ID;
                } else {
                    $product_for = '';
                }
            }
        }


        $category_for = '';
        if (isset($this->exclusion['woocommerce_category_list']) && mvx_catalog_get_settings_value($this->exclusion['woocommerce_category_list'], 'multiselect')) {
            if (is_array(mvx_catalog_get_settings_value($this->exclusion['woocommerce_category_list'], 'multiselect'))) {
                if (isset($product)) {
                    $term_list = wp_get_post_terms($post->ID, 'product_cat', array('fields' => 'ids'));

                    if (count(array_intersect($term_list, mvx_catalog_get_settings_value($this->exclusion['woocommerce_category_list'], 'multiselect'))) > 0) {
                        $category_for = $post->ID;
                    } else {
                        $category_for = '';
                    }
                } else {
                    $category_for = '';
                }
            } else {
                $category_for = '';
            }
        } else {
            $category_for = '';
        }



        if ($product_for == $post->ID || $category_for == $post->ID) {
            add_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
        } else {
            if (isset($this->settings['is_remove_price_free']) && mvx_catalog_get_settings_value($this->settings['is_remove_price_free'], 'checkbox') == "Enable") {
                remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
            }
        }
    }

    public function add_to_cart_button_for_selected_product() {
        global $post, $product;
        $product_for = '';

        if (isset($this->exclusion['woocommerce_product_list']) && mvx_catalog_get_settings_value($this->exclusion['woocommerce_product_list'], 'multiselect')) {
            if (is_array(mvx_catalog_get_settings_value($this->exclusion['woocommerce_product_list'], 'multiselect')) && isset($post->ID)) {
                if (in_array($post->ID, mvx_catalog_get_settings_value($this->exclusion['woocommerce_product_list'], 'multiselect'))) {
                    $product_for = $post->ID;
                } else {
                    $product_for = '';
                }
            }
        }
        

        $category_for = '';
        if (isset($this->exclusion['woocommerce_category_list']) && mvx_catalog_get_settings_value($this->exclusion['woocommerce_category_list'], 'multiselect')) {
            if (is_array(mvx_catalog_get_settings_value($this->exclusion['woocommerce_category_list'], 'multiselect'))) {
                if (isset($product)) {
                    $term_list = wp_get_post_terms($post->ID, 'product_cat', array('fields' => 'ids'));

                    if (count(array_intersect($term_list, mvx_catalog_get_settings_value($this->exclusion['woocommerce_category_list'], 'multiselect'))) > 0) {
                        $category_for = $post->ID;
                    } else {
                        $category_for = '';
                    }
                } else {
                    $category_for = '';
                }
            } else {
                $category_for = '';
            }
        } else {
            $category_for = '';
        }
        

        if ($product_for == $post->ID || $category_for == $post->ID) {
            add_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
        } else {
            if (isset($this->settings_button['button_type']) && mvx_catalog_get_settings_value($this->settings_button['button_type'], 'select')) {
                add_filter('woocommerce_loop_add_to_cart_link', array($this, 'woocommerce_loop_add_to_cart_link'), 99, 3);
            } else {
                remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
            }
        }
    }

    public function add_read_more_button() {
        global $post;
        $enquiry_button_text = __("Read More", 'woocommerce-catalog-enquiry');
        if (!empty($this->settings['enquiry_button_text'])) {
            $enquiry_button_text = $this->settings['enquiry_button_text'];
        }
        $link = get_permalink($post->ID);
        echo ' <center><a  id="woocommerce-catalog-enquiry-custom-button" href="' . $link . '" class="single_add_to_cart_button button">' . $enquiry_button_text . '</a></center>';
    }

    public function add_external_link_button() {
        $enquiry_button_text = "Read More";
        if (!empty($this->settings_button['enquiry_button_text'])) {
            $enquiry_button_text = $this->settings_button['enquiry_button_text'];
        }
        $link = $this->settings_button['button_link'];
        echo ' <center><a  id="woocommerce-catalog-enquiry-custom-button" href="' . $link . '" class="single_add_to_cart_button button">' . $enquiry_button_text . '</a></center>';
    }

    public function add_external_link_button_independent() {
        global $post;
        $enquiry_button_text = "Read More";
        if (!empty($this->settings_button['enquiry_button_text'])) {
            $enquiry_button_text = $this->settings_button['enquiry_button_text'];
        }
        $link = get_post_field("woocommerce_catalog_enquiry_product_link", $post->ID);
        echo ' <center><a id="woocommerce-catalog-enquiry-custom-button" href="' . $link . '" class="single_add_to_cart_button button">' . $enquiry_button_text . '</a></center>';
    }

    public function add_custom_button_without_link() {
        $enquiry_button_text = "Read More";
        if (!empty($this->settings_button['enquiry_button_text'])) {
            $enquiry_button_text = $this->settings_button['enquiry_button_text'];
        }
        $link = "#";
        echo ' <center><a id="woocommerce-catalog-enquiry-custom-button" href="' . $link . '" class="single_add_to_cart_button button">' . $enquiry_button_text . '</a></center>';
    }

    public function remove_add_to_cart_button() {
        if ( isset( $this->settings_button['button_type'] ) ) {
            add_filter('woocommerce_loop_add_to_cart_link', array($this, 'woocommerce_loop_add_to_cart_link'), 99, 3);
        } else {
            remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
            // remove variation from product single
            remove_action('woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20);
        }
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
        add_action('woocommerce_single_product_summary', array($this, 'add_variation_product'), 29);
    }

    public function add_variation_product() {

        global $Woocommerce_Catalog_Enquiry, $product;
        if ($product->is_type('variable')) {
            $variable_product = new WC_Product_Variable($product);
            // Enqueue variation scripts
            wp_enqueue_script('wc-add-to-cart-variation');
            $available_variations = $variable_product->get_available_variations();
            //attributes
            $Woocommerce_Catalog_Enquiry->template->get_template('woocommerce-catalog-enquiry-variable-product.php', array('available_variations' => $available_variations));

        } elseif ($product->is_type('simple')) {
            echo wc_get_stock_html($product);
        }
    }

    public function remove_price_from_product_list_loop() {
        remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
    }

    public function remove_price_from_product_list_single() {
        remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation', 10 );
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
    }

    function frontend_scripts() {
        global $Woocommerce_Catalog_Enquiry;
        $frontend_script_path = $Woocommerce_Catalog_Enquiry->plugin_url . 'assets/frontend/js/';
        $frontend_script_path = str_replace(array('http:', 'https:'), '', $frontend_script_path);
        $pluginURL = str_replace(array('http:', 'https:'), '', $Woocommerce_Catalog_Enquiry->plugin_url);
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

        // Enqueue your frontend javascript from here

        if (isset($this->settings['is_enable']) && mvx_catalog_get_settings_value($this->settings['is_enable'], 'checkbox') == "Enable") {

            wp_enqueue_script('wce_frontend_js', $frontend_script_path . 'frontend.js', array( 'jquery', 'jquery-blockui' ), $Woocommerce_Catalog_Enquiry->version, true);

            // Variable declarations
            $arr_field = array();
            $arr_field[] = "name";
            $arr_field[] = "email";

            // error levels
            $error_levels = array();
            $error_levels['name_required'] = __('Name is required field', 'woocommerce-catalog-enquiry');
            $error_levels['email_required'] = __('Email is required field', 'woocommerce-catalog-enquiry');
            $error_levels['email_valid'] = __('Please Enter Valid Email Id', 'woocommerce-catalog-enquiry');
            $error_levels['captcha_required'] = __('Please enter the security code', 'woocommerce-catalog-enquiry');
            $error_levels['captcha_valid'] = __('Please enter the valid seurity code', 'woocommerce-catalog-enquiry');
            $error_levels['ajax_error'] = __('Error in system please try later', 'woocommerce-catalog-enquiry');
            $error_levels['filetype_error'] = __('Invalid file format.', 'woocommerce-catalog-enquiry');
            $error_levels['filesize_error'] = __('Exceeded filesize limit.', 'woocommerce-catalog-enquiry');

            // Captcha
            $arr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
            $i = 0;
            $captcha = '';
            while ($i < 8) {
                $v1 = rand(0, 35);
                $captcha .= $arr[$v1];
                $i++;
            }
            set_transient('woocaptcha', $captcha, 30 * MINUTE_IN_SECONDS);
            wp_localize_script(
                    'wce_frontend_js', 'catalog_enquiry_front', apply_filters('woocommerce_catalog_enquiry_localize_script_data', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'json_arr' => json_encode($arr_field),
                'settings' => $this->settings,
                'settings_gen' => $this->settings_form,
                'error_levels' => $error_levels,
                'ajax_success_msg' => __('Enquiry sent successfully', 'woocommerce-catalog-enquiry'),
                'redirect_link' => isset($this->settings['redirect_page_id']) ? get_permalink(mvx_catalog_get_settings_value($this->settings['redirect_page_id'], 'select')) : '',
                'captcha' => $captcha,
            )));
        }
    }

    function frontend_styles() {
        global $Woocommerce_Catalog_Enquiry;

        $frontend_style_path = $Woocommerce_Catalog_Enquiry->plugin_url . 'assets/frontend/css/';
        $frontend_style_path = str_replace(array('http:', 'https:'), '', $frontend_style_path);
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        $custom_button_css = $button_hover_css = $button_css = '';
        // Enqueue your frontend stylesheet from here
        if (isset($this->settings['is_enable']) && mvx_catalog_get_settings_value($this->settings['is_enable'], 'checkbox') == "Enable") {
            wp_enqueue_style('wce_frontend_css', $frontend_style_path . 'frontend.css', array(), $Woocommerce_Catalog_Enquiry->version);

            if (isset($this->settings_button) || isset($settings)) {
                if (isset($this->settings_button['custom_button_size']))
                $custom_button_css .= ' padding: ' . $this->settings_button['custom_button_size'] . 'px;' ;
                if (isset($this->settings_button['custom_font_size']))
                $custom_button_css .= ' font-size: ' . $this->settings_button['custom_font_size'] . 'px;';
                if (isset($this->settings_button['custom_border_radius']))
                $custom_button_css .= ' border-radius: ' . $this->settings_button['custom_border_radius'] . 'px;';
                if (isset($this->settings_button['custom_border_size']))
                    $custom_button_css .= ' border-style: solid; border-width: ' . $this->settings_button['custom_border_size'] . 'px;' ;
                if (isset($this->settings_button['custom_top_gradient_color']) && isset($this->settings_button['custom_bottom_gradient_color']))
                $custom_button_css .= ' background-image: linear-gradient('.$this->settings_button['custom_top_gradient_color'] . ',' . $this->settings_button['custom_bottom_gradient_color'] .'); ';
                if (isset($this->settings_button['custom_border_color']))
                    $custom_button_css .= ' border-color: ' . $this->settings_button['custom_border_color'] . ';' ;
                if (isset($this->settings_button['custom_text_color']))
                    $custom_button_css .= ' color: ' . $this->settings_button['custom_text_color'] . ' !important;' ;
                if (isset($this->settings_button['custom_button_font']) && !empty($this->settings_button['custom_button_font']))
                    $custom_button_css .= ' font-family: ' . $this->settings_button['custom_button_font']['value'] . ';' ;

                //hover changes
                if (isset($this->settings_button['custom_hover_background_color']))
                    $button_hover_css .= 'background-image: none; background-color: ' . $this->settings_button['custom_hover_background_color'] . ';' ;
                if (isset($this->settings_button['custom_hover_text_color']))
                    $button_hover_css .= ' color: ' . $this->settings_button['custom_hover_text_color'] . ';' ;

                if (!empty($custom_button_css)){
                    $button_css = 'button.custom_enquiry_buttons_css_new {'. $custom_button_css . '}';
                }
                if (!empty($button_hover_css)){
                    $button_css .= 'button.custom_enquiry_buttons_css_new:hover{'. $button_hover_css .'}';
                }
                
                $custom_button_css = '';
                $inline_css = "				
				/* The Modal (background) */
                " . wp_strip_all_tags($button_css) . "
				#woocommerce-catalog .catalog-modal {
				    display: none; /* Hidden by default */
				    position: fixed; /* Stay in place */
				    z-index: 100000; /* Sit on top */
				    /*padding-top: 100px;*/ /* Location of the box */
				    left: 0;
				    top: 0;
				    width: 100%; /* Full width */
				    height: 100%; /* Full height */
				    overflow: auto; /* Enable scroll if needed */
                    background-color: rgb(0,0,0,0.4); /* Fallback color */
				}";

                wp_add_inline_style('wce_frontend_css', $inline_css);
            }
            if (isset($this->settings['custom_css_product_page']) && $this->settings['custom_css_product_page'] != "") {
                wp_add_inline_style('wce_frontend_css', $this->settings['custom_css_product_page']);
            }
        }
    }
    
    public function wce_enquiry_button_shortcode() {
        if (isset($this->settings['is_enable']) && mvx_catalog_get_settings_value($this->settings['is_enable'], 'checkbox') == "Enable" && ($this->available_for == '' || $this->available_for == 0)) {
            if (isset($this->settings['is_enable_enquiry']) && mvx_catalog_get_settings_value($this->settings['is_enable_enquiry'], 'checkbox') == "Enable") {
                $piority = apply_filters('woocommerce_catalog_enquiry_button_possition_piority', 100);
                if (isset($this->settings['is_disable_popup']) && mvx_catalog_get_settings_value($this->settings['is_disable_popup'], 'checkbox') == "Enable") {
                    remove_action('woocommerce_single_product_summary', array($this, 'add_form_for_enquiry_without_popup'), $piority);
                    $this->add_form_for_enquiry_without_popup();
                } else {
                    remove_action('woocommerce_single_product_summary', array($this, 'add_form_for_enquiry'), $piority);
                    $this->add_form_for_enquiry();
                }
            }
        }
    }

    public function remove_pricing_from_catalog_orderby( $orderby ) {
        if ( isset( $orderby['price'] ) ) unset( $orderby['price'] );
        if ( isset( $orderby['price-desc'] ) ) unset( $orderby['price-desc'] );
        return $orderby;
    }

}
