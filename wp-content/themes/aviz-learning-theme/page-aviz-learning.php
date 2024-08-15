<?php
/*
Template Name: Aviz Learning Platform
*/
get_header();
?>

<div class="aviz-container">
    <header class="aviz-header">
        <h1>פלטפורמת הלמידה של אביץ</h1>
        <nav class="aviz-nav">
            <ul>
                <li><a href="#courses">הקורסים שלי</a></li>
                <li><a href="#progress">התקדמות</a></li>
                <li><a href="#profile">פרופיל</a></li>
            </ul>
        </nav>
    </header>

    <main class="aviz-main">
        <section id="courses" class="aviz-section">
            <h2>הקורסים שלי</h2>
            <?php echo do_shortcode('[aviz_main_dashboard]'); ?>
        </section>

        <section id="progress" class="aviz-section">
            <h2>התקדמות כללית</h2>
            <!-- כאן תוכל להוסיף תרשים או מידע על התקדמות כללית -->
        </section>

        <section id="profile" class="aviz-section">
            <h2>הפרופיל שלי</h2>
            <!-- כאן תוכל להוסיף מידע על הפרופיל של המשתמש -->
        </section>
    </main>
</div>

<?php get_footer(); ?>