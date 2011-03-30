<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Shows a db user authentication form to enable the user to authenticate in
 * order to apply database schema changes (the Apache user should never have that
 * much authority).
 */
/* @var $sub_form int If set, then the form is a subform and shouldn't show its own open and submit/close */
/* @var $db_connection Doctrine_Connection */
$sub_form = isset($sub_form) ? $sub_form : false;
?>

<?php
if ( ! $sub_form)
{
    echo Form::open();
}?>
<div class="kodoctrine_db_auth">
    <p>To confirm this action, enter your database credentials here:</p>
    
    <p>Database: <?=$db_connection->getOption('dsn')?>
        <?=Form::hidden('db_dsn',$db_connection->getOption('dsn'));?>
    </p>
    <p><label>Username: <?=Form::input('db_username')?></label></p>
    <p><label>Password: <?=Form::password('db_password')?></label></p>
    <? if ( ! $sub_form)
       {
          echo Form::submit('db_auth', "Execute");
       }?>
</div>

<?php
if ( ! $sub_form)
{
    echo Form::close();
}?>