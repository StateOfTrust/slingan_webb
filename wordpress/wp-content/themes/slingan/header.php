<?php

declare(strict_types=1);

$ctaLabel = get_theme_mod('slingan_header_cta_label', '');
$ctaUrl = slingan_theme_mod_url('slingan_header_cta_url', '/join-us/');
$logoUrl = slingan_header_logo_url();
$logoW = 216;
$logoH = 67;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('slingan-site'); ?>>
<?php wp_body_open(); ?>
<a class="slingan-skip" href="#main"><?php esc_html_e('Hoppa till innehåll', 'slingan'); ?></a>
<header class="slingan-header" role="banner">
    <div class="slingan-header__brand">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="slingan-header__logo-link">
            <img
                src="<?php echo esc_url($logoUrl); ?>"
                alt="<?php echo esc_attr(get_bloginfo('name')); ?>"
                class="slingan-header__logo"
                width="<?php echo (int) $logoW; ?>"
                height="<?php echo (int) $logoH; ?>"
                decoding="async"
            >
        </a>
    </div>
    <?php if (has_nav_menu('primary')) : ?>
        <nav class="slingan-header__nav" aria-label="<?php esc_attr_e('Huvudmeny', 'slingan'); ?>">
            <?php
            wp_nav_menu([
                'theme_location' => 'primary',
                'container' => false,
                'menu_class' => 'slingan-nav',
                'fallback_cb' => false,
            ]);
            ?>
        </nav>
    <?php endif; ?>
    <?php if ($ctaLabel !== '' && $ctaUrl !== '') : ?>
        <a class="slingan-btn slingan-btn--header" href="<?php echo esc_url($ctaUrl); ?>">
            <?php echo esc_html($ctaLabel); ?>
        </a>
    <?php endif; ?>
</header>
