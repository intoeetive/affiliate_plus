<?php 

$this->load->view('tabs'); 

if ($total_count == 0) {
	
	?>
	<div class="tableFooter">
		<p class="notice"><?=lang('no_records')?></p>
		<p><a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=rule_edit'?>"><?=lang('create_rule')?></a></p>
	</div>
<?php 

}
else
{

$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => lang('id'), 'style' => 'width:10%;'),
    array('data' => lang('title'), 'style' => 'width:50%;'),
    array('data' => lang('commission_rate'), 'style' => 'width:10%;'),
    array('data' => lang('priority'), 'style' => 'width:10%;'),
    array('data' => '', 'style' => 'width:10%;'),
    array('data' => '', 'style' => 'width:10%;')
);


foreach ($data as $item)
{
	$this->table->add_row($item['rule_id'], $item['rule_title'] , $item['commission_rate'], $item['rule_priority'], $item['edit']);//, $item['stats']);
}

echo $this->table->generate();


$this->table->clear();

}
?>