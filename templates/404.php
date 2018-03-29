<article role="article" class="error-404 not-found text-center">

    <header class="entry-header">
        <h1 class="entry-title">
            <span class="title404">404</span><br/>
            <?php _e( 'Oops! That page can&rsquo;t be found.', 'waboot' ); ?>
        </h1>
    </header>

    <div class="entry-content">
            <p><?php _e( 'It looks like nothing was found at this location. Maybe try a search or one of the links below?', 'waboot' ); ?></p>
            <?php get_search_form(); ?>
            <p><?php _e( 'Let\'s return to the', 'waboot' ); ?> <a href="<?php echo get_site_url(); ?>">Homepage</a> </p>
    </div><!-- .entry-content -->

</article>


