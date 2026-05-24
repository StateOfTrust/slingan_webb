<?php

/**
 * Plugin Name: Slingan BGG plays
 * Description: Shows recent board games played from a BoardGameGeek profile in Slingan tile style.
 * Version: 1.0.0
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$secrets = __DIR__ . '/slingan-bgg-secrets.php';
if (is_readable($secrets)) {
    require_once $secrets;
}

define('SLINGAN_BGG_PLAYS_DIR', __DIR__ . '/slingan-bgg-plays');

require_once SLINGAN_BGG_PLAYS_DIR . '/includes/class-slingan-bgg-api.php';
require_once SLINGAN_BGG_PLAYS_DIR . '/includes/class-slingan-bgg-plays-list.php';
require_once SLINGAN_BGG_PLAYS_DIR . '/includes/class-slingan-bgg-plays-top.php';
require_once SLINGAN_BGG_PLAYS_DIR . '/includes/class-slingan-bgg-plays.php';

Slingan_Bgg_Plays::init();
Slingan_Bgg_Plays_List::init();
Slingan_Bgg_Plays_Top::init();
