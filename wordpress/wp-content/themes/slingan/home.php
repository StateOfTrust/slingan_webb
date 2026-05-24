<?php

declare(strict_types=1);

/**
 * No posts page — blog lives as four tiles on the front page only.
 */
wp_safe_redirect(home_url('/'), 301);
exit;
