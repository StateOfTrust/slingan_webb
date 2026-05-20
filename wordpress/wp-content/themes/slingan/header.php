<?php

declare(strict_types=1);
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
<a class="slingan-skip" href="#main"><?php esc_html_e('Skip to content', 'slingan'); ?></a>
<header class="slingan-header" role="banner">
    <div class="slingan-header__brand">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="slingan-header__title-link">
            <span class="slingan-header__title"><?php bloginfo('name'); ?></span>
            <?php if (get_bloginfo('description', 'display')) : ?>
                <span class="slingan-header__tagline"><?php bloginfo('description'); ?></span>
            <?php endif; ?>
        </a>
    </div>
    <?php if (has_nav_menu('primary')) : ?>
        <nav class="slingan-header__nav" aria-label="<?php esc_attr_e('Primary', 'slingan'); ?>">
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
</header>
