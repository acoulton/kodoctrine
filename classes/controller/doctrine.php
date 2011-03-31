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
        $Config = Kohana::config('doctrine');
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
        $Config = Kohana::config('doctrine');
        $this->request->response = View::factory('kodoctrine/build_database')
                                    ->set('db_connection',Doctrine_Manager::connection())
                                    ->bind('executed',$executed)
                                    ->bind('migration',$migration)
                                    ->bind('build_response',$build_response);
        if ($_POST) {
            $executed = true;
            $this->elevate_db_user($_POST);
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
        $Config = Kohana::config('doctrine');
        $this->request->response = View::factory('kodoctrine/build_migration')
                                    ->bind('migration',$migration)
                                    ->bind('changes',$changes)
                                    ->bind('preview',$preview);

        //we need to be in MODEL_LOADING_AGGRESSIVE (PEAR doesn't work)
        $manager = Doctrine_Manager::getInstance();
        $modelLoading = $manager->getAttribute(Doctrine_Core::ATTR_MODEL_LOADING);
        $manager->setAttribute(Doctrine_Core::ATTR_MODEL_LOADING, Doctrine_Core::MODEL_LOADING_AGGRESSIVE);
        //generate the migration classes
        foreach ($Config->schemaFiles as $schemaFile) {
            $oldVersion = $Config->schemaPath.'history\\'.$schemaFile;
            $newVersion = $Config->schemaPath.$schemaFile;

            //create a blank history file if none exists
            if ( ! file_exists($oldVersion)) {
                file_put_contents($oldVersion, "");
            }

            if ($_POST) {
                $preview = false;

                $changes[$schemaFile] = Doctrine_Core::generateMigrationsFromDiff(
                        $Config->schemaPath.'migrations',
                        $oldVersion, $newVersion);

                //and copy the old file over the new one
                copy($newVersion, $oldVersion);
            } else {
                $preview = true;
                $diff = new Doctrine_Migration_Diff($oldVersion, $newVersion,
                                $Config->schemaPath.'migrations');
                $changes[$schemaFile] = $diff->generateChanges();
            }

        }
        //return to the old MODEL_LOADING value
        $manager->setAttribute(Doctrine_Core::ATTR_MODEL_LOADING, $modelLoading);

        if ($_POST) {
            //now build the model files
            $this->action_buildModels();
        }

        $migration = new Doctrine_Migration($Config->schemaPath.'migrations');
    }

    /**
     * This action migrates the database to current version. Note that it does not
     * carry out any authentication or confirmation, nor does it backup the DB.
     * These are all left as actions for future implementation.
     */
    public function action_migrate() {
        //@todo: Backup before beginning!
        $Config = Kohana::config('doctrine');
        $migration = new Doctrine_Migration($Config->schemaPath.'migrations');
        $this->request->response = View::factory('kodoctrine/migrate')
                                    ->set('migration',$migration)
                                    ->set('db_connection',Doctrine_Manager::connection())
                                    ->bind('migrate_done', $migrate_done);

        if ($_POST) {
            $this->elevate_db_user($_POST);
            $migration->migrate();
            $migrate_done = true;
        }
    }

    protected function elevate_db_user($values) {
        $dsn = Arr::get($values, 'db_dsn');
        $connection = Doctrine_Manager::connection();
        if ($dsn != $connection->getOption('dsn')) {
            throw new Exception("Current database connection is not the one for which elevation was requested");
        }
        $connection->close();
        $connection->setOption('username', Arr::get($values, 'db_username'));
        $connection->setOption('password', Arr::get($values, 'db_password'));
        $connection->connect();
    }
}
