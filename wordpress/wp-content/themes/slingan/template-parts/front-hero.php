<?php

declare(strict_types=1);

$posts = slingan_get_blog_roll_posts();
$heading = get_theme_mod('slingan_hero_heading', '');
$kicker = get_theme_mod('slingan_hero_kicker', 'Brädspel på Ribban');
$btnLabel = get_theme_mod('slingan_hero_btn_label', '');
$btnUrl = slingan_theme_mod_url('slingan_hero_btn_url', '/speltraffar/');
$heroBg = slingan_hero_background_color();
?>
<section class="slingan-front-hero" aria-label="<?php esc_attr_e('Slingan startsida', 'slingan'); ?>">
    <div class="slingan-front-hero__intro" style="background-color:<?php echo esc_attr($heroBg); ?>">
        <div class="slingan-front-hero__intro-inner">
            <div class="slingan-front-hero__copy">
                <?php if ($kicker !== '') : ?>
                    <p class="slingan-front-hero__kicker"><?php echo esc_html($kicker); ?></p>
                <?php endif; ?>
                <?php if ($heading !== '') : ?>
                    <h1 class="slingan-front-hero__title"><?php echo esc_html($heading); ?></h1>
                <?php endif; ?>
            </div>
            <?php if ($btnLabel !== '' && $btnUrl !== '') : ?>
                <a class="slingan-btn slingan-btn--primary slingan-btn--hero" href="<?php echo esc_url($btnUrl); ?>">
                    <?php echo esc_html($btnLabel); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="slingan-front-hero__main">
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
    </div>
</section>
