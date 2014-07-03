<?php
/* Doctrine integration */
if ( ! class_exists('Doctrine_Manager')) {
	throw new \Exception(
		"Could not find Doctrine_Manager - either you didn't install with composer or you haven't included the composer autoloader in your project"
	);
}
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
