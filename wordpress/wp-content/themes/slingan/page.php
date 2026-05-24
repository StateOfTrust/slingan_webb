<?php

declare(strict_types=1);

get_header();
?>

<main id="main" class="site-main site-main--page">
    <?php
    while (have_posts()) {
        the_post();
        ?>
        <article <?php post_class('slingan-entry'); ?> id="post-<?php the_ID(); ?>">
            <header class="entry-header">
                <h1 class="entry-title"><?php the_title(); ?></h1>
            </header>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
        </article>
        <?php
    }
    ?>
</main>

<?php
get_footer();
