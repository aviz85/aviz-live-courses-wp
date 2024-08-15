<?php get_header(); ?>

<div class="error-404 not-found">
    <div class="page-content">
        <div class="error-container">
            <h1 class="error-title">404</h1>
            <div class="error-face">
                <div class="error-eyes">
                    <div class="error-eye"></div>
                    <div class="error-eye"></div>
                </div>
                <div class="error-mouth"></div>
            </div>
            <p class="error-message">אופס! נראה שהלכנו לאיבוד...</p>
            <p>הדף שחיפשת לא נמצא. אולי הוא יצא לטיול קצר?</p>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="back-home">חזרה לדף הבית</a>
        </div>
    </div>
</div>

<?php get_footer(); ?>