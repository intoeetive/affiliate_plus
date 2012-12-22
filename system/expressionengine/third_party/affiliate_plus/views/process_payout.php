<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=process_payout_action');?>

<?php 

$this->load->view('tabs'); 

?> 

<?php 
$this->table->set_template($cp_pad_table_template);
foreach ($arr as $key => $val)
{
	$this->table->add_row(lang($key, $key), $val);
}
echo $this->table->generate();
$this->table->clear();
?>



<p><?=form_submit('submit', lang('process_payout'), 'class="submit"')?></p>


<?php
form_close();