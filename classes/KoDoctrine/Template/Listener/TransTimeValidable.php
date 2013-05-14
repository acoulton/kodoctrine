<?php
/**
 * Listener for TransTimeValidable behaviour which implements Transaction Time Validity
 * by replacing all update and delete queries with queries modifying the TransValidFrom and
 * TransValidTo values for the underlying table, and automatically including these values
 * in queries.
 * 
 * @package     Doctrine
 * @subpackage  KohanaDoctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link
 * @since       1.0
 * @version     $Revision$
 * @author      Andrew Coulton
 */
class KoDoctrine_Template_Listener_TransTimeValidable extends Doctrine_Record_Listener
{
    /**
     * Array of TransTimeValidable options
     *
     * @var string
     */
    protected $_options = array();

    /**
     * __construct
     *
     * @param string $options 
     * @return void
     */
    public function __construct(array $options)
    {
        $this->_options = $options;
    }

    /**
     * Stores the Transaction Time - which is implemented as a singleton per
     * execution
     * @var float
     */
    public static $TransactionTime = null;

    /**
     * Returns the value of the Transaction Time - which is implemented as a
     * singleton per execution
     * @return float
     */
    public function getTransactionTime() {
        //@todo: Implement a method to set a new TransactionTime for batches of operations (eg linked to a DB transaction?) for long running executions
        if (self::$TransactionTime == null) {
            self::$TransactionTime = round(microtime(true),$this->_options['options']['scale']);
        }
        return self::$TransactionTime;
    }

    /**
     * Returns the maximum value for the timestamp field
     * @return int
     */
    public function getEndOfTime() {
        return $this->_options['endOfTime'];
    }

    /**
     * Skip the normal delete options so we can override it with our own
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function preDelete(Doctrine_Event $event)
    {
        $name = $this->_options['toname'];
        $invoker = $event->getInvoker();

        //set the ttvEnd column value
        $invoker->$name = $this->getTransactionTime();
        //skip the delete operation
        $event->skipOperation();

        //@todo: Implement a hardDelete option for archival?
    }

    /**
     * In the postDelete() event, we save the modified row
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function postDelete(Doctrine_Event $event)
    {
        $event->getInvoker()->save();        
    }

    /**
     * Implement preDqlDelete() hook and modify a dql delete query so it updates
     * the TransValidTo column instead of deleting the record
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function preDqlDelete(Doctrine_Event $event)
    {
        $params = $event->getParams();
        $field = $params['alias'] . '.' . $this->_options['toname'];
        $query = $event->getQuery();
        if ( ! $query->contains($field)) {
            $query->from('')->update($params['component']['table']->getOption('name') . ' ' . $params['alias']);
            //set the value
            $query->set($field, '?', $this->getTransactionTime());
            //@todo: SoftDelete only sets the value if it's currently null. Should we be restricting only if = endOfTime?
            //$query->addWhere($field . ' IS NULL');
        }
    }

    /**
     * Implement the preUpdate hook and convert an update into an update of the
     * transaction time validity columns of the existing row and an insert of the
     * new row.
     * @param Doctrine_Event $event
     */
    public function preUpdate(Doctrine_Event $event)
    {
        $toField = $this->_options['toname'];
        $invoker = $event->getInvoker();
        $invokerClass = get_class($invoker);

        /*
         * Insert a new row with the new data and new startTTV
         */
        /* @var $newEntry Doctrine_Record */
        $newEntry = new $invokerClass();
        $newEntry->merge($invoker->toArray(),false);
        $newEntry->save();

        /*
         * Update the old row to have the endTTV value of the transaction
         */
        $invoker->state(Doctrine_Record::STATE_CLEAN);
        $invoker->$toField = $this->getTransactionTime();
    }

    /**
     * Implement the preInsert hook and set the transaction time validity columns
     * @param Doctrine_Event $event
     */
    public function preInsert(Doctrine_Event $event)
    {
        $fromname = $this->_options['fromname'];
        $toname = $this->_options['toname'];
        $invoker = $event->getInvoker();

        if ($invoker->$toname == null) {
            $invoker->$toname = $this->getEndOfTime();
        }

        if ($invoker->$fromname == null) {
            $invoker->$fromname = $this->getTransactionTime();
        }
    }

    /**
     * Implement preDqlSelect() and do two things:
     *  - rewrite the ValidAt condition to a TransValidFrom <= ValidAt < TransValidTo condition
     *  - add the TransValidFrom and TransValidTo to all queries
     *
     * @param Doctrine_Event $event 
     * @return void
     */
    public function preDqlSelect(Doctrine_Event $event)
    {
        $params = $event->getParams();
        $fromField = $params['alias'] . '.' . $this->_options['fromname'];
        $toField = $params['alias'] . '.' . $this->_options['toname'];
        $transTime = $this->getTransactionTime();

        $query = $event->getQuery();

        $query->getDqlPart($queryPart);

        // We only need to add the restriction if:
        // 1 - We are in the root query
        // 2 - We are in the subquery and it defines the component with that alias
        if (( ! $query->isSubquery() || ($query->isSubquery() && $query->contains(' ' . $params['alias'] . ' ')))) {
            //we should be selecting rows where from <= TransactionTime < To
            if ( ! $query->contains($fromField)) {
                $query->addPendingJoinCondition($params['alias'], $fromField . ' <= ' . $transTime);
            }
            if ( ! $query->contains($toField)) {
                $query->addPendingJoinCondition($params['alias'], $toField . ' > ' . $transTime);
            }
        }
    }
}