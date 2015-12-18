<?php

get_header();
?>

<div id="main-content">
	<div class="container">
		<div id="content-area" class="clearfix">
			<div id="left-area">
			<?php while ( have_posts() ) : the_post();
				$fpImg = isset($post->fpImg) ? '<img src="'.$post->fpImg.'" alt="" />' : '<img src="http://placehold.it/800x800?text=Floor+Plan+Image+Unavailable" alt="" />';
				$fpTitle = get_the_title();
				$terms = wp_get_post_terms( $post->ID, 'property_relationship');
				$flPlnPropTaxonomy = explode('-', $terms[0]->name);
				$flPlnProp = '';
				foreach ($flPlnPropTaxonomy as $key) {
					$flPlnProp .= ucfirst($key).' ';
				}
			?>
					<div id="singleFloorplanContent" class="entry-content">
            <h1><?= $flPlnProp.' Floor Plan: '.$fpTitle; ?></h1>
            <div class="floorplan-details">
              <span id="fpBeds"><?= $post->fpBeds ?> Bed</span>,
              <span id="fpBaths"> <?= $post->fpBaths ?> Bath</span>
							<?php
								if ($post->fpAvailUnitCount === 0) {
									echo ' | <span id="fpAvailUnitCount">No Units Available</span>';
								}
							?>
            </div>
            <div class="floorplan-content">
              <?php the_content(); ?>
            </div>
            <div class="topline-floorplan-info">
              <div class="left half">
              	<?= has_post_thumbnail() ? the_post_thumbnail() : $fpImg ?>
              </div>
							<div class="right half">
								<div class="starting-at">
									<p>Starting at</p>
									<h2>$<?= $post->fpMinRent ?> per Month</h2>
								</div>
								<div class="schedule-tour">
									<a href="javascript:void()" class="button et_pb_button">Schedule a Tour</a>
									</br>
									<p class="leasing-cta">Speak With a Leasing Consultant</p>
									<a href="tel:<?= $post->fpPhone ?>" class="leasing-number"><?= $post->fpPhone ?></a>
								</div>
								<hr>
								<div class="need-to-know">
									<h2>What You Need to Know</h2>
									<ul>
										<li>
											<span class="double-right">&raquo;</span>
											<?= $post->fpAvailUnitCount == "0" ? 'No Units Available' : $post->fpAvailUnitCount." Units Available" ?>
										</li>
										<li>
											<span class="double-right">&raquo;</span>
											Rent Range:
											<?= $post->fpMinRent.'-'.$post->fpMaxRent ?>
										</li>
										<li>
											<span class="double-right">&raquo;</span>
											<?= $post->fpBeds.' Beds and '.$post->fpBaths.' Baths' ?>
										</li>
										<li>
											<span class="double-right">&raquo;</span>
											<?= 'Square Feet Range: '.$post->fpMinSQFT.'-'.$post->fpMaxSQFT ?>
										</li>
									</ul>
								</div>
							</div>
            </div>
						<div class="topline-floorplan-units">
							<?php
								$fpTitleTaxonomy = strtolower($fpTitle);
								$fpTitleTaxonomy = trim(str_replace(' ', '-', $fpTitleTaxonomy));
								$args = array(
										'post_type' => 'units',
										'meta_key' => 'fp_id',
										'meta_value' => $post->fpID,
										'meta_compare' => '=',
										'post_status' => 'publish'
								);
								$units = new WP_Query($args);
								if ($units->have_posts()) {
									$html = '';
									$html .= '<table id="unitTable">';
									$html .= '<thead><tr>';
									$html .= '<td>Unit Number</td>';
									$html .= '<td>Rent Min/Max</td>';
									$html .= '<td>Square Foot Min/Max</td>';
									$html .= '<td>Unit Availability Status</td>';
									$html .= '</tr></thead>';
									$html .= '<tbody>';
									$availCount = 0;
									while ($units->have_posts()) {
										$html .= '<tr valign="top" class="unit-item">';
										$units->the_post();
										$unitMeta = get_post_meta($units->post->ID);
										if(strpos($unitMeta['status'][0], 'Unavailable') !== false) continue;
										$html .= '<td>'.$unitMeta['unit_number'][0].'</td>';
										$html .= '<td>'.$unitMeta['rent_minimum'][0].'-'.$unitMeta['rent_maximum'][0].'</td>';
										$html .= '<td>'.$unitMeta['square_foot_minimum'][0].'-'.$unitMeta['square_foot_maximum'][0].'</td>';
										$html .= '<td>'.$unitMeta['status'][0].'</td>';
										$html .= '<td><a class="button" href="/contact">Apply Now</a></td>';
										$html .= '</tr>';
										$availCount++;
									}
									if($availCount == 0) {
										$html .= '<tr valign="top" class="unit-item">';
										$html .= '<td colspan="4"><b><i>No Units Available</i></b></td>';
										$html .= '</tr>';
									}
									$html .= '</tbody></table>';
									echo $html;
								}
								wp_reset_postdata(); ?>
						</div>
					</div> <!-- .entry-content -->
			<?php endwhile; ?>
			</div> <!-- #left-area -->

			<?php // get_sidebar(); ?>
		</div> <!-- #content-area -->
	</div> <!-- .container -->
</div> <!-- #main-content -->

<?php get_footer(); ?>
