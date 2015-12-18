<?php

get_header();
?>

<div id="main-content">
	<div class="container">
		<div id="content-area" class="clearfix">
			<div id="left-area">
			<?php while ( have_posts() ) : the_post(); ?>
					<div id="singlePropertyContent" class="entry-content">
            <h1><?= $post->propName ?></h1>
            <div class="property-details">
              <span id="propertyAddress"><b><?= $post->propAddress ?></b></span> |
              <span id="propertyCity"><?= $post->propCity ?></span>,
              <span id="propertyState"><?= $post->propState ?></span>
              <span id="propertyZip"><?= $post->propZip ?></span>
            </div>
            <div class="property-content">
              <?php the_content(); ?>
            </div>
            <div class="property-floorplans">
              <h4>Property Floorplans:</h4>
							<?php
								$propertyTaxonomy = strtolower($post->propName);
								$propertyTaxonomy = trim(str_replace(' ', '-', $propertyTaxonomy));
							 ?>
              <?= do_shortcode('[floorplanlist taxterms="'.$propertyTaxonomy.'" status="publish"]') ?>
            </div>
					</div> <!-- .entry-content -->
			<?php endwhile; ?>
			</div> <!-- #left-area -->

			<?php get_sidebar(); // standard wp sidebar ?>
		</div> <!-- #content-area -->
	</div> <!-- .container -->
</div> <!-- #main-content -->

<?php get_footer(); ?>
