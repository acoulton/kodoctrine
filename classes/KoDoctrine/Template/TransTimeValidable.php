<?php
/**
 * Listener for SoftDelete behavior which will allow you to turn on the behavior which
 * sets a delete flag instead of actually deleting the record and all queries automatically
 * include a check for the deleted flag to exclude deleted records.
 *
 * @package     Doctrine
 * @subpackage  KohanaDoctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link
 * @since       1.0
 * @version     $Revision$
 * @author      Andrew Coulton
 */
class KoDoctrine_Template_TransTimeValidable extends Doctrine_Template
{
    /**
     * Array of TransTimeValidable options
     *
     * @var string
     */
    protected $_options = array(

        'fromname'          =>  'ttvstart',
        'toname'            =>  'ttvend',
        'type'          =>  'decimal',
        'length'        =>  14,
        'options'       =>  array(
            'scale' => 4,
            'notnull' => true,
            'default' => null,
            'primary' => true
            ),
        'endOfTime' => 2147483647.9999
    );

    protected $_listener;

    /**
     * Set table definition for SoftDelete behavior
     *
     * @return void
     */
    public function setTableDefinition()
    {

        $this->hasColumn($this->_options['fromname'], $this->_options['type'], $this->_options['length'], $this->_options['options']);
        $this->hasColumn($this->_options['toname'], $this->_options['type'], $this->_options['length'], $this->_options['options']);

        $this->check($this->_options['fromname'] . ' < ' . $this->_options['toname']);
        $this->_listener = new KoDoctrine_Template_Listener_TransTimeValidable($this->_options);
        $this->addListener($this->_listener);

    }

}
?>
