<?php
/**
 * Topline Plugin Main class
 * Author:   DJF
 * Company:  30Lines
 * Purpose:  This class serves as the main 'action' center for our TopLine processes
 */
include_once('topline_LifeCycle.php');

class topline_Plugin extends topline_LifeCycle {

    /**
     * Get plugin display name
     * This name will be used for the admin menu name, settings page section title, etc.
     * @return string
     */
    public function getPluginDisplayName() {
        return 'TopLine';
    }

    /**
     * Get plugin main plugin file name
     * This allows us to be dynamic in what our root file can actually be
     * @return string
     */
    protected function getMainPluginFileName() {
        return 'toplineWP.php';
    }

    /**
     * Get TopLine option meta data
     * This creates the array for the first option meta data available to the user
     * @return array
     */
    public function getOptionMetaData() {
        return array(
            //'_version' => array('Installed Version'), // Leave this one commented-out. Uncomment to test upgrades.
            'api_token' => array(__('API Token', 'topline-wp')),
            'api_username' => array(__('API Username', 'topline-wp')),
            'template_override' => array(__('Single Template Override', 'topline-wp'), 'false', 'true'),
            'can_refresh' => array(__('Access level that can refresh the feed', 'topline-wp'), 'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber', 'Anyone'),
            'properties' => array(
              'property_name' => array(__('Property Name', 'topline-wp')),
              'property_code' => array(__('Property Code', 'topline-wp'))
            )
        );
    }

    /**
     * Initialize option meta
     */
    protected function initOptions() {
      $options = $this->getOptionMetaData();
      if (!empty($options)) {
          foreach ($options as $key => $arr) {
              if (is_array($arr) && $key === 'properties') {
                $this->addOption($key, $arr);
              } else {
                $this->addOption($key, $arr[1]);
              }
          }
      }
    }

    /**
     * Called by install() to create any database tables if needed.
     * Best Practice:
     * (1) Prefix all table names with $wpdb->prefix
     * (2) make table names lower case only
     * @return void
     */
    protected function installDatabaseTables() {
        // Here's an example on how we would create a new table if we needed to
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("CREATE TABLE IF NOT EXISTS `$tableName` (
        //            `id` INTEGER NOT NULL");
    }

    /**
     * Drop plugin-created tables and custom post types on uninstall.
     * @return void
     */
    protected function unInstallDatabaseTables() {
        // example of uninstalling all posts related to all custom post types available in the plugin
        global $wpdb;
        /* Remove Custom Post Type Related Posts */
        $tableName = $this->prefixTableName('wp_posts');
        foreach ($this->customPostTypes as $postType) {
            $wpdb->query("DELETE FROM ".$tableName." WHERE post_type = '".$postType."'");
        }
    }


    /**
     * Perform actions when upgrading from version X to version Y
     * @return void
     */
    public function upgrade() {
      // Just in case any database changes need to be made or post types updated
    }

    /**
     * Deactive TopLine plugin
     * Remove all topline settings, custom post types, scheduled actions, and trailing plugin data
     */
    public function deactivate() {
      /* Remove TopLine Settings from database */
      $options = $this->getOptionMetaData();
      foreach($options as $key => $val) {
        $this->deleteOption($key);
      }

      /* Remove all posts from Custom Post Types */
      $postTypes = $this->customPostTypes;
      foreach($postTypes as $postType) {
        $args = array(
        	'post_type' => $postType,
          'posts_per_page' => -1
        );
        $posts = new WP_Query( $args );
        if($posts->have_posts()) : while($posts->have_posts()) : $posts->the_post();
          wp_delete_post( $posts->post->ID, true);
        endwhile; endif;
        wp_reset_postdata();
      }

      /* Remove Custom Post Types */
      if ( ! function_exists( array($this, 'toplineUnregisterPostType') ) ) {
        foreach($this->customPostTypes as $postType) {
          $unregisterPropPostType = $this->toplineUnregisterPostType($postType);
        }
      }
    }

    /**
     * Unregister TopLine Custom Post Type
     *
     * @param $post_type string // Name of the custom post type to remove
     * @return BOOL
     */
    public function toplineUnregisterPostType( $post_type ) {
        global $wp_post_types;
        /* If the post type exists, remove it */
        if ( isset( $wp_post_types[ $post_type ] ) )
          unset( $wp_post_types[ $post_type ] );
        return isset( $wp_post_types[ $post_type ] ) ? true : false;
    }

    /**
     * Initialize Custom Post Types
     */
    public function addCustomPostTypes() {
      /* Custom Post Types */
      add_action( 'init', array( $this, 'addPropertyPosts' ), 0);
      add_action( 'init', array( $this, 'addFloorplanPostType' ), 0);
      add_action( 'init', array( $this, 'addUnitPosts' ), 0);
      /*  Initialize taxonomies */
      add_action( 'init', array($this, 'propAssociationTaxonomy'), 0 );
      add_action( 'init', array($this, 'unitAssociationTaxonomy'), 0 );
      /* Construct Post Meta Boxes */
      add_action( 'add_meta_boxes', array( $this, 'addCustomPostTypeMetaBoxes' ), 0);
    }

    /**
     * Initialize Taxonomies
     */
    public function propAssociationTaxonomy() {

     	$labels = array(
     		'name'                       => _x( 'Properties', 'Taxonomy General Name', 'text_domain' ),
     		'singular_name'              => _x( 'Property Association', 'Taxonomy Singular Name', 'text_domain' ),
     		'menu_name'                  => __( 'Property Categories', 'text_domain' ),
     		'all_items'                  => __( 'All Properties', 'text_domain' ),
     		'parent_item'                => __( 'Parent Properties', 'text_domain' ),
     		'parent_item_colon'          => __( 'Parent Properties:', 'text_domain' ),
     		'new_item_name'              => __( 'New Property Name', 'text_domain' ),
     		'add_new_item'               => __( 'Add New Property', 'text_domain' ),
     		'edit_item'                  => __( 'Edit Property', 'text_domain' ),
     		'update_item'                => __( 'Update Property', 'text_domain' ),
     		'view_item'                  => __( 'View Property', 'text_domain' ),
     		'separate_items_with_commas' => __( 'Separate properties with commas', 'text_domain' ),
     		'add_or_remove_items'        => __( 'Add or remove properties', 'text_domain' ),
     		'choose_from_most_used'      => __( 'Choose from the most used', 'text_domain' ),
     		'popular_items'              => __( 'Popular Properties', 'text_domain' ),
     		'search_items'               => __( 'Search Properties', 'text_domain' ),
     		'not_found'                  => __( 'Not Found', 'text_domain' ),
     	);
     	$args = array(
     		'labels'                     => $labels,
     		'hierarchical'               => true,
     		'public'                     => true,
     		'show_ui'                    => true,
     		'show_admin_column'          => true,
     		'show_in_nav_menus'          => true,
     		'show_tagcloud'              => true,
     	);
     	register_taxonomy( 'property_relationship', array( 'floorplans' ), $args );

     }

     public function unitAssociationTaxonomy() {

     	$labels = array(
     		'name'                       => _x( 'Units', 'Taxonomy General Name', 'text_domain' ),
     		'singular_name'              => _x( 'Unit Association', 'Taxonomy Singular Name', 'text_domain' ),
     		'menu_name'                  => __( 'Unit Categories', 'text_domain' ),
     		'all_items'                  => __( 'All Units', 'text_domain' ),
     		'parent_item'                => __( 'Parent Units', 'text_domain' ),
     		'parent_item_colon'          => __( 'Parent Units:', 'text_domain' ),
     		'new_item_name'              => __( 'New Unit Name', 'text_domain' ),
     		'add_new_item'               => __( 'Add New Unit', 'text_domain' ),
     		'edit_item'                  => __( 'Edit Unit', 'text_domain' ),
     		'update_item'                => __( 'Update Unit', 'text_domain' ),
     		'view_item'                  => __( 'View Unit', 'text_domain' ),
     		'separate_items_with_commas' => __( 'Separate units with commas', 'text_domain' ),
     		'add_or_remove_items'        => __( 'Add or remove units', 'text_domain' ),
     		'choose_from_most_used'      => __( 'Choose from the most used', 'text_domain' ),
     		'popular_items'              => __( 'Popular Units', 'text_domain' ),
     		'search_items'               => __( 'Search Units', 'text_domain' ),
     		'not_found'                  => __( 'Not Found', 'text_domain' ),
     	);
     	$args = array(
     		'labels'                     => $labels,
     		'hierarchical'               => true,
     		'public'                     => true,
     		'show_ui'                    => true,
     		'show_admin_column'          => true,
     		'show_in_nav_menus'          => true,
     		'show_tagcloud'              => true,
     	);
     	register_taxonomy( 'unit_relationship', array( 'units' ), $args );

     }

    /**
     * Initialize Custom Post Type Meta Boxes
     */
    public function addCustomPostTypeMetaBoxes() {
      /*-->property meta box */
  		add_meta_box(
  			'prop_info',
  			'Property Information',
  			[$this, 'propMetaFields'],
  			'properties'
  		);
      /*-->floor plan meta box */
  		add_meta_box(
  			'fp_info',
  			'Floor Plan Information',
  			[$this, 'fpMetaFields'],
  			'floorplans'
  		);
      /*-->unit meta box */
  		add_meta_box(
  			'unit_info',
  			'Unit Information',
  			[$this, 'fpUnitMetaFields'],
  			'units'
  		);
    }

    /**
    * Creates meta fields for properties post type
    */
    public function propMetaFields( $post ) {

      $propInfo = array(
        'propName'        => 'Property Name',
        'propAddress'     => 'Address',
        'propCity'        => 'City',
        'propState'       => 'State',
        'propZip'         => 'Zip',
        'propURL'         => 'Property URL',
        'propDescription' => 'Property Description',
        'propEmail'       => 'Property Email',
        'propLatitude'    => 'Latitude',
        'propLongitude'   => 'Longitude',
        'prop_code'       => 'Property Code',
        'propMinRent'     => 'Minimum Rent',
        'propMaxRent'     => 'Maximum Rent',
        'propMinBeds'     => 'Minimum Bedrooms',
        'propMaxBeds'     => 'Maximum Bedrooms',
        'propMinBaths'    => 'Minimum Bathrooms',
        'propMaxBaths'    => 'Maximum Bathrooms',
        'propMinSQFT'     => 'Minimum Square Feet',
        'propMaxSQFT'     => 'Maximum Square Feet'
      );
      /* Display input and nonce field */
      foreach ($propInfo as $key => $propMeta) {
        $propMeta = get_post_meta( $post->ID, $key, true ); ?>
        <label for="<?= $key ?>"><?= $propInfo[$key] ?></label>
        </br>
        <?php wp_nonce_field( 'topline_save_meta_box_data', $key.'_nonce' ); ?>
        <input type="text" id="<?= $key ?>" name="<?= $key ?>" value="<?php echo esc_attr( $propMeta ); ?>" style="width:100%;" size="25" /> <?php
      }
    }

    /**
     * Creates meta fields for floorplans post type
     */
     public function fpMetaFields( $post ) {

    	 $fpInfo = array(
    	 	'fpID' 						=> 'Floor Plan ID',
    	 	'fpName'          => 'Floor Plan Name',
    	 	'fpBeds'          => 'Bedroom Count',
    	 	'fpBaths'         => 'Bathroom Count',
    	 	'fpMinSQFT'       => 'Minimum Square Feet',
    	 	'fpMaxSQFT'       => 'Maximum Square Feet',
    	 	'fpMinRent'       => 'Minimum Rent',
    	 	'fpMaxRent'       => 'Maximum Rent',
    	 	'fpMinDeposit'    => 'Minimum Deposit',
    	 	'fpMaxDeposit'    => 'Maximum Deposit',
    	 	'fpAvailUnitCount'=> 'Number of units available with this floor plan',
    	 	'fpAvailURL'      => 'Availability URL',
    	 	'fpImg'           => 'Floor plan image URL',
        'fpPhone'         => 'Floor plan contact number'
    	 );
      /* Display input and nonce field */
      foreach ($fpInfo as $key => $fpMeta) {
        $fpMeta = get_post_meta( $post->ID, $key, true ); ?>
        <label for="<?= $key ?>"><?= $fpInfo[$key] ?></label>
        </br>
        <?php wp_nonce_field( 'topline_save_meta_box_data', $key.'_nonce' ); ?>
        <input type="text" id="<?= $key ?>" name="<?= $key ?>" value="<?php echo esc_attr( $fpMeta ); ?>" style="width:100%;" size="25" /> <?php
      }
    }

    /**
     * Unit Meta Fields
     */
    public function fpUnitMetaFields( $post ) {
    	$unitInfo = array(
    		'unit_number' 				=> 'Unit Number',
    		'status' 							=> 'Unit Status',
    		'rent_minimum' 				=> 'Rent Minimum',
    		'rent_maximum' 				=> 'Rent Maximum',
    		'square_foot_minimum' => 'Square Foot Minimum',
    		'square_foot_maximum' => 'Square Foot Maximum'
      );
      /* Display input and nonce field */
    	foreach ($unitInfo as $key => $unitMeta) {
        $unitMeta = get_post_meta( $post->ID, $key, true ); ?>
        <label for="<?= $key ?>"><?= $unitInfo[$key] ?></label>
        </br>
        <?php wp_nonce_field( 'topline_save_meta_box_data', $key.'_nonce' ); ?>
        <input type="text" id="<?= $key ?>" name="<?= $key ?>" value="<?php echo esc_attr( $unitMeta ); ?>" style="width:100%;" size="25" />
    		<?php
      }
    }

    public function addPropertyPosts() {
      $labels = array(
        'name'                => _x( 'Properties', 'Post Type General Name', 'text_domain' ),
        'singular_name'       => _x( 'Property', 'Post Type Singular Name', 'text_domain' ),
        'menu_name'           => __( 'Properties', 'text_domain' ),
        'parent_item_colon'   => __( 'Parent Property:', 'text_domain' ),
        'all_items'           => __( 'All Properties', 'text_domain' ),
        'view_item'           => __( 'View Property', 'text_domain' ),
        'add_new_item'        => __( 'Add New Property', 'text_domain' ),
        'add_new'             => __( 'Add New Property', 'text_domain' ),
        'edit_item'           => __( 'Edit Property', 'text_domain' ),
        'update_item'         => __( 'Update Property', 'text_domain' ),
        'search_items'        => __( 'Search Properties', 'text_domain' ),
        'not_found'           => __( 'No Property found', 'text_domain' ),
        'not_found_in_trash'  => __( 'Not found in Trash', 'text_domain' ),
      );

      $args = array(
        'label'               => __( 'properties', 'text_domain' ),
        'description'         => __( 'properties post type', 'text_domain' ),
        'labels'              => $labels,
        'supports'            => array( 'title', 'editor', 'thumbnail', ),
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-location-alt',
        'can_export'          => true,
        'has_archive'         => false,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'capability_type'     => 'page',
      );
      register_post_type( 'properties', $args );
    }

    /**
     *  Floor Plan Post Type Registration
     */
    public function addFloorplanPostType() {

    	$labels = array(
    		'name'                => _x( 'Floor Plans', 'Post Type General Name', 'text_domain' ),
    		'singular_name'       => _x( 'Floor Plan', 'Post Type Singular Name', 'text_domain' ),
    		'menu_name'           => __( 'Floor Plans', 'text_domain' ),
    		'parent_item_colon'   => __( 'Parent Plan:', 'text_domain' ),
    		'all_items'           => __( 'All Floor Plans', 'text_domain' ),
    		'view_item'           => __( 'View Floor Plan', 'text_domain' ),
    		'add_new_item'        => __( 'Add New Floor Plan', 'text_domain' ),
    		'add_new'             => __( 'Add New Floor Plan', 'text_domain' ),
    		'edit_item'           => __( 'Edit Floor Plan', 'text_domain' ),
    		'update_item'         => __( 'Update Floor Plan', 'text_domain' ),
    		'search_items'        => __( 'Search Floor Plans', 'text_domain' ),
    		'not_found'           => __( 'No Floor Plan found', 'text_domain' ),
    		'not_found_in_trash'  => __( 'Not found in Trash', 'text_domain' ),
    	);

    	$args = array(
    		'label'               => __( 'floorplans', 'text_domain' ),
    		'description'         => __( 'Floor plans post type', 'text_domain' ),
    		'labels'              => $labels,
    		'supports'            => array( 'title', 'editor', 'thumbnail', ),
    		'hierarchical'        => false,
    		'public'              => true,
    		'show_ui'             => true,
    		'show_in_menu'        => true,
    		'show_in_nav_menus'   => true,
    		'show_in_admin_bar'   => true,
    		'menu_position'       => 5,
    		'menu_icon'           => 'dashicons-schedule',
    		'can_export'          => true,
    		'has_archive'         => false,
    		'exclude_from_search' => false,
    		'publicly_queryable'  => true,
    		'capability_type'     => 'page',
    	);
    	register_post_type( 'floorplans', $args );
    }

    /**
     *  Unit Post Type Registration
     */
    public function addUnitPosts() {

    	$labels = array(
    		'name'                => _x( 'Units', 'Post Type General Name', 'text_domain' ),
    		'singular_name'       => _x( 'Unit', 'Post Type Singular Name', 'text_domain' ),
    		'menu_name'           => __( 'Units', 'text_domain' ),
    		'parent_item_colon'   => __( 'Parent Floorplan:', 'text_domain' ),
    		'all_items'           => __( 'All Units', 'text_domain' ),
    		'view_item'           => __( 'View Unit', 'text_domain' ),
    		'add_new_item'        => __( 'Add New Unit', 'text_domain' ),
    		'add_new'             => __( 'Add New Unit', 'text_domain' ),
    		'edit_item'           => __( 'Edit Unit', 'text_domain' ),
    		'update_item'         => __( 'Update Unit', 'text_domain' ),
    		'search_items'        => __( 'Search Units', 'text_domain' ),
    		'not_found'           => __( 'No Units found', 'text_domain' ),
    		'not_found_in_trash'  => __( 'No Units found in Trash', 'text_domain' ),
    	);

    	$args = array(
    		'label'               => __( 'units', 'text_domain' ),
    		'description'         => __( 'units post type', 'text_domain' ),
    		'labels'              => $labels,
    		'supports'            => array( 'title', 'editor', 'thumbnail', ),
    		'hierarchical'        => false,
    		'public'              => true,
    		'show_ui'             => true,
    		'show_in_menu'        => true,
    		'show_in_nav_menus'   => true,
    		'show_in_admin_bar'   => true,
    		'menu_position'       => 45,
    		'menu_icon'           => 'dashicons-location-alt',
    		'can_export'          => true,
    		'has_archive'         => false,
    		'exclude_from_search' => false,
    		'publicly_queryable'  => true,
    		'capability_type'     => 'page',
    	);
    	register_post_type( 'units', $args );
    }

    /**
     * Add any plugin applicaiton actions or filters
     */
    public function addActionsAndFilters() {
        include_once('topline_PropertiesShortcode.php');
        include_once('topline_FloorplanShortcode.php');

        // Add options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        add_action('admin_menu', array(&$this, 'createSettingsMenu'));

        // Example adding a script & style just for the options administration page
        if (strpos($_SERVER['REQUEST_URI'], $this->getSettingsSlug()) !== false) {
          wp_enqueue_script('topline-option-scripts', plugins_url('/js/topline-options.js', __FILE__), array('jquery'));
          wp_enqueue_style('topline-option-styles', plugins_url('/css/topline.css', __FILE__));
        }

        // Add Actions & Filters
        add_action('refreshToplineToken', 'refreshFeed');
        add_filter( 'single_template', [$this, 'useCustomSingleTemplate'] );

        /* Action that overrides saving custom post types */
        add_action('save_post', [$this, 'save_custom_post_meta']);

        // Adding scripts & styles for single property and floorplan views
        wp_enqueue_style('topline-global', plugins_url('/css/topline-shortcodes.css', __FILE__));

        // Register short codes
      	$propertyShortcode = new topline_PropertiesShortcode();
      	$propertyShortcode->register('propertieslist');

        $floorplanShortcode = new topline_FloorplanShortcode();
        $floorplanShortcode->register('floorplanlist');

        // Register AJAX hooks
        // e.g. ajaxACTION would be the name of your ajax method to accept the request
        // The ACTION portion of your method is your unique identifier
        // Let's say it's now 'MyAjaxActionName'
        // In ajaxMyAjaxActionName method:
        // You get the plain url like so: $plainUrl = $this->getAjaxUrl('MyAjaxActionName');
        // Send query variables: $urlWithId = $this->getAjaxUrl('MyAjaxActionName&id=MyId');
        // cont. but more sophisticated:
        // $parametrizedUrl = $this->getAjaxUrl('MyAjaxActionName&id=%s&lat=%s&lng=%s');
        // $urlWithParamsSet = sprintf($parametrizedUrl, urlencode($myId), urlencode($myLat), urlencode($myLng));
        // add_action('wp_ajax_ACTION', array(&$this, 'ajaxACTION'));
        // add_action('wp_ajax_nopriv_ACTION', array(&$this, 'ajaxACTION')); // optional for non-signed in users

    }

    /**
    * Save post metadata when a post is saved.
    *
    * @param int $post_id The post ID.
    * @param post $post The post object.
    * @param bool $update Whether this is an existing post being updated or not.
    */
    public function save_custom_post_meta( $post_id, $post, $update ) {

      /*
       * In production code, $slug should be set only once in the plugin,
       * preferably as a class property, rather than in each function that needs it.
       */
      $slugs = ['properties', 'floorplans', 'units'];
      // If this isn't a 'book' post, don't update it.
      if ( !in_array($_REQUEST['post_type'], $slugs) ) {
          return;
      }

      switch ($_REQUEST['post_type']) {
        case 'properties':
          $property = get_post($_REQUEST['post_ID']);
          $property = get_post_meta($property->ID);
          if(isset($_REQUEST)) {
            foreach ($property as $metaKey => $metaValue) {
              if(preg_match('/^prop*/', $metaKey)){
                update_post_meta($_REQUEST['post_ID'], $metaKey, sanitize_text_field($_REQUEST[$metaKey]));
              }
            }
          }
          break;
        case 'floorplans':
          $floorplan = get_post($_REQUEST['post_ID']);
          $floorplan = get_post_meta($floorplan->ID);
          if(isset($_REQUEST)) {
            foreach ($floorplan as $metaKey => $metaValue) {
              if(preg_match('/^fp*/', $metaKey)){
                update_post_meta($_REQUEST['post_ID'], $metaKey, sanitize_text_field($_REQUEST[$metaKey]));
              }
            }
          }
          break;
        case 'units':
          $units = get_post($_REQUEST['post_ID']);
          $units = get_post_meta($units->ID);
          if(isset($_REQUEST)) {
            foreach ($units as $metaKey => $metaValue) {
              if($metaKey == 'fp_id') continue;
              update_post_meta($_REQUEST['post_ID'], $metaKey, sanitize_text_field($_REQUEST[$metaKey]));
            }
          }
          break;
        default:
          # code...
          break;
      }
    }

    /**
     * Checks if single template override is set to 'true' in the TopLine options meta
     *
     * @param FILELOCATION $single_template
     * @return FILELOCATION
     */
    public function useCustomSingleTemplate($single_template) {
       global $post;
    	 $override = $this->getOption('template_override');
    	 if($override == 'false') return $single_template;
       if ($post->post_type == 'properties') $single_template = dirname( __FILE__ ) . '/templates/single-property.php';
    	 if ($post->post_type == 'floorplans') $single_template = dirname( __FILE__ ) . '/templates/single-floorplan.php';
       return $single_template;
    }

    /**
     * Refresh the listings that were brought in from the TopLine Service
     */
    public function refreshFeed() {
    	if(function_exists('updateFeed')) {
        updateFeed();
      }
    }

}
