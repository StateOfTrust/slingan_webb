<?php

declare(strict_types=1);

/**
 * Static front page: compact intro + four tiles (above the fold).
 */
get_header();
?>

<main id="main" class="site-main site-main--front slingan-front">
    <?php get_template_part('template-parts/front-hero'); ?>
</main>

<?php
get_footer();
