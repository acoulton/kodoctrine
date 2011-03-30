<?php defined('SYSPATH') or die('No direct script access.');
/* @var $migration Doctrine_Migration */?>
<h2>Migrate database</h2>
<p>Your version: <?=$migration->getCurrentVersion();?></p>
<p>Latest version: <?=$migration->getLatestVersion();?></p>
<?php echo View::factory('kodoctrine/db_auth')
            ->set('db_connection',$db_connection)?>
