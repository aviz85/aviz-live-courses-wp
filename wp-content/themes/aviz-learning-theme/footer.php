</div><!-- #content -->

<footer id="colophon" class="site-footer">
    <div class="site-info">
        <?php
        printf(esc_html__('© %1$s %2$s', 'aviz-learning-theme'), date('Y'), get_bloginfo('name'));
        ?>
    </div>
    <nav class="footer-navigation">
        <?php
        wp_nav_menu(array(
            'theme_location' => 'footer',
            'menu_id'        => 'footer-menu',
        ));
        ?>
    </nav>
</footer>
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>