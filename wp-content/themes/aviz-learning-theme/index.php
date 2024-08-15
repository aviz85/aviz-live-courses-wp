<?php get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <?php
        if (have_posts()) :
            while (have_posts()) :
                the_post();
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class('post'); ?>>
                    <h2 class="post-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    <div class="post-meta">
                        <?php echo get_the_date(); ?> | <?php the_author(); ?>
                    </div>
                    <div class="post-content">
                        <?php the_excerpt(); ?>
                    </div>
                    <a href="<?php the_permalink(); ?>" class="read-more">קרא עוד</a>
                </article>
                <?php
            endwhile;

            the_posts_navigation();
        else :
            ?>
            <p>לא נמצאו פוסטים.</p>
            <?php
        endif;
        ?>
    </main>
</div>

<?php get_footer(); ?>