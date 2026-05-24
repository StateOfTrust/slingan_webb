<?php

declare(strict_types=1);

get_header();
?>

<main id="main" class="site-main site-main--single">
    <?php
    while (have_posts()) {
        the_post();
        ?>
        <article <?php post_class('slingan-entry'); ?> id="post-<?php the_ID(); ?>">
            <header class="entry-header">
                <p class="entry-meta">
                    <time datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                        <?php echo esc_html(get_the_date()); ?>
                    </time>
                </p>
                <h1 class="entry-title"><?php the_title(); ?></h1>
            </header>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
            <?php
            $linkedEvent = slingan_post_linked_event(get_post());
            if ($linkedEvent instanceof WP_Post) {
                get_template_part('template-parts/post-event-tickets', null, [
                    'event' => $linkedEvent,
                ]);
            }
            ?>
        </article>
        <?php
    }
    ?>
</main>

<?php
get_footer();
