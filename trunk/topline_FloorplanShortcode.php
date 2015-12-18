<?php
include_once('topline_ShortCodeScriptLoader.php');

class topline_FloorplanShortcode extends topline_ShortCodeScriptLoader {

    static $addedAlready = false;
    public function handleShortcode($atts) {
      $attributes = shortcode_atts( array(
          'type' => 'floorplans',
          'status' => 'publish',
          'taxterms' => null,
          'taxonomy' => 'property_relationship'
      ), $atts );
      $args = [
          'post_type' => $attributes['type'],
          'posts_per_page' => -1
      ];
      if(isset($attributes['taxterms'])) $args[$attributes['taxonomy']] = $attributes['taxterms'];
      $args['post_status'] = $attributes['status'];
      $floorplans = new WP_Query($args);
      $html = ''; $count = 0;
      if($floorplans->have_posts()) :
        $tmp = '';
        $output = '<div class="floorplanFilters">';
        while($floorplans->have_posts()) : $floorplans->the_post();
          $category_classes = '';
          $categories = get_the_terms($floorplans->post->ID, $attributes['taxonomy']);
          if($categories) :
            if($categories[0]->name !== $tmp || $tmp == '') :
              $filterLinkName = str_replace('-', ' ', str_replace('.', '', $categories[0]->name));
              $filter = str_replace('.', '', $categories[0]->name);
              $html .= "<a href='javascript:void()' class='filter button' data-filter=' ".$filter." '>".ucwords(htmlentities($filterLinkName))."</a>";
							$count++;
              ob_start();
              echo $html;
              $output .= ob_get_contents();
              ob_end_clean();
            endif;
						$tmp = $categories[0]->name;
          endif;
        endwhile;
        $output .= "</div>";
        if($count == 1) $html = "";
        wp_reset_postdata();
        $html .= "<div id='floorplanContainer'>";
        while($floorplans->have_posts()) : $floorplans->the_post();
           $taxTerms = get_the_terms($floorplans->post->ID , $attributes['taxonomy']);
           $floorplan = get_post_meta($floorplans->post->ID);
 					 $permalink = get_the_permalink();
           $thumb = wp_get_attachment_image_src( get_post_thumbnail_id($floorplans->post->ID), 'thumbnail' );
           $imgsrc = $thumb['0'];
 					 $imgsrc = isset($imgsrc) ? '<img src="'.$imgsrc.'" alt=""/>' : '<img src="http://placehold.it/150x150" alt=""/>';
           $termName = isset($taxTerms[0]) ? $taxTerms[0]->name : '';
           $html .= "<div class='mix '".str_replace('.', '', $termName) ."'>";
           $html .= "<div class='mix-img'>".$imgsrc."</div>";
           $html .= "<h4><a href='".$permalink."'> ".$floorplan['fpName'][0]."</a></h4>";
           $html .= "<p>Starting at $".$floorplan['fpMaxRent'][0]." </p></div>";
        endwhile;
        $html .= "</div>";
      endif;
      wp_reset_postdata();
      return $output.$html;
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
