<?php defined('SYSPATH') or die('No direct script access.');
/* @var $migration Doctrine_Migration */?>
<h2>Build Database Migrations</h2>
<p>Your version: <?=$migration->getCurrentVersion();?></p>
<p>Latest version: <?=$migration->getLatestVersion();?></p>
<?php foreach ($changes as $schema=>$change_types):?>
<h3><?=$schema?></h3>
<table>
    <?php foreach ($change_types as $type=>$details):
        if (count($details)):
                switch ($type) {
                    case 'created_tables':
                    case 'created_foreign_keys':
                    case 'created_columns':
                        $rowclass='created';
                        break;
                    case 'changed_columns':
                        $rowclass='changed';
                        break;
                    default:
                        $rowclass='dropped';
                }
                $rowspan=count($details);
            else:
                $rowclass='inactive';
                $rowspan=1;
            endif;?>
            <tr class="<?=$rowclass?>">
                <td rowspan="<?=$rowspan?>"><?=$type?></td>
                <?php foreach ($details as $data):
                    switch ($type)
                {
                    case 'created_tables':
                    case 'dropped_tables':
                        $key = $data['tableName'];
                        break;
                    default:
                        $key = key($data);
                }?>
                <td><?=$key?></td>
                </tr><tr>
                <?php endforeach;?>
            </tr>
    <?php endforeach;?>
</table>
<?php endforeach;?>
<?php if ($preview):?>
    <?=Form::open()?>
    <p><?=Form::submit('build_migrations', 'Build migrations')?></p>
    <?=Form::close()?>
<?php endif;?>