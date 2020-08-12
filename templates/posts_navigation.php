<?php if ($navigation_links['prev_link'] || $navigation_links['next_link']): ?>
<p class="post-nav-links">
    <?php if ($navigation_links['prev_link']): ?>
    <a href="<?php echo esc_url($navigation_links['prev_link']); ?>" class="post-page-numbers">
        <?php echo $prev_text; ?>
    </a><?php if ($navigation_links['next_link']): ?>&nbsp;<?php endif; ?>
    <?php endif; ?>
    <?php if ($navigation_links['next_link']): ?>
    <a href="<?php echo esc_url($navigation_links['next_link']); ?>" class="post-page-numbers">
        <?php echo $next_text; ?>
    </a>
    <?php endif; ?>
</p>
<?php endif; ?>
