<?php
/* Doctrine integration */
require Kohana::find_file('vendor', 'doctrine1.2/lib/Doctrine');
spl_autoload_register(array('Doctrine', 'autoload'));

// Get configurations for doctrine
$Config = Kohana::$config->load('doctrine');

// initializing manager
$manager = Doctrine_Manager::getInstance();

//Create connections
foreach ($Config->connections as $name=>$connect) {
    $manager->connection($connect,$name);
}

//Configure Doctrine
// @see http://www.doctrine-project.org/documentation/manual/1_1/en/configuration
foreach ($Config->attributes as $attrib => $value) {
    $manager->setAttribute($attrib, $value);
}

//we need to register a dummy validator so that the schema builder doesn't complain about the validation element
$manager->registerValidators('validation');

//Block normal access to the doctrine controller
Route::set('doctrineBlock', 'doctrine')
  ->defaults(array(
    'controller' => 'doctrine',
    'action'     => 'directAccessError'
  ));

Route::set('doctrineAdmin', $Config->adminRoute."(/<action>(/<id>))")
        ->defaults(array(
		'controller' => 'doctrine',
		'action'     => 'index',
	));