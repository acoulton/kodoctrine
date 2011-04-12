<?php
/**
 * Configuration file for the doctrine module, which includes key Doctrine config
 * settings and application specific parameters.
 */
return array(
  /**
   * connections is an array of connections to create, keyed on connection name
   */
  'connections' => array(
      'default_connection' => 'mysql://root:pwd@localhost/db'),
  /**
   * attributes is an array of doctrine attributes to set on the connection.
   * This works even though the array is numeric indexed due to the lovely
   * behaviour of Kohana Arr::is_assoc which compares the keys of the array
   * with the keys of the keys
   */
  'attributes' => array(
      Doctrine::ATTR_MODEL_LOADING => Doctrine::MODEL_LOADING_PEAR,
      Doctrine::ATTR_VALIDATE => Doctrine::VALIDATE_ALL,
      Doctrine_Core::ATTR_USE_NATIVE_ENUM => true,
      Doctrine::ATTR_DEFAULT_IDENTIFIER_OPTIONS =>
            array('name' => 'id', 'type' => 'integer', 'length' => 11),
      Doctrine::ATTR_PORTABILITY => Doctrine::PORTABILITY_ALL,
      Doctrine::ATTR_QUOTE_IDENTIFIER => true,
      Doctrine::ATTR_EXPORT => Doctrine::EXPORT_ALL,
      Doctrine::ATTR_TBLNAME_FORMAT => '%s',
      Doctrine::ATTR_MODEL_CLASS_PREFIX => 'Model_',
      Doctrine::ATTR_AUTOLOAD_TABLE_CLASSES => true),
  /**
   * adminroute is a site-specific route to the doctrine controller for (very basic)
   * security. This should be changed from site to site.
   */
  'adminRoute' => 'dc1234',
  'migration_classes' => APPPATH . 'schema\migrations',
  'migration_schema' => APPPATH . 'schema\current_schema.php',
  'builderOptions' => array(
      'baseClassPrefix'=>'Base_',
      'baseClassesDirectory'=>'',
      'baseClassName'=>'KoDoctrine_Record',
      'pearStyle'=>'true',
      'classPrefix'=>'Model_',
      'classPrefixFiles'=>false,
      'generateTableClasses'=>true,
      'phpDocPackage' => 'CustomiseDoctrineConfig',
      'phpDocSubpackage' => '',
      'phpDocName' => 'Author',
      'phpDocEmail' => 'AuthorMail'),
   'defaultDataFixtures' => array(),

  );