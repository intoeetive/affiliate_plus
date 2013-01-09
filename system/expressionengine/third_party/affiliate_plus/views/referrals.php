<?php 

$this->load->view('tabs'); 

if ($total_count == 0) {
	
	?>
	<div class="tableFooter">
		<p class="notice"><?=lang('no_records')?></p>
	</div>
<?php 

}
else
{

?>

<div id="filterMenu">
	<fieldset>
		<legend><?=lang('refine_results')?></legend>

	<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=referrals');?>

		<div class="group">
            <?php
            
			$field = array(
              'name'        => 'search',
              'value'       => $selected['search'],
              'size'        => '25',
              'id'          => 'search',
              'style'       => 'width:240px'
            );
			
			echo lang('name_contains').NBS.NBS.form_input($field).NBS.NBS;
			
			$perpage = array(
				'25' => '25',
				'50' => '50',
				'100' => '100',
				'0' => lang('all')
			);

			echo form_dropdown('perpage', $perpage, $selected['perpage']).NBS.lang('records').NBS.lang('per_page');
            
            echo NBS.NBS.form_submit('submit', lang('show'), 'class="submit" id="search_button"');
            
            ?>
		</div>

	<?=form_close()?>
	</fieldset>
</div>


<?php

$this->table->set_template($cp_pad_table_template);
$this->table->set_heading($table_headings);


foreach ($data as $item)
{
	//var_dump($item);
	$this->table->add_row($item['affiliate'] , $item['referral'], $item['date']);
}

echo $this->table->generate();


$this->table->clear();
?>
<span class="pagination"><?=$pagination?></span>
<?php
}
?>