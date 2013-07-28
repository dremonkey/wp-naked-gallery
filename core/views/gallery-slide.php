<?php
/**
 * Slide Content Template File
 *
 * This should replace the main content of the page... 
 * so in the twentyeleven theme this would replace the content-single.php file
 *
 * This file can be replaced by a theme template file. To replace this template file, create a 
 * new file in your theme directory with exactly the same name as this file OR you can use the 
 * filter hook 'ng_slide_view_path' to specify your own template  
 */
?>

<article id="slide">

	<div class="content-header">

		<h1 class="content-title">
			<?php the_title(); ?>
		</h1>

		<div class="content-meta">

			<?php // set up some author variables
			$author_link = esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); 
			$author_title = esc_attr( sprintf( 'View all posts by %s' , get_the_author() ) ); ?>

			<a class="url fn n" href="<?php echo $author_link ?>" title="<?php echo $author_title ?>" rel="author">
				by <span class="author vcard"><?php echo get_the_author() ?></span>
			</a>

			<span class="sep"></span>
			
			<time class="date" datetime="<?php echo get_the_date( 'c' ) ?>" pubdate>
				<?php echo get_the_date() ?> 
			</time>

		</div><!-- .content-meta -->

	</div>

	<div class="primary">

		<div class="inner">

			<div class="media-container">
				<div class="media-wrapper">
					<?php ng_get_media() ?>
				</div>
			</div>

		</div><!-- .inner -->

	</div>

	<div class="sidebar">

		<div class="inner">

			<nav id="gallery-nav" class="nav">
				<?php ng_get_nav(); ?>
			</nav>

			<span class="edit">
				<?php edit_post_link(); ?>
			</span>

			<div class="description">
				<?php ng_get_description(); ?>
			</div>
			
			<?php // list the tags
			if( $tags = get_the_tag_list( '', '' ) ) : ?>
				<div class="tags">
					<span class="label"><?php echo __( 'Tags', 'soompi' ) ?></span>
					<?php echo $tags ?> 
				</div>
			<?php endif; // end if $tags ?>

		</div>

	</div>

</article><!-- #slide -->