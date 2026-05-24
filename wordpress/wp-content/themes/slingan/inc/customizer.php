<?php

declare(strict_types=1);

/**
 * Customizer: front page hero and brand colours.
 */
function slingan_customize_register(WP_Customize_Manager $wp_customize): void
{
    $wp_customize->add_panel('slingan_front', [
        'title' => __('Slingan startsida', 'slingan'),
        'priority' => 30,
    ]);

    $wp_customize->add_section('slingan_hero', [
        'title' => __('Banner', 'slingan'),
        'panel' => 'slingan_front',
    ]);

    $heroFields = [
        'slingan_hero_kicker' => ['Brädspel på Ribban', 'text'],
        'slingan_hero_heading' => ['Brädspel och gemenskap vid samma bord', 'text'],
        'slingan_hero_text' => [
            'En liten spelgemenskap: kom förbi på en spelträff, chatta i Discord, eller läs mer om oss.',
            'textarea',
        ], // Not shown on front page (tiles are the hero); kept for possible future use.
        'slingan_hero_btn_label' => ['Kommande spelträffar', 'text'],
        'slingan_hero_btn_url' => ['/speltraffar/', 'url'],
    ];

    foreach ($heroFields as $id => [$default, $type]) {
        $wp_customize->add_setting($id, [
            'default' => $default,
            'sanitize_callback' => $type === 'url' ? 'esc_url_raw' : 'sanitize_text_field',
        ]);
        $wp_customize->add_control($id, [
            'label' => ucfirst(str_replace(['slingan_hero_', '_'], ['', ' '], $id)),
            'section' => 'slingan_hero',
            'type' => $type === 'textarea' ? 'textarea' : 'text',
        ]);
    }

    $wp_customize->add_setting('slingan_hero_image', [
        'sanitize_callback' => 'esc_url_raw',
    ]);
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'slingan_hero_image', [
        'label' => __('Bakgrundsbild (valfritt)', 'slingan'),
        'description' => __('Foto visas under röd toning. Ta bort bilden för ren röd bakgrund.', 'slingan'),
        'section' => 'slingan_hero',
    ]));

    $wp_customize->add_setting('slingan_hero_bg_color', [
        'default' => '#d24749',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'slingan_hero_bg_color', [
        'label' => __('Bakgrundsfärg', 'slingan'),
        'section' => 'slingan_hero',
    ]));

    $wp_customize->add_section('slingan_colors', [
        'title' => __('Färger (State of Trust)', 'slingan'),
        'description' => __('Standard: röd #d24749, kol #1e1e1e, bakgrund #f5f5f0 — samma som stateoftrust-temat.', 'slingan'),
        'priority' => 40,
    ]);

    $colors = [
        'slingan_color_accent' => ['#d24749', __('Accent (röd)', 'slingan')],
        'slingan_color_secondary' => ['#1e1e1e', __('Kol / rubriker', 'slingan')],
        'slingan_color_bg' => ['#f5f5f0', __('Bakgrund', 'slingan')],
    ];

    foreach ($colors as $id => [$default, $label]) {
        $wp_customize->add_setting($id, [
            'default' => $default,
            'sanitize_callback' => 'sanitize_hex_color',
        ]);
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, $id, [
            'label' => $label,
            'section' => 'slingan_colors',
        ]));
    }

    $wp_customize->add_section('slingan_header', [
        'title' => __('Sidhuvud', 'slingan'),
        'priority' => 35,
    ]);

    $wp_customize->add_setting('slingan_header_cta_label', [
        'default' => 'Gå med',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('slingan_header_cta_label', [
        'label' => __('Knapp i sidhuvud', 'slingan'),
        'section' => 'slingan_header',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('slingan_header_cta_url', [
        'default' => '/join-us/',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    $wp_customize->add_control('slingan_header_cta_url', [
        'label' => __('Knapp — länk', 'slingan'),
        'section' => 'slingan_header',
        'type' => 'url',
    ]);
}
add_action('customize_register', 'slingan_customize_register');

/**
 * Output CSS variables from Customizer.
 */
function slingan_customizer_css(): void
{
    $accent = get_theme_mod('slingan_color_accent', '#d24749');
    $secondary = get_theme_mod('slingan_color_secondary', '#1e1e1e');
    $bg = get_theme_mod('slingan_color_bg', '#f5f5f0');

    $heroBg = slingan_hero_background_color();

    printf(
        '<style id="slingan-customizer-css">:root{--slingan-accent:%1$s;--slingan-secondary:%2$s;--slingan-bg:%3$s;--slingan-hero-bg:%4$s;}.slingan-hero{background-color:%4$s;}</style>',
        esc_attr($accent),
        esc_attr($secondary),
        esc_attr($bg),
        esc_attr($heroBg)
    );
}
add_action('wp_head', 'slingan_customizer_css', 50);
