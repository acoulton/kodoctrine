<?php

/*
 *  $Id: Record.php 7496 2010-03-30 20:20:37Z jwage $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL.
*/
/**
 * KoDoctrine_Record
 * Record classes should inherit from this customised Record superclass to benefit
 * from improved validation behaviour
 *
 *
 * @package     KoDoctrine
 * @subpackage  Record
 * @author      Andrew Coulton
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @version     $Revision: 7496 $
 */
abstract class KoDoctrine_Record extends Doctrine_Record {

    const COL_TYPE_INTEGER = 'integer';
    const COL_TYPE_DECIMAL = 'decimal';
    const COL_TYPE_STRING = 'string';
    const COL_TYPE_CLOB = 'clob';
    const COL_TYPE_FLOAT = 'float';
    const COL_TYPE_ARRAY = 'array';
    const COL_TYPE_OBJECT = 'object';
    const COL_TYPE_BLOB = 'blob';
    const COL_TYPE_GZIP = 'gzip';
    const COL_TYPE_BOOLEAN = 'boolean';
    const COL_TYPE_DATE = 'date';
    const COL_TYPE_TIME = 'time';
    const COL_TYPE_TIMESTAMP = 'timestamp';
    const COL_TYPE_ENUM = 'enum';

    /**
     * An array of the data as at last validation used to support skipping
     * re-validation if data has not changed
     * @var array
     */
    protected $_validatedData = array();

    /**
     * The validator object used to validate this record
     * @var Validate
     */
    protected $_validator = null;

    /**
     * The name of the validator message file used to humanise messages
     * @var string
     */
    protected $_validationMessageFile = 'validate';

    /**
     * Tests validity of record using current data but replacing core validation
     * behaviour to:
     * - use Kohana validators rather than Doctrine
     * - fully validate all relations if deep rather than stopping at first failure
     *
     * @param boolean $deep   run the validation process on the relations
     * @param boolean $hooks  invoke save hooks before start
     * @return boolean        whether or not this record is valid
     */
    public function isValid($deep = false, $hooks = true) {

        if (!$this->_table->getAttribute(Doctrine_Core::ATTR_VALIDATE)) {
            return true;
        }

        if ($this->_state == self::STATE_LOCKED || $this->_state == self::STATE_TLOCKED) {
            return true;
        }

        /*
         * Check if the data has changed since last validation, and only validate
         * our own data if it has not. We will still go deep in case child data
         * has changed
        */
        //$validateData = $this->exists() ? $this->getModified() : $this->getData();
        $validateData = $this->getData();
        if (count(array_diff_assoc($validateData, $this->_validatedData))) {

            if ($hooks) {
                $this->invokeSaveHooks('pre', 'save');
                $this->invokeSaveHooks('pre', $this->exists() ? 'update' : 'insert');
            }

            // Clear the stack from any previous errors.
            $this->getErrorStack()->clear();

            // Run validation process
            $event = new Doctrine_Event($this, Doctrine_Event::RECORD_VALIDATE);
            $this->preValidate($event);
            $this->getTable()->getRecordListener()->preValidate($event);

            if (!$event->skipOperation) {
                //begin Kohana validation for this record
                //$validateData = $this->exists() ? $this->getModified() : $this->getData();
                $validateData = $this->getData();

                $validator = $this->get_validation($validateData);

                //add the messages to the error stack
                if (!$validator->check()) {
                    //@todo: Consider possibility of supporting multiple messages per field
                    //@todo: Consider whether to store error stackk as native and allow application-level control of message display
                    $errors = $validator->errors($this->_validationMessageFile);
                    //throw new Exception(Kohana::dump($errors) . Kohana::dump($validateData));
                    $stack = $this->getErrorStack();
                    foreach ($errors as $field=>$error) {
                        $stack->add($field, $error);
                    }
                }

                $this->validate();
                if ($this->_state == self::STATE_TDIRTY || $this->_state == self::STATE_TCLEAN) {
                    $this->validateOnInsert();
                } else {
                    $this->validateOnUpdate();
                }
                $this->_validatedData = $validateData;
            }

            $this->getTable()->getRecordListener()->postValidate($event);
            $this->postValidate($event);
        }

        $valid = $this->getErrorStack()->count() == 0 ? true : false;
        if ($deep) {    //run deep whether valid or not
            $stateBeforeLock = $this->_state;
            $this->_state = $this->exists() ? self::STATE_LOCKED : self::STATE_TLOCKED;

            foreach ($this->_references as $reference) {
                if ($reference instanceof Doctrine_Record) {
                    $valid = $reference->isValid($deep) && $valid;
                } else if ($reference instanceof Doctrine_Collection) {
                    foreach ($reference as $record) {
                        $valid = $record->isValid($deep) && $valid;
                    }
                }
            }
            $this->_state = $stateBeforeLock;
        }

        return $valid;
    }

    public function get_validation($data) {

        $validation = Validation::factory($data);
        $validation->bind(':model', $this);

        //@todo: implement a validation object repository for model types and create new via clone?

        $table = $this->getTable();
        foreach ($table->getFieldNames() as $fieldName)
        {
            $meta = $table->getDefinitionOf($fieldName);
            if ( ! $meta) {
                continue;
            }

            //auto create a rule for length
            if (($meta['type']=='string') && isset($meta['length'])) {
                $validation->rule($fieldName, 'max_length', array(':value',$meta['length']));
            }
            //@todo: automatically create validators for type, enum, notnull etc

            }
        return $validation;
    }

    /**
     * Internal factory method that supports creating a new record, loading existing,
     * and valdiating whether the user is trying to create a new record.
     * @throws InvalidArgumentException If the record does not exist, or null is passed and $allow_create is false
     * @param string $class
     * @param int $id
     * @param boolean $allow_create
     * @param Doctrine_Query $query
     * @return KoDoctrine_Record
     */
    protected static function _internal_factory($class, $id = null, $allow_create = true, $query = null)
    {
        if ($id === null)
        {
            if ($allow_create)
            {
                return new $class;
            }
            else
            {
                throw new InvalidArgumentException("No ID passed to factory for $class");
            }
        }

        if ($query == null)
        {
            $query = Doctrine_Query::create();
        }

        $result = $query->from($class . " m")
                        ->where('id = ?', (int) $id)
                        ->fetchOne();

        if ( ! $result)
        {
            throw new InvalidArgumentException("$id was not a valid $class ID");
        }
        return $result;
    }

    /**
     * Allow modules to add relations without having to insert cascading classes
     */
    public function relations_from_config()
    {
        $config = Kohana::$config->load('model');

        // Build an array of inheritance classes
        $inheritances = array();
        $class = new ReflectionClass($this);
        while ($class)
        {
            $name = $class->getName();
            $inheritances[] = $name;
            $class = ($name == 'KoDoctrine_Record') ? null : $class->getParentClass();
        }
        array_reverse($inheritances);

        // Load the relations from the configuration
        $relations = array();
        foreach ($inheritances as $class_name)
        {
            $relations = Arr::merge($relations, Arr::path($config, $class_name . ".relations",array()));
        }

        // Apply the relations
        foreach ($relations as $relation => $config)
        {
            $method = 'has' . $config['type'];
            $this->$method($config['model'].' as '.$relation, $config['options']);
        }
    }
}