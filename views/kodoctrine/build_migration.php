<?php defined('SYSPATH') or die('No direct script access.');
/* @var $migration Doctrine_Migration */?>
<h2>Build Database Migrations</h2>
<p>Your version: <?=$migration->getCurrentVersion();?></p>
<p>Latest version: <?=$migration->getLatestVersion();?></p>
<table>
    <tbody class='dropped'>
        <tr><th colspan="5">Dropped Tables</th></tr>
        <?php
            $details = Arr::get($changes, 'dropped_tables', array());
            foreach ($details as $table=>$columns):
                $first = true;
                foreach ($columns as $name=>$details):?>
                <tr>
                    <?php if ($first):?>
                        <td rowspan ="<?=count($columns);?>"><?=$table;?></td>
                    <?php $first = false; endif;?>
                        <td><?=$name?></td>
                        <td><?=Arr::get($details,'type','MISSING')?></td>
                        <td><?=Arr::get($details,'length','MISSING')?></td>
                        <td></td>
                </tr>
          <?php endforeach;
            endforeach;?>
        <tr><th colspan="5">Dropped Columns</th></tr>
        <?php
            $details = Arr::get($changes, 'dropped_columns', array());
            foreach ($details as $table=>$columns):
                $first = true;
                foreach ($columns as $name=>$details):?>
                <tr>
                    <?php if ($first):?>
                        <td rowspan ="<?=count($columns);?>"><?=$table;?></td>
                    <?php $first = false; endif;?>
                        <td><?=$name?></td>
                        <td><?=Arr::get($details,'type','MISSING')?></td>
                        <td><?=Arr::get($details,'length')?></td>
                        <td></td>
                </tr>
          <?php endforeach;
            endforeach;?>
        <tr><th colspan="5">Dropped Foreign Keys</th></tr>
        <?php
            $details = Arr::get($changes, 'dropped_foreign_keys', array());
            foreach ($details as $table=>$columns):
                $first = true;
                foreach ($columns as $name=>$details):?>
                <tr>
                    <?php if ($first):?>
                        <td rowspan ="<?=count($columns);?>"><?=$table;?></td>
                    <?php $first = false; endif;?>
                        <td><?=$name?></td>
                        <td><?=Arr::get($details,'type','MISSING')?></td>
                        <td><?=Arr::get($details,'length')?></td>
                        <td></td>
                </tr>
          <?php endforeach;
            endforeach;?>
        <tr><th colspan="5">Dropped Indexes</th></tr>
        <?php
            $details = Arr::get($changes, 'dropped_indexes', array());
            foreach ($details as $table=>$columns):
                $first = true;
                foreach ($columns as $name=>$details):?>
                <tr>
                    <?php if ($first):?>
                        <td rowspan ="<?=count($columns);?>"><?=$table;?></td>
                    <?php $first = false; endif;?>
                        <td><?=$name?></td>
                        <td><?=Arr::get($details,'type','MISSING')?></td>
                        <td><?=Arr::get($details,'length')?></td>
                        <td></td>
                </tr>
          <?php endforeach;
            endforeach;?>
    </tbody>
    <tbody class='changed'>
        <tr><th colspan="5">Changed Columns</th></tr>
        <?php
            $details = Arr::get($changes, 'changed_columns', array());
            foreach ($details as $table=>$columns):
                $first = true;
                foreach ($columns as $name=>$details):?>
                <tr>
                    <?php if ($first):?>
                        <td rowspan ="<?=count($columns);?>"><?=$table;?></td>
                    <?php $first = false; endif;?>
                        <td><?=$name?></td>
                        <td><?=Arr::get($details,'type','MISSING')?></td>
                        <td><?=Arr::get($details,'length')?></td>
                        <td></td>
                </tr>
          <?php endforeach;
            endforeach;?>
    </tbody>
    <tbody class='created'>
       <tr><th colspan="5">Created Tables</th></tr>
        <?php
            $details = Arr::get($changes, 'created_tables', array());
            foreach ($details as $table=>$data):
                $first = true;
                $columns = $data['columns'];
                foreach ($columns as $name=>$details):?>
                <tr>
                    <?php if ($first):?>
                        <td rowspan ="<?=count($columns)+1;?>"><?=$table;?></td>
                    <?php $first = false; endif;?>
                        <td><?=$name?></td>
                        <td><?=Arr::get($details,'type','MISSING')?></td>
                        <td><?=Arr::get($details,'length')?></td>
                        <td></td>
                </tr>
          <?php endforeach;?>
                <tr><td colspan="4"><?=Kohana::debug($data['options']);?></td></tr>
          <?php endforeach;?>
        <tr><th colspan="5">Created Columns</th></tr>
        <?php
            $details = Arr::get($changes, 'created_columns', array());
            foreach ($details as $table=>$columns):
                $first = true;
                foreach ($columns as $name=>$details):?>
                <tr>
                    <?php if ($first):?>
                        <td rowspan ="<?=count($columns);?>"><?=$table;?></td>
                    <?php $first = false; endif;?>
                        <td><?=$name?></td>
                        <td><?=Arr::get($details,'type','MISSING')?></td>
                        <td><?=Arr::get($details,'length')?></td>
                        <td></td>
                </tr>
          <?php endforeach;
            endforeach;?>
        <tr><th colspan="5">Created Foreign Keys</th></tr>
        <?php
            $details = Arr::get($changes, 'created_foreign_keys', array());
            foreach ($details as $table=>$columns):
                $first = true;
                foreach ($columns as $name=>$details):?>
                <tr>
                    <?php if ($first):?>
                        <td rowspan ="<?=count($columns);?>"><?=$table;?></td>
                    <?php $first = false; endif;?>
                        <td><?=$name?></td>
                        <td><?=$details['local']?></td>
                        <td><?=$details['foreign']?></td>
                        <td><?=$details['foreignTable']?></td>
                </tr>
          <?php endforeach;
            endforeach;?>
        <tr><th colspan="5">Created Indexes</th></tr>
        <?php
            $details = Arr::get($changes, 'created_indexes', array());
            foreach ($details as $table=>$columns):
                $first = true;
                foreach ($columns as $name=>$details):?>
                <tr>
                    <?php if ($first):?>
                        <td rowspan ="<?=count($columns);?>"><?=$table;?></td>
                    <?php $first = false; endif;?>
                        <td><?=$name?></td>
                        <td colspan="3"><?=Kohana::debug($details['fields'])?></td>
                </tr>
          <?php endforeach;
            endforeach;?>
    </tbody>
</table>
<?php if ($preview):?>
    <?=Form::open()?>
    <p><?=Form::submit('build_migrations', 'Build migrations')?></p>
    <?=Form::close()?>
<?php endif;?>