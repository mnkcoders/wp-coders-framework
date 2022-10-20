<?php defined('ABSPATH') or die; ?>

<div class="wrap">
    <?php foreach( CodersApp::list() as $endpoint ) :?>
    <p><a href="<?php print get_site_url() . '/' . $endpoint . '/' ?>" target="_self"><?php print $endpoint  ?></a></p>
    <?php endforeach; ?>
</div>

