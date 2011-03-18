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
            Doctrine_Core::generateModelsFromYaml(
                    $Config->schemaPath,
                    $Config->modelPath,
                    $Config->builderOptions);
        }
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
        Doctrine_Core::dropDatabases();
        Doctrine_Core::createDatabases();
        $this->request->response = Doctrine_Core::createTablesFromModels($Config->modelPath);
        foreach ($Config->defaultDataFixtures as $fixtureFile) {
            Doctrine_Core::loadData($fixtureFile);
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

            $changes[$schemaFile] = Doctrine_Core::generateMigrationsFromDiff(
                        $Config->schemaPath.'migrations',
                        $oldVersion, $newVersion);
            
            //and copy the old file over the new one
            copy($newVersion, $oldVersion);
            
        }
        //return to the old MODEL_LOADING value
        $manager->setAttribute(Doctrine_Core::ATTR_MODEL_LOADING, $modelLoading);

        //now build the model files
        $this->action_buildModels();

        //now return the data
        $this->request->response = "<PRE>" . Kohana::dump($changes) . "</PRE>";
    }

    /**
     * This action migrates the database to current version. Note that it does not
     * carry out any authentication or confirmation, nor does it backup the DB.
     * These are all left as actions for future implementation.
     */
    public function action_migrate() {
        //@todo: Authentication
        //@todo: Confirmation
        //@todo: Backup before beginning!
        $Config = Kohana::config('doctrine');

        $migration = new Doctrine_Migration($Config->schemaPath.'migrations');
        $migration->migrate();
        $this->request->response = "OK";
    }
}
