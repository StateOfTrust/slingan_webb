<?php

declare(strict_types=1);
?>
<dialog class="slingan-rsvp-modal" id="slingan-rsvp-modal" aria-labelledby="slingan-rsvp-modal-title">
    <div class="slingan-rsvp-modal__panel" role="document">
        <button type="button" class="slingan-rsvp-modal__close" data-slingan-rsvp-close aria-label="<?php esc_attr_e('Stäng', 'slingan'); ?>">
            <span aria-hidden="true">&times;</span>
        </button>
        <header class="slingan-rsvp-modal__header">
            <h2 id="slingan-rsvp-modal-title" class="slingan-rsvp-modal__title"></h2>
            <p class="slingan-rsvp-modal__schedule"></p>
        </header>
        <div class="slingan-rsvp-modal__body" data-slingan-rsvp-body></div>
        <footer class="slingan-rsvp-modal__footer">
            <a class="slingan-rsvp-modal__article" href="#" data-slingan-rsvp-article hidden>
                <?php esc_html_e('Hela inlägget', 'slingan'); ?>
            </a>
        </footer>
    </div>
</dialog>
