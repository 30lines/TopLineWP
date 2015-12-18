<?php
/**
 * Topline API Manager
 * Author:  DJF
 * Company: 30Lines
 */
include_once('topline_InstallIndicator.php');
include_once('topline_OptionsManager.php');
include_once('topline_API.php');

class topline_ApiManager extends topline_OptionsManager {

  /**
   * The first method to be called when updating the feed
   * @param optional array $properties // list of properties already saved by user
   */
  public function updatePropertyFeeds($properties = null)
  {
    $apiKey = isset($properties) ? $properties['api_token'] : $this->getOption('api_token');
    if(!isset($apiKey)) return;
    $apiUsername = isset($properties) ? $properties['api_username'] : $this->getOption('api_username');
    $propertyList = isset($properties) ? $properties['properties'] : $this->getOption('properties');
    $topline = new topline_API($apiKey, $apiUsername);
    return $this->updateToplineFeed($topline, $propertyList);
  }

  /**
   * This method queries information from the TopLine service, then iterates through the response data
   * to save each property, floor plan, and unit
   * @return string // response telling user of success or failure
   */ 
  public function updateToplineFeed($topline, $propertyList)
  {
    $results = $topline->toplineMultiPropertyRefresh($propertyList);
		foreach ($results['codes'] as $key => $code) {
			$this->propertyUpdate($results[$code]);
			$this->floorplanUpdate($results[$code]);
			$this->unitUpdate($results[$code]);
		}
		if(count($results['codes']) == 0) {
			echo "<div style='background:#d32f2f;padding:20px;color:white;'>No properties available to request. Please configure your TopLine plugin settings.</div>";
			return;
		} else {
			echo "<div style='background:#0099cc;padding:20px;color:white;'>Successful Property Feed Refresh</div>";
			return;
		}
  }

  private function propertyUpdate($results) {
		/**
		 *  Property Post Type Creation/Updates
		 */
		 // WP_Query arguments
			 $args = array (
				'meta_key'       => 'propName',
				'meta_value'     => $results['propInfo']['propName'],
				'meta_compare'	 => '=',
				'post_type'      => 'properties',
				'post_status'    => 'publish'
			 );

			 // The Query
			 $propCheck = new WP_Query( $args );
			 // The Loop
			 if ( $propCheck->have_posts() ) {
				 while($propCheck->have_posts()) {
					 $propCheck->the_post();
					 // Update post meta of property post
					 // This will update every time so we know that the information is the most current
					 $this->updatePropertyMeta($propCheck->post->ID, $results['propInfo']);
				 }
			 } else {
				 // Variables used to create information for post
				$postVars = array(
					'post_title'      => $results['propInfo']['propName'],
					'post_type'       => 'properties',
					'post_status'     => 'publish',
				);
				 // Store post, store id
				 $updatePostID = wp_insert_post($postVars);

				 // Update post meta of most recent created property post
				 $this->updatePropertyMeta($updatePostID, $results['propInfo']);
			 }
			 // Restore original Post Data
			 wp_reset_postdata();
	}

  private function floorplanUpdate($results) {

		/* Floorplans post type creator/updator */
			if(count($results['floorplanInfo']) > 0) {
				foreach ($results['floorplanInfo'] as $floorplan) {
					// Floorplan query arguments
					$args = array (
					 'post_type'    => 'floorplans',
					 'tax_query' => array(
						 'taxonomy' => 'property_relationship',
						 'field'    => 'slug',
						 'terms'    => $results['taxonomy'],
					  ),
						'meta_key'     => 'fpID',
						'meta_value'   => is_null($floorplan['fpID']) ? 'property-tag' : $floorplan['fpID'],
						'meta_compare' => '=',
					  'post_status'  => 'publish'
					);

					// The Query -- if only I could be so grossly incandescent
					$fp_check = new WP_Query( $args );

					// The Loop
					if ( $fp_check->have_posts() ) {
						while ($fp_check->have_posts()) {
							$fp_check->the_post();

							// Set category dynamically to associated property
							wp_set_object_terms( $fp_check->post->ID, $results['taxonomy'], 'property_relationship', false );

							?>
							<script>console.log('<?= $results['taxonomy'] ?>')</script>
							<?php

							// Update post meta of property post
							// This will update every time so we know that the information is the most current
							$this->updateFloorPlanMeta($fp_check->post->ID, $floorplan);
						}
					} else {
						// Variables used to create information for post
					 $postVars = array(
						 'post_title'      => $floorplan['fpName'],
						 'post_type'       => 'floorplans',
						 'post_status'     => 'publish',
					 );
						// Store post, store id
						$updatePostID = wp_insert_post($postVars);

						// Set category dynamically to associated property
						wp_set_object_terms( $updatePostID, $results['taxonomy'], 'property_relationship', false );

						// Update post meta of most recent created property post
						$this->updateFloorPlanMeta($updatePostID, $floorplan);
					}

					//  // Restore original Post Data
					wp_reset_postdata();
				}
			}
	}

  private function unitUpdate($results)
  {
    // die(var_dump($results['unitInfo']));
    /* Unit post type creator/updator */
    if(count($results['unitInfo']) > 0) {
      foreach ($results['unitInfo'] as $unit) {
        // Unit query arguments
        $args = array (
         'post_type'    => 'units',
         'tax_query' => array(
           'taxonomy' => 'unit_relationship',
           'field'    => 'slug',
           'terms'    => $unit['taxonomy'],
          ),
          'meta_key'     => 'unit_number',
          'meta_value'   => is_null($unit['unit_number']) ? 'property-tag' : $unit['unit_number'],
          'meta_compare' => '=',
          'post_status'  => 'publish'
        );

        $unitCheck = new WP_Query($args);
        if ($unitCheck->have_posts()) {
          while ($unitCheck->have_posts()) {
            $unitCheck->the_post();

            // Set category dynamically to associated property, the unit taxonomy uses the parent floorplan id as a prefix
            wp_set_object_terms( $unitCheck->post->ID, $unit['fp_id'].'_'.$unit['taxonomy'], 'unit_relationship', false );

            // Update post meta of property post
            // This will update every time so we know that the information is the most current
            unset($unit['taxonomy']);
            $this->updateUnitMeta($unitCheck->post->ID, $unit);
          }
        } else {
          // Variables used to create information for post
         $postVars = array(
           'post_title'      => $unit['unit_number'],
           'post_type'       => 'units',
           'post_status'     => 'publish'
         );
          // Store post, store id
          $updatePostID = wp_insert_post($postVars);

          // Set category dynamically to associated property, , the unit taxonomy uses the parent floorplan id as a prefix
          wp_set_object_terms( $updatePostID, $unit['fp_id'].'_'.$unit['taxonomy'], 'unit_relationship', false );

          // Update post meta of most recent created property post
          unset($unit['taxonomy']);
          $this->updateUnitMeta($updatePostID, $unit);
        }
        wp_reset_postdata();
      }
    }
  }


  /**
	 *  Update function for updating property meta data
	 */
	private function updatePropertyMeta($updatePostID, $propInfo) {
		foreach ($propInfo as $key => $value) {
			if(isset($value) && $value !== '') update_post_meta($updatePostID, $key, $value);
		}
	}

  /**
	 *  Update function for updating floor plan meta data
	 */
	private function updateFloorPlanMeta($updatePostID, $floorPlan) {
		foreach ($floorPlan as $key => $value) {
			if(isset($value) && $value !== '') update_post_meta($updatePostID, $key, $value);
		}
	}

  /**
	 *  Update function for updating floor plan unit meta data
	 */
	private function updateUnitMeta($updatePostID, $units)
	{
		foreach ($units as $key => $value) {
			if(isset($value) && $value !== '') update_post_meta($updatePostID, $key, $value);
		}
	}
}
