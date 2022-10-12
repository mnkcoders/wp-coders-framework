<?php
defined('ABSPATH') or die;

$plugin_data = CodersApp::pluginInfo();
$instances = CodersApp::list();
?>

<div class="container">
    <?php if ($plugin_data !== FALSE) : ?>
        <h2 class="title"><?php
            print preg_replace('/<a /', '<a class="author" target="_blank" ',
                            $plugin_data['Title'])
            ?> [v<?php print $plugin_data['Version'] ?>]</h2>
        <!--p><span class="author"><?php print $plugin_data['AuthorName'] ?></span></p-->
    <?php endif; ?>
</div>

<div class="container">
    <h2 class="title"><?php print __('Active Applications', 'coders_framework') ?></h2>
    <?php if (count($instances)) : ?>
        <ul class="list">
            <?php foreach ($instances as $endpoint => $app) : ?>
                <li class="app-box">
                    <a href="<?php print $app['url'] ?>"
                       target="_blank"
                       class="app-name button <?php print $app['type'] ?>">
                           <?php print $app['name'] ?>
                        <?php if (strlen($app['key'])): ?>
                            <strong class="app-key green">[ <?php print $app['key'] ?> ]</strong>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p><?php print __('No applications detected', 'coders_framework'); ?></p>
    <?php endif; ?>
</div>
