<?php

/**
 * The Migrate task runs migrations against the database schema to bring it up to date.
 * Provide a user and password with permission to modify the database schema in the --user and --password arguments.
 *
 * [!!] Passing credentials as arguments is not good security practice - be cautious about doing this unless you are
 *      sure you have control of your environment.
 *
 * @package    KoDoctrine
 * @category   Administration
 * @author     Andrew Coulton
 * @copyright  (c) 2014 Andrew Coulton
 * @license    http://kohanaframework.org/license
 */
class Task_Kodoctrine_Migrate extends Minion_Task
{

	/**
	 * @var string[]
	 */
	protected $_options = array(
		'user'     => NULL,
		'password' => NULL,
	);

	/**
	 * @param Validation $validation
	 * @return Validation
	 */
	public function build_validation(Validation $validation)
	{
		$validation->rule('user',     'not_empty');
		$validation->rule('password', 'not_empty');
		return parent::build_validation($validation);
	}

	/**
	 * Reconnects as an account with privileges to carry out the migration
	 * @param string $user
	 * @param string $password
	 */
	protected function elevate_db_user($user, $password) {
		$connection = Doctrine_Manager::connection();
		$connection->close();
		$connection->setOption('username', $user);
		$connection->setOption('password', $password);
		$connection->connect();
	}

	public function _execute(array $params)
	{
		$config = Kohana::$config->load('doctrine');
		$this->elevate_db_user($params['user'], $params['password']);

		$migration_path = $config->migration_classes;
		Minion_CLI::write('Checking for migrations in '.Debug::path($migration_path).PHP_EOL);
		$migration = new Doctrine_Migration($migration_path);

		$current = $migration->getCurrentVersion();
		$latest = $migration->getLatestVersion();

		if ($current == $latest)
		{
			Minion_CLI::write('Your database is up to date at version '.$current, 'green');
			return;
		}

		Minion_CLI::write('*********************************************************************');
		Minion_CLI::write('Your database is at version '.$current.' and needs to be migrated to version '.$latest,
			'white','red');
		Minion_CLI::write('*********************************************************************'.PHP_EOL);

		$migration->migrate();

		Minion_CLI::write('Your database was successfully migrated to version '.$migration->getLatestVersion());
	}
}
