<?php
defined('SYSPATH') or die('No direct script access.');

class KoDoctrine_Migration_Diff extends Doctrine_Migration_Diff
{

    public function __construct()
    {
        // We always go from a schema state file
        // To all the current models
        // So there's nothing to do here!

        $this->_from = Kohana::config('doctrine.migration_schema');
        $this->_migration = new Doctrine_Migration(Kohana::config('doctrine.migration_classes'));
    }

    protected function _from_schema()
    {
        static $schema = array();

        if ($schema)
        {
            return $schema;
        }

        // Load the fromInfo from the application path
        if (file_exists($this->_from))
        {
            $schema = Doctrine_Parser::load($this->_from, 'yml');
        }

        return $schema;
    }

    public function generateChanges()
    {
        static $diff = null;

        if ( $diff)
        {
            return $diff;
        }

        $fromInfo = $this->_from_schema();

        // Load all models
        $toInfo = $this->_to_schema();

        // Build array of changes between the from and to information
        $diff = $this->_buildChanges($fromInfo, $toInfo);

        return $diff;
    }

    protected function _to_schema()
    {
        static $schema = array();
        if ($schema)
        {
            return $schema;
        }

        $schema = $this->_buildModelInformation(array());
        return $schema;
    }

    protected function _diff($from, $to)
    {
        return;
    }

    protected function _buildModelInformation(array $models)
    {
        $files = Arr::flatten(Kohana::list_files('classes/model'));
        ksort($files);
        //print_r(Kohana::debug($files));
        foreach ($files as $file)
        {
            require_once($file);
        }
        $models = Doctrine_Core::getLoadedModels();

        $info = array();
        foreach ($models as $key => $model) {
            $table = Doctrine_Core::getTable($model);
            if ($table->getTableName() !== $this->_migration->getTableName()) {
                $info[$model] = $table->getExportableFormat();
            }
        }

        $info = $this->_cleanModelInformation($info);

        return $info;
    }

    protected function _cleanModelInformation($info)
    {
        return $info;
    }

    protected function _getItemExtension($item)
    {
        return false;
    }

    protected function _generateModels($prefix, $item)
    {
        return false;
    }

    protected function _cleanup()
    {
        // Nothing to do here!
    }

    public function generateMigrationClasses()
    {
        $changeset = parent::generateMigrationClasses();

        $schema = $this->_to_schema();
        /*
         * Clean up the ReflectionClass instances
         */
        foreach ($schema as $key=>$model)
        {
            if (isset($model['options']['declaringClass'])
                && $model['options']['declaringClass'] instanceof ReflectionClass)
            {
                $schema[$key]['options']['declaringClass'] = $model['options']['declaringClass']->getName();
            }
        }

        Doctrine_Parser::dump($schema, 'yml', $this->_from);
    }

    public function migration()
    {
        return $this->_migration;
    }

}