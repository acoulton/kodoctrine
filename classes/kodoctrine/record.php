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

                //@todo: Consider how to temporarily remove validation rules to allow validation of just modified data

                $validator = $this->getValidator();
                $validator->exchangeArray($validateData);

                //add the messages to the error stack
                //@todo: Check whether this record class has any validation rules configured. If not it automatically passes this stage
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

    public function getValidator() {
        if ( ! $this->_validator) {
            $this->_validator = new Validate(array());
        }
        //@todo: implement a validation object repository for model types and create new via clone?
        $table = $this->getTable();
        foreach ($table->getFieldNames() as $fieldName)
        {
            $meta = $table->getDefinitionOf($fieldName);
            if ( ! $meta) {
                print_r('*'.$fieldName);
                continue;
            }

            $rules = array();

            //auto create a rule for length
            if (($meta['type']=='string') && isset($meta['length'])) {
                $rules['max_length'] = array($meta['length']);
            }
            //@todo: automatically create validators for type, length, enum etc
            
            //override auto-rules with any specifics
            if (isset($meta['validation'])) {
                //implement the rules
                $rules = Arr::merge($rules, $meta['validation']['rules']);
            }            
            //set the validation rules
            $this->_validator->rules($fieldName, $rules);            
            //@todo: support callbacks, filters, etc            
        }
        return $this->_validator;
    }
}