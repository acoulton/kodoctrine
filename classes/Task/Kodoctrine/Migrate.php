<?php

/**
 * The Migrate task runs migrations against the database schema to bring it up
 * to date.
 *
 * @package    KoDoctrine
 * @category   Administration
 * @author     Andrew Coulton
 * @copyright  (c) 2012 Andrew Coulton
 * @license    http://kohanaframework.org/license
 */
class Task_Kodoctrine_Migrate extends Minion_Task
{
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

		if (Minion_CLI::read(PHP_EOL.'This cannot be reversed - please confirm you wish to proceed',array('y','n'))!='y')
		{
			Minion_CLI::write('Your database has not been changed. To run migrations in future, run minion kodoctrine:migrate');
			return;
		}

		Minion_CLI::write('Please provide credentials of a user with permission to migrate');
		Minion_CLI::write('-database '.Doctrine_Manager::connection()->getOption('dsn'));
		$user = Minion_CLI::read(PHP_EOL.'Username');
		$password = Minion_CLI::read('Password');
		$this->elevate_db_user($user, $password);

		$migration->migrate();

		Minion_CLI::write('Your database was successfully migrated to version '.$migration->getLatestVersion());
	}
}