<?php
if ( post_password_required() ) {
    return;
}
?>

<div id="comments" class="comments-area">

    <?php if ( have_comments() ) : ?>
        <h2 class="comments-title">
            <?php
            $comments_number = get_comments_number();
            if ( '1' === $comments_number ) {
                printf( _x( 'תגובה אחת ל־"%s"', 'comments title', 'aviz-learning-theme' ), get_the_title() );
            } else {
                printf(
                    /* translators: 1: number of comments, 2: post title */
                    _nx(
                        '%1$s תגובה ל־"%2$s"',
                        '%1$s תגובות ל־"%2$s"',
                        $comments_number,
                        'comments title',
                        'aviz-learning-theme'
                    ),
                    number_format_i18n( $comments_number ),
                    get_the_title()
                );
            }
            ?>
        </h2>

        <ol class="comment-list">
            <?php
            wp_list_comments( array(
                'style'       => 'ol',
                'short_ping'  => true,
                'avatar_size' => 60,
            ) );
            ?>
        </ol>

        <?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>
        <nav class="comment-navigation" role="navigation">
            <div class="nav-previous"><?php previous_comments_link( __( '&larr; תגובות קודמות', 'aviz-learning-theme' ) ); ?></div>
            <div class="nav-next"><?php next_comments_link( __( 'תגובות חדשות &rarr;', 'aviz-learning-theme' ) ); ?></div>
        </nav>
        <?php endif; ?>

    <?php endif; ?>

    <?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
        <p class="no-comments"><?php _e( 'התגובות סגורות.', 'aviz-learning-theme' ); ?></p>
    <?php endif; ?>

    <?php comment_form(); ?>

</div>