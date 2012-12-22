<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=save_rule');?>

<?php 

$this->load->view('tabs'); 

foreach ($data as $key=>$arr)
{

?> 

<div class="editAccordion open<?php if ($arr['show']==false) echo ' collapsed';?>"> 
<h3<?php if ($arr['show']==false) echo ' class="collapsed"';?>><?=lang("$key")?></h3> 
    <div<?php if ($arr['show']==false) echo ' style="display: none;"';?>> 

<?php 
$this->table->set_template($cp_pad_table_template);
foreach ($arr as $key => $val)
{
	if ($key!='show')
	{
		$this->table->add_row(lang($key, $key), $val);
	}
}
echo $this->table->generate();
$this->table->clear();
?>
</div>
</div>
<?php
}
?>


<p><?=form_submit('submit', lang('save'), 'class="submit"')?></p>

<?php if ($this->input->post('id')!=''):?>

<p>&nbsp;</p>

<p><a class="rule_delete_warning" href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=delete_rule'.AMP.'id='.$this->input->post('id')?>"><?=lang('delete_rule')?></a> </p>

<?php endif;?>

<p>&nbsp;</p>

<?php
form_close();