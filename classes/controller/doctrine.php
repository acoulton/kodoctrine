<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Controller for accessing Doctrine commands used to build models, migrations,
 * etc. This is currently NOT SECURE and requires further work to implement a
 * secure means of handling authentication on an application based level.
 *
 * @package    KoDoctrine
 * @category   Administration
 * @author     Andrew Coulton
 * @copyright  (c) 2010 Andrew Coulton
 * @license    http://kohanaphp.com/license
 */
class Controller_Doctrine extends Controller {

    /**
     * Builds all model files, based on the schema files loaded in the
     * application config file.
     */
    public function action_buildModels() {
        throw new Exception("Uhoh, this doesn't work with the CFS!");
        $Config = Kohana::$config->load('doctrine');
        foreach ($Config->schemaFiles as $file) {
            $files[] = Kohana::find_file('schema', $file,'yml');
        }
        Doctrine_Core::generateModelsFromYaml(
                    $files,
                    $Config->modelPath,
                    $Config->builderOptions);

    }

    /**
     * Rebuilds the database entirely - not for the fainthearted! This action will
     * drop your entire database and rebuild, without any further confirmation.
     *
     * Of course, you shouldn't have a means for the standard web user to do this anyway,
     * so it should not be an issue in a production environment. But security of this
     * action will be tightened up in further releases!
     */
    public function action_buildDatabase() {
        throw new Exception("Uhoh, this doesn't work with the CFS!");
        $Config = Kohana::$config->load('doctrine');
        $view = View::factory('kodoctrine/build_database')
                                    ->set('db_connection',Doctrine_Manager::connection())
                                    ->bind('executed',$executed)
                                    ->bind('migration',$migration)
                                    ->bind('build_response',$build_response);
        if ($creds = $this->interactive_credentials()) {
            $executed = true;
            set_time_limit(90);
            $this->elevate_db_user($creds);
            Doctrine_Core::dropDatabases();
            Doctrine_Core::createDatabases();
            $migration = new Doctrine_Migration($Config->schemaPath.'migrations');
            $migration->setCurrentVersion($migration->getLatestVersion());
            Doctrine_Core::createTablesFromModels($Config->modelPath);
            foreach ($Config->defaultDataFixtures as $fixtureFile) {
                Doctrine_Core::loadData($fixtureFile);
            }
            $build_response = Doctrine_Manager::connection()->import->listTables();
        }

        $this->response->body($view);
    }

    /**
     * This function will rebuild models, and then the database - without any
     * confirmation!
     */
    public function action_buildAll() {
        $this->action_buildModels();
        $this->action_buildDatabase();
    }

    /**
     * This action will rebuild migrations against the schema changes. It does this
     * by using a second copy of the schema file in the {schemaPath}\history folder
     * which is used as a comparator. After building migrations, it copies the amended
     * file over the top of the previous one.
     *
     * Workflow is therefore:
     * 1. Amend your schema files
     * 2. Update code from repository to ensure at current version
     * 3. Run doctrine/buildMigrations()
     * 4. Commit the new migrations files, the new schema file and the history folder
     */
    public function action_buildMigrations() {
        $Config = Kohana::$config->load('doctrine');
        $view = View::factory('kodoctrine/build_migration')
                                    ->bind('migration',$migration)
                                    ->bind('changes',$changes)
                                    ->bind('preview',$preview);


        $diff = new KoDoctrine_Migration_Diff();
        $changes = $diff->generateChanges();
        $preview = true;

        if ($_POST)
        {
            $preview = false;
            $diff->generateMigrationClasses();
        }

        $migration = $diff->migration();

        $this->response->body($view);
    }

    /**
     * This action migrates the database to current version. Note that it does not
     * carry out any authentication or confirmation, nor does it backup the DB.
     * These are all left as actions for future implementation.
	 *
	 * THIS METHOD IS DEPRECATED - use minion kodoctrine:migrate
	 * @deprecated
     */
    public function action_migrate() {
        //@todo: Backup before beginning!
        $Config = Kohana::$config->load('doctrine');
        $migration = new Doctrine_Migration($Config->migration_classes);
        $view = View::factory('kodoctrine/migrate')
                                    ->set('migration',$migration)
                                    ->set('db_connection',Doctrine_Manager::connection())
                                    ->bind('migrate_done', $migrate_done);

        if ($creds = $this->interactive_credentials()) {
            $this->elevate_db_user($creds);
            $migration->migrate();
            $migrate_done = true;
        }
        $this->response->body($view);
    }

    /**
     * Checks if this is an interactive request and returns credentials
     * @return array
     */
    protected function interactive_credentials()
    {
        if ($this->request->method() == 'POST')
        {
            $dsn = Arr::get($this->request->post(), 'db_dsn');
            $connection = Doctrine_Manager::connection();
            if ($dsn != $connection->getOption('dsn'))
            {
                throw new Exception("Current database connection is not the one for which elevation was requested");
            }

            return Arr::extract($this->request->post(), array('db_username', 'db_password'));
        }

        if (Kohana::$is_cli)
        {
            $args = CLI::options('user','pwd');
            return array(
                'db_username'=>Arr::get($args, 'user'),
                'db_password'=>Arr::get($args, 'pwd'));
        }
    }

    protected function elevate_db_user($values) {
        $connection = Doctrine_Manager::connection();
        $connection->close();
        $connection->setOption('username', Arr::get($values, 'db_username'));
        $connection->setOption('password', Arr::get($values, 'db_password'));
        $connection->connect();
    }
}
