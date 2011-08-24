<?php defined('SYSPATH') or die('No direct script access.');?>
<h2>Build Database</h2>
<?php if ($executed):?>
    <p>Successfully built database. Initialised migrations to version
        <em><?=$migration->getCurrentVersion()?></em>.</p>
    <p><?=Debug::dump($build_response);?></p>
<?php else:?>
    <p style="background-color:#cc0000;border:1px solid red; font-size:2em;">
        This action will delete and rebuild your entire database. All data will
        be lost. Only proceed if you have a backup and are certain you know what you are doing!
    </p>
<?php echo View::factory('kodoctrine/db_auth')
            ->set('db_connection',$db_connection)?>
<?php endif;?>