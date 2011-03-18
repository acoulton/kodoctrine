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
 * KoDoctrine_Template_Encryptable
 *
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
class KoDoctrine_Template_Encryptable extends Doctrine_Template
{
    /**
     * Array of Encryptable options
     *  - secureFields is an array of field names, with field by field options:
     *      - obscurePattern
     *      - obscureChar
     *      - cryptColumnName
     *      - cryptColumnType
     *      - cryptColumnLength
     *      - preValidate is an array of validators to run before encryption
     * @var string
     */
    protected $_options = array(
        'secureFields'  =>  array(),
        'publicKey'     =>  null,
        'privateKey'    =>  null
    );

    protected $_fieldOptions = array(
        'obscurePattern' => '/^.+(?=.{4}$)/s',
        'obscureChar'    => '*',
        'cryptColumnName'=> 'crypt_%s',
        'cryptColumnType'=> 'blob',
        'cryptColumnLength'=> 128,
        'preValidate'   => array()
    );

    /**
     * Prepare default options by merging in the fieldOptions for each field
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        if (array_key_exists('secureFields', $options)) {
            foreach ($options['secureFields'] as $key=>$data) {
                $this->_options['secureFields'][$key] = $this->_fieldOptions;
            }
        }
        parent::__construct($options);
    }

    /**
     * Set table definition for Encryptable behavior
     *
     * @return void
     */
    public function setTableDefinition()
    {
        //we need to add a column for each of the crypt fields
        foreach ($this->_options['secureFields'] as $fieldName => $fieldOptions) {
            $this->_options[$fieldName]['cryptColumnName'] = sprintf($fieldOptions['cryptColumnName'] , $fieldName);
            $this->hasColumn($this->_options[$fieldName]['cryptColumnName'],
                             $fieldOptions['cryptColumnType'],
                             $fieldOptions['cryptColumnLength']);

            //@todo: set the obscurePattern to be appropriate to the length of the field
            //@todo: verify that the data column is not longer than the maximum allowed by the encryption method and key
            //@todo: auto-fill the preValidate based on the field validation rules and remove from the field
        }

        //add the listener
        $this->addListener(new KoDoctrine_Template_Listener_Encryptable($this->_options));
    }

    /**
     * Decrypts the record in-memory, loading the information back into the plain
     * text columns for display.
     * @todo: Implement a way of passing a certificate/passphrase/both to this routine that doesn't involve security credentials held in memory
     */
    public function decryptRecord($priv_key) {
        $record = $this->getInvoker();
        $table = $record->getTable();
        //@todo: keys should be provided by the application, and should be configurable

        foreach ($this->_options['secureFields'] as $fieldName => $options) {
            $clearField = $table->getFieldName($fieldName);
            $cipherField = $table->getFieldName(sprintf($options['cryptColumnName'] , $fieldName));

            openssl_private_decrypt($record->$cipherField, $plainText, $priv_key);
            $record->$clearField = $plainText;
        }
    }
}