<?php defined('ABSPATH') or die; ?>

<div class="wrap">
    <h1><?php print get_admin_page_title() ?></h1>
    <?php foreach( CodersApp::list(true) as $ep => $content ) : ?>
    <div class="card">
        <h2><?php print $ep ?></h2>
        <p><i class=""><?php print $content['type'] ?></i></p>
    </div>
    <?php endforeach; ?>
</div>

