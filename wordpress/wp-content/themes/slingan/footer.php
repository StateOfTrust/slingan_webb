<?php

declare(strict_types=1);
?>
<footer class="slingan-footer" role="contentinfo">
    <p class="slingan-footer__copy">
        &copy; <?php echo esc_html((string) gmdate('Y')); ?>
        <a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a>
    </p>
</footer>
<?php wp_footer(); ?>
</body>
</html>
