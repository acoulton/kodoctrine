<?php
defined('SYSPATH') or die('No direct script access.');

/**
 * Extends the core Kohana debug class to limit the recursion within KODoctrine
 * objects - which are huge and can prevent effective rendering of the stack trace
 * in the development mode exception handler.
 */
class Debug extends Kohana_Debug
{
	protected static function _dump( & $var, $length = 128, $limit = 10, $level = 0)
	{
		// Only recurse three levels within a Doctrine_Record, Collection or Table
		if ($var instanceof Doctrine_Record
			OR $var instanceof Doctrine_Collection
			OR $var instanceof Doctrine_Table)
		{
			return parent::_dump($var, $length, 3, $level);
		}

		return parent::_dump($var, $length, $limit, $level);
	}
}
