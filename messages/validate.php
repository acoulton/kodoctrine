<?php defined('SYSPATH') or die('No direct script access.');

return array(
	'not_empty'    => 'This field must not be empty',
	'matches'      => 'This field must be the same as :param1',
	'regex'        => 'This field does not match the required format',
	'exact_length' => 'This field must be exactly :param1 characters long',
	'min_length'   => 'This field must be at least :param1 characters long',
	'max_length'   => 'This field must be no more than :param1 characters long',
	'in_array'     => 'This field must be one of the available options',
	'digit'        => 'This field can only contain digits with no dots or dashes',
	'decimal'      => 'This field must be a decimal with :param1 places',
	'range'        => 'This field must be within the range of :param1 to :param2',
        'date'         => 'This field must be a date in day/month/year format',
        'email'        => 'This field must be a valid email address',
        'email_domain' => 'This email address is not valid',
        'url'          => 'This field must be a valid web address',
        'ip'           => 'This field must be a valid internet (IP) address',
        'credit_card'  => 'This field must be a valid credit card number',
        'phone'        => 'This field must be a valid phone number',
        'alpha'        => 'This field can only contain alphabetic characters',
        'alpha_numeric'=> 'This field can only contain alphabetic characters and numbers',
        'alpha_dash'   => 'This field can only contain alphabetic, numeric, underscore and dash characters',
        'numeric'      => 'This field must be a number',
        'color'        => 'This field must be a valid HTML colour'
);