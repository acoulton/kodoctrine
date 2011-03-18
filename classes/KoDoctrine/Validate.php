<?php

defined('SYSPATH') or die('No direct script access.');

/**
 * Array and variable validation, extended to transparently support
 * Doctrine_Null on all relevant validators
 *
 * @package    KoDoctrine
 * @category   Security
 * @author     Andrew Coulton
 * @license    http://kohanaphp.com/license
 */
class KoDoctrine_Validate extends Kohana_Validate {

    /**
     * Checks if a field is not empty.
     * Doctrine_Null is transparently replaced with null
     *
     * @return  boolean
     */
    public static function not_empty($value) {
        if ($value instanceof Doctrine_Null) {
            $value = null;
        }
        return Kohana_Validate::not_empty($value);
    }

}