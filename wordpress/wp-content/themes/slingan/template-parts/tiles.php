<?php

declare(strict_types=1);

$posts = slingan_get_blog_roll_posts();
?>
<section class="slingan-tiles" aria-labelledby="slingan-tiles-heading">
    <header class="slingan-tiles__header">
        <p class="slingan-tiles__eyebrow"><?php esc_html_e('Från bordet', 'slingan'); ?></p>
        <h2 id="slingan-tiles-heading" class="slingan-tiles__title"><?php esc_html_e('Senaste', 'slingan'); ?></h2>
        <p class="slingan-tiles__intro"><?php esc_html_e('De fyra senaste inläggen — inget separat bloggarkiv.', 'slingan'); ?></p>
    </header>
    <?php if ($posts === []) : ?>
        <p class="slingan-tiles__empty">
            <?php
            printf(
                wp_kses_post(__('Inga inlägg ännu. <a href="%s">Skriv det första inlägget</a> i admin.', 'slingan')),
                esc_url(admin_url('post-new.php'))
            );
            ?>
        </p>
    <?php else : ?>
        <?php
        get_template_part('template-parts/blog-roll-carousel', null, [
            'posts' => $posts,
        ]);
        ?>
    <?php endif; ?>
</section>
