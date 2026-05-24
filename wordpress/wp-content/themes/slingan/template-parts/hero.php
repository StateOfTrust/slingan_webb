<?php

declare(strict_types=1);

$heading = get_theme_mod('slingan_hero_heading', '');
$text = get_theme_mod('slingan_hero_text', '');
$kicker = get_theme_mod('slingan_hero_kicker', 'Brädspel på Ribban');
$btnLabel = get_theme_mod('slingan_hero_btn_label', '');
$btnUrl = slingan_theme_mod_url('slingan_hero_btn_url', '/speltraffar/');
$image = get_theme_mod('slingan_hero_image', '');
$heroBg = slingan_hero_background_color();

$styleParts = ['background-color:' . $heroBg];
if ($image !== '') {
    $styleParts[] = 'background-image:url(' . esc_url($image) . ')';
}
$style = ' style="' . esc_attr(implode(';', $styleParts)) . '"';
?>
<section class="slingan-hero"<?php echo $style; ?>>
    <div class="slingan-hero__overlay">
        <div class="slingan-hero__inner">
            <?php if ($kicker !== '') : ?>
                <p class="slingan-hero__kicker"><?php echo esc_html($kicker); ?></p>
            <?php endif; ?>
            <?php if ($heading !== '') : ?>
                <h1 class="slingan-hero__title"><?php echo esc_html($heading); ?></h1>
            <?php endif; ?>
            <?php if ($text !== '') : ?>
                <p class="slingan-hero__lead"><?php echo esc_html($text); ?></p>
            <?php endif; ?>
            <?php if ($btnLabel !== '' && $btnUrl !== '') : ?>
                <p class="slingan-hero__cta">
                    <a class="slingan-btn slingan-btn--primary slingan-btn--hero" href="<?php echo esc_url($btnUrl); ?>">
                        <?php echo esc_html($btnLabel); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</section>
