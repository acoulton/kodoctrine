<?php
defined('SYSPATH') or die('No direct script access.');

class KoDoctrine_Migration_Diff extends Doctrine_Migration_Diff
{

    public function __construct()
    {
        // We always go from a schema state file
        // To all the current models
        // So there's nothing to do here!
        $config = Kohana::$config->load('doctrine');
        $this->_from = $config->migration_schema;
        $path = $config->migration_classes;
        if ( ! is_dir($path))
        {
            mkdir($path, 0777, true);
        }
        $this->_migration = new Doctrine_Migration($path);
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
        $models = Doctrine_Core::initializeModels(Doctrine_Core::getLoadedModels());

        $info = array();
        foreach ($models as $key => $model) {
            $table = Doctrine_Core::getTable($model);
            if ($table->getTableName() === $this->_migration->getTableName()) {
                continue;
            }

            /*
             * Departure from the Doctrine1.2 code, to support model inheritance
             * properly.
             */
            $model_schema = $table->getExportableFormat();
            $table_name = $model_schema['tableName'];
            if ( ! isset($info[$table_name]))
            {
                $info[$table_name] = $model_schema;
                $info[$table_name]['models'][] = $model;
                continue;
            }

            // The table already exists, so merge in data from this model
            $table_schema =& $info[$table_name];
            $table_schema['models'][] = $model;

            foreach ($model_schema['columns'] as $name=>$options)
            {
                // The column doesn't yet exist in the table schema
                if ( ! isset($table_schema['columns'][$name]))
                {
                    $table_schema['columns'][$name] = $options;
                    continue;
                }

                // The column is equal in both
                if (Arr::flatten($table_schema['columns'][$name])
                        == Arr::flatten($options))
                {
                    continue;
                }

                // The column is different in the two models
                $table_column =& $table_schema['columns'][$name];
                if ($table_column['type'] != $options['type'])
                {
                    throw new Kohana_Exception('Type definition of column :column in table :table
                        differs between :model (:model_type) and :defined_models (:defined_type)',
                            array(':column'=>$name,
                                  ':table'=>$table_name,
                                  ':model'=>$model_name,
                                  ':model_type'=>$options['type'],
                                  ':defined_models'=>implode(", ",$table_schema['models']),
                                  ':defined_type' => $table_column['type']));
                }

                // If length is different, take the biggest
                if ($table_column['length'] < $options['length'])
                {
                    $table_column['length'] = $options['length'];
                }

                // Merge ENUM values
                if (isset($table_column['values']) OR isset($options['values']))
                {
                    $table_column['values'] = Arr::merge(
                            Arr::get($table_column,'values',array()),
                            Arr::get($options,'values',array()));
                }

                // Default
                // Autoincrement?
                // Primary?
            }

            // Keys
            // Indexes
            // type
            // charset
            // collate

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
