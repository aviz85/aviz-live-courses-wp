<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">

    </header>

    <div class="entry-content">
        <?php
        the_content();
        wp_link_pages();
        ?>
    </div>

    <footer class="entry-footer">
        <?php
        if ('post' === get_post_type()) :
            ?>
            <div class="entry-meta">
                <?php
                aviz_posted_on();
                aviz_posted_by();
                ?>
            </div>
        <?php endif; ?>
    </footer>
</article>