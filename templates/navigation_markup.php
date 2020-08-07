<nav class="navigation posts-navigation" role="navigation" aria-label="<?php echo __('Posts navigation'); ?>">
    <h2 class="screen-reader-text"><?php echo __('Posts navigation'); ?></h2>
    <div class="nav-links">
        <ul class="page-numbers">
            <?php foreach ($page_links as $page_link): ?>
            <li><?php echo $page_link; ?>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
