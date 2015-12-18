<?php
include_once('topline_ShortCodeScriptLoader.php');

class topline_PropertiesShortcode extends topline_ShortCodeScriptLoader {

    static $addedAlready = false;
    public function handleShortcode($atts) {
      $attributes = shortcode_atts( array(
          'type' => 'properties',
          'status' => 'publish',
          'taxterms' => '',
          'taxonomy' => 'property_relationship'
      ), $atts );
      $args = [
          'post_type' => $attributes['type'],
          'post_status' => $attributes['status'],
          'posts_per_page' => -1
      ];
      $properties = new WP_Query($args);
      $html = '';
      if($properties->have_posts()) :
        $html .= '<div id="propertyContainer">';
        while($properties->have_posts()) : $properties->the_post();
          $property = get_post_meta($properties->post->ID);
          $filterName = str_replace('.', '', $property['propName'][0]);
          $filter = strtolower(str_replace(' ', '-', $filterName));
          $link = get_the_permalink();
          $html .= '<div class="mix  '.$filter.' ">';
          $html .= '<h4><a href=" '.$link.' "> '.$property['propName'][0].' </a></h4>';
          $html .= '<span> '.$property['propAddress'][0].' | Residents starting at $'.$property['propMinRent'][0].' </span></div>';
        endwhile;
        $html .= '</div>';
      else :
          $html .= '<div id="noPropertyContainer" style="background:darkred;padding:10px;color:white;"><h4>No properties have been added in your TopLine settings.</h4></div>';
      endif;
      wp_reset_postdata();
      return $html;
    }

    /**
     * Add any scripts that might be needed to format the shortcode
     */
    public function addScript() {
      if (!self::$addedAlready) {
        self::$addedAlready = true;
        // wp_register_script('my-script', plugins_url('js/my-script.js', __FILE__), array('jquery'), '1.0', true);
        // wp_print_scripts('my-script');
      }
    }
}
