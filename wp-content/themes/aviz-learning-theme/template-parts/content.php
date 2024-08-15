<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <?php
        if (is_singular()) :
            the_title('<h1 class="entry-title">', '</h1>');
        else :
            the_title('<h2 class="entry-title"><a href="' . esc_url(get_permalink()) . '" rel="bookmark">', '</a></h2>');
        endif;
        ?>
    </header>

    <?php if (has_post_thumbnail()) : ?>
        <div class="post-thumbnail">
            <?php the_post_thumbnail(); ?>
        </div>
    <?php endif; ?>

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