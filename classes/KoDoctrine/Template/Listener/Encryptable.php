<?php
/*
 *  $Id$
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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

/**
 * Automatically maintain application-level certificate based encryption for
 * sensitive data
 *
 * @package     KoDoctrine
 * @subpackage  Template
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @version     $Revision$
 * @author      Andrew Coulton
 */
class KoDoctrine_Template_Listener_Encryptable extends Doctrine_Record_Listener
{
    /**
     * Array of encryptable options
     *
     * @var string
     */
    protected $_options = array();

    /**
     * __construct
     *
     * @param string $array
     * @return void
     */
    public function __construct(array $options)
    {
        $this->_options = $options;
    }

    /**
     * Set the encrypted values automatically when a record is inserted
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function preInsert(Doctrine_Event $event)
    {
        $record = $event->getInvoker();
        $table = $record->getTable();

        foreach ($this->_options['secureFields'] as $fieldName => $options) {
            $clearField = $table->getFieldName($fieldName);
            $cipherField = $table->getFieldName(sprintf($options['cryptColumnName'] , $fieldName));
            
            $this->encryptField($record, $clearField, $cipherField, $options);
        }
    }

    /**
     * Set the encrypted values automatically when a record is updated if the
     * cleartext value has changed
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function preUpdate(Doctrine_Event $event)
    {
        $record = $event->getInvoker();
        $table = $record->getTable();
        $modified = $record->getModified();

        foreach ($this->_options['secureFields'] as $fieldName => $options) {
            $plainField = $table->getFieldName($fieldName);
            if (array_key_exists($plainField, $modified)) {
                $clearField = $table->getFieldName($fieldName);
                $cipherField = $table->getFieldName(sprintf($options['cryptColumnName'] , $fieldName));
                $this->encryptField($record, $clearField, $cipherField, $options);
            }
        }
    }

    /**
     * Encrypts the value of the cleartext field, stores in the ciphertext field
     * and then obscures the plaintext
     * @param Doctrine_Record $record The record
     * @param string $clearField The field name for the cleartext
     * @param string $cipherField The field name for the ciphertext
     * @param array $options The options for the column
     */
    public function encryptField($record, $clearField, $cipherField, $options) {
        //@FUTURE: Implement a method to transparently remove and replace the validation rules for these fields and run them here
        //run the preValidators
        $preValidation = Arr::get($options,'preValidate');
        if ($preValidation) {
            $validator = new Validate(array($clearField=>$record->$clearField));
            $validator->rules($clearField, Arr::get($preValidation,'rules',array()));
            if ( ! $validator->check()) {

                //we set a flag on the record which is converted into a validation error in postValidate
                if ($record->hasMappedValue('Encryptable_Errors')) {
                    $errors=$record->Encryptable_Errors;
                } else {
                    $errors=array();
                }
                //@todo: Should be a configurable place to select a different validation message file
                foreach ($validator->errors('validate') as $field=>$error) {                    
                    $errors[$field] = $error;
                }
                $record->mapValue('Encryptable_Errors',$errors);                
                return false;
            }
        }
        
        //encrypt the data
        //@todo: Provide a configuration option for the key files somewhere
        $fp = fopen(APPPATH."keys\public_key.pem",'r');
        $pub_key = fread($fp,8192);
        fclose($fp);
        openssl_public_encrypt($record->$clearField, $cipherText, $pub_key);
        $record->$cipherField = $cipherText;

        //now obscure the plaintext
        if ($record->$clearField != null) {
            preg_match($options['obscurePattern'], $record->$clearField,$matches);
            $replace = str_repeat($options['obscureChar'], strlen($matches[0]));
            $record->$clearField = preg_replace($options['obscurePattern'],
                                    $replace,
                                    $record->$clearField);
        }
        //@todo: should there be an exception if the clearField is not obscured for some reason
        //@FUTURE: We ought to reinstate the plaintext if the record saving fails to allow user to resubmit card details - or is this controller level?
    }

    public function postValidate(Doctrine_Event $event){
        $record = $event->getInvoker();
        if ($record->hasMappedValue('Encryptable_Errors')) {
            $stack = $record->getErrorStack();
            foreach ($record->Encryptable_Errors as $field=>$error) {
                $stack->add($field,$error);
            }
            $record->Encryptable_Errors = array();
        }       
    }

}