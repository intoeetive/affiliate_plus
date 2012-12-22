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

$this->table->set_template($cp_pad_table_template);
$this->table->set_heading($table_headings);


foreach ($data as $item)
{
	$this->table->add_row($item['date'], $item['member'] , $item['amount'], $item['status'], $item['link']);
}

echo $this->table->generate();


$this->table->clear();

}
?>