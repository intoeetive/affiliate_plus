<?php

/*
=====================================================
 Affiliate Plus
-----------------------------------------------------
 http://www.intoeetive.com/
-----------------------------------------------------
 Copyright (c) 2012 Yuri Salimovskiy
=====================================================
 This software is intended for usage with
 ExpressionEngine CMS, version 2.0 or higher
=====================================================
 File: ext.affiliate_plus.php
-----------------------------------------------------
 Purpose: Referrals system that works well
=====================================================
*/

if ( ! defined('BASEPATH'))
{
	exit('Invalid file request');
}

require_once PATH_THIRD.'affiliate_plus/config.php';

class Affiliate_plus_mcp {

    var $version = AFFILIATE_PLUS_ADDON_VERSION;
    
    var $settings = array();
    
    var $perpage = 25;
    
    var $multiselect_fetch_limit = 50;
    
    function __construct() { 
        // Make a local reference to the ExpressionEngine super object 
        $this->EE =& get_instance(); 
        
        $this->EE->load->library('affiliate_plus_lib');
        
        $this->EE->cp->set_variable('cp_page_title', lang('affiliate_plus_module_name'));
    } 
    
    
    //global settings: e-commerce solution
    //groups
    //member-to-group assignment tool
    //per-product settings (rate and requirement to purchase)
    //stats
    
    function index()
    {
        $ext_settings = $this->EE->affiliate_plus_lib->_get_ext_settings();
        if (empty($ext_settings))
        {
        	$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=settings');
			return;
        }
		
		$priorities = array(
			'0'	=> 'Lowest',
			'1'	=> 'Lower',
			'2'	=> 'Medium',
			'3'	=> 'Higher',
			'4'	=> 'Highest'
		);
		
		$this->EE->load->library('table');  
      
    	$vars = array();
        
        $query = $this->EE->db->select('rule_id, rule_title, commission_type, commission_rate, rule_priority')
				->from('affiliate_rules')
				->get();
				
		$vars['total_count'] = $query->num_rows();
				
		$i = 0;
        foreach ($query->result_array() as $row)
        {
           $vars['data'][$i]['rule_id'] = $row['rule_id'];
           $vars['data'][$i]['rule_title'] = "<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=rule_edit'.AMP.'id='.$row['rule_id']."\" title=\"".$this->EE->lang->line('edit')."\">".$row['rule_title']."</a>";
           $vars['data'][$i]['commission_rate'] = $row['commission_rate'].NBS; 
           $vars['data'][$i]['commission_rate'] .= ($row['commission_type']=='percent')?'%':'$';
           $vars['data'][$i]['rule_priority'] = $priorities[$row['rule_priority']];    
           $vars['data'][$i]['edit'] = "<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=rule_edit'.AMP.'id='.$row['rule_id']."\" title=\"".$this->EE->lang->line('edit')."\"><img src=\"".$this->EE->cp->cp_theme_url."images/icon-edit.png\" alt=\"".$this->EE->lang->line('edit')."\"></a>";
           $vars['data'][$i]['stats'] = "";//"<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=stats'.AMP.'stats_type=rule'.AMP.'id='.$row['rule_id']."\" title=\"".$this->EE->lang->line('view_stats')."\"><img src=\"".$this->EE->config->slash_item('theme_folder_url')."third_party/affiliate_plus/stats.png\" alt=\"".$this->EE->lang->line('view_stats')."\"></a>";
           
           $i++;
 			
        }
     
        $this->EE->cp->set_right_nav(array(
		            'create_rule' => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=rule_edit')
		        );
        
    	return $this->EE->load->view('rules', $vars, TRUE);
	
    }   
    
    
    
    function rule_edit()
    {
    	$ext_settings = $this->EE->affiliate_plus_lib->_get_ext_settings();
		
		$this->EE->load->helper('form');
    	$this->EE->load->library('table');  
    	
    	$yesno = array(
                                    'y' => $this->EE->lang->line('yes'),
                                    'n' => $this->EE->lang->line('no')
     	);
    	
    	$js = '';
    	
		$theme_folder_url = trim($this->EE->config->item('theme_folder_url'), '/').'/third_party/affiliate_plus/';
        $this->EE->cp->add_to_foot('<link type="text/css" href="'.$theme_folder_url.'multiselect/ui.multiselect.css" rel="stylesheet" />');
        $this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$theme_folder_url.'multiselect/plugins/localisation/jquery.localisation-min.js"></script>');
        $this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$theme_folder_url.'multiselect/plugins/blockUI/jquery.blockUI.js"></script>');
        $this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$theme_folder_url.'multiselect/ui.multiselect.js"></script>');

       	$values = array(
    		'rule_id'			=> false,
			'rule_title'		=> '',	
			'rule_type'			=> 'open',
			
			'rule_terminator'	=> 'n',
			
			'rule_participant_members'	=> array(),
			'rule_participant_member_groups'=> array(),
			'rule_participant_member_categories'=> array(),
			'rule_participant_by_profile_field'	=> '',
			
			'rule_product_ids'=> array(),
			'rule_product_groups'=> array(),
			'rule_product_by_custom_field'=> '',
			
			'commission_type'	=> 'percent',
			'commission_rate'	=> 0,
			
			'rule_require_purchase'		=> 'n',
			
			'commission_aplied_maxamount'		=> 0,
			'commission_aplied_maxpurchases'		=> 0,
			'commission_aplied_maxtime'		=> 0,
			
			'rule_gateways'		=> array(),
			
			'rule_priority'		=> 2
			
		);
		
		if ($this->EE->input->get('id')!==false)
		{
			$q = $this->EE->db->select()
					->from('affiliate_rules')
					->where('rule_id', $this->EE->input->get('id'))
					->get();
			if ($q->num_rows()==0)
			{
				show_error(lang('unauthorized_access'));
			}
			
			foreach ($values as $field_name=>$default_field_val)
			{
				if (is_array($default_field_val))
				{
					$values["$field_name"] = ($q->row("$field_name")!='')?unserialize($q->row("$field_name")):array();
				}
				else
				{
					$values["$field_name"] = $q->row("$field_name");
				}
			}
		}
		
		
		$js .= "
        $('#rule_participant_member_groups').multiselect({ droppable: 'none', sortable: 'none' });
        ";
        $total_members = $this->EE->db->count_all('members');
       	if ($total_members > $this->multiselect_fetch_limit)
        {
            $act = $this->EE->db->query("SELECT action_id FROM exp_actions WHERE class='Affiliate_plus' AND method='find_members'");
            $remoteUrl = trim($this->EE->config->item('site_url'), '/').'/?ACT='.$act->row('action_id');
            $js .= "
            $('#rule_participant_members').multiselect({ droppable: 'none', sortable: 'none', remoteUrl: '$remoteUrl' });
            ";
        }
        else
        {
            $js .= "
            $('#rule_participant_members').multiselect({ droppable: 'none', sortable: 'none' });
            ";
        }
		
		$member_groups_list_items = array();
        $this->EE->db->select('group_id, group_title');
        $this->EE->db->from('member_groups');
        $this->EE->db->where('site_id', $this->EE->config->item('site_id'));
        $q = $this->EE->db->get();
        foreach ($q->result_array() as $row)
        {
            $member_groups_list_items[$row['group_id']] = $row['group_title'];
        }
        
        $members_list_items = array();
        $this->EE->db->select('member_id, screen_name');
        $this->EE->db->from('members');
        if ($total_members > $this->multiselect_fetch_limit)
        {
            $this->EE->db->limit($this->multiselect_fetch_limit);
        }
        $q = $this->EE->db->get();
        foreach ($q->result_array() as $row)
        {
            $members_list_items[$row['member_id']] = $row['screen_name'];
        }        
        
        $member_profile_fields_list_items = array();
		$member_profile_fields_list_items[''] = '';
        $this->EE->db->select('m_field_id, m_field_label');
        $this->EE->db->from('member_fields');
        $q = $this->EE->db->get();
        foreach ($q->result_array() as $row)
        {
            $member_profile_fields_list_items[$row['m_field_id']] = $row['m_field_label'];
        }

		switch ($ext_settings['ecommerce_solution'])
		{ 
    		case 'simplecommerce':
				
				$this->EE->db->where('item_enabled', 'y');
		        $total_products = $this->EE->db->count_all_results('simple_commerce_items');
		       	if ($total_members > $this->multiselect_fetch_limit)
		        {
		            $act = $this->EE->db->query("SELECT action_id FROM exp_actions WHERE class='Affiliate_plus' AND method='find_products'");
		            $remoteUrl = trim($this->EE->config->item('site_url'), '/').'/?ACT='.$act->row('action_id').'&system=simplecommerce';
		            $js .= "
		            $('#rule_product_ids').multiselect({ droppable: 'none', sortable: 'none', remoteUrl: '$remoteUrl' });
		            ";
		        }
		        else
		        {
		            $js .= "
		            $('#rule_product_ids').multiselect({ droppable: 'none', sortable: 'none' });
		            ";
		        }
		        
		        $product_ids_list_items = array();
		        $product_channels = array();
		        $this->EE->db->select('simple_commerce_items.entry_id, channel_id, title');
		        $this->EE->db->from('simple_commerce_items');
		        $this->EE->db->join('channel_titles', 'simple_commerce_items.entry_id=channel_titles.entry_id', 'left');
		        $this->EE->db->where('item_enabled', 'y');
		        if ($total_members > $this->multiselect_fetch_limit)
		        {
		            $this->EE->db->limit($this->multiselect_fetch_limit);
		        }
		        $q = $this->EE->db->get();
		        foreach ($q->result_array() as $row)
		        {
		            $product_ids_list_items[$row['entry_id']] = $row['title'];
		            $product_channels[$row['channel_id']] = $row['channel_id'];
		        }       
		        
		        
				
				$product_groups_list_items = array();
    			$this->EE->db->select('channel_id, channel_title');
		        $this->EE->db->from('channels');
		        $this->EE->db->where_in('channel_id', $product_channels);
		        $q = $this->EE->db->get();
		        foreach ($q->result_array() as $row)
		        {
		            $product_groups_list_items[$row['channel_id']] = $row['channel_title'];
		        }
		        $js .= "
		            $('#rule_product_groups').multiselect({ droppable: 'none', sortable: 'none' });
		            ";
		            


		        $product_field_list_items = array();
    			$product_field_list_items[''] = '';
    			$this->EE->db->select('field_id, field_label');
    			$this->EE->db->distinct();
    			$this->EE->db->from('channels');
		        $this->EE->db->join('channel_fields', 'channels.field_group=channel_fields.group_id', 'left');
		        $this->EE->db->where_in('channel_id', $product_channels);
		        $q = $this->EE->db->get();
		        foreach ($q->result_array() as $row)
		        {
		            $product_field_list_items[$row['field_id']] = $row['field_label'];
		        }
		        
		        
		        
		        
		        break;
		        
		        
		        
    		case 'cartthrob':
    		default:
    			$this->EE->load->add_package_path(PATH_THIRD.'cartthrob/');
				$this->EE->load->model('cartthrob_settings_model');
				$cartthrob_config = $this->EE->cartthrob_settings_model->get_settings();
				$this->EE->load->remove_package_path(PATH_THIRD.'cartthrob/');
				
				$product_groups_list_items = array();
		        $this->EE->db->select('channel_id, channel_title');
		        $this->EE->db->from('channels');
		        $this->EE->db->where_in('channel_id', $cartthrob_config['product_channels']);
		        $q = $this->EE->db->get();
		        foreach ($q->result_array() as $row)
		        {
		            $product_groups_list_items[$row['channel_id']] = $row['channel_title'];
		        }
		        $js .= "
		            $('#rule_product_groups').multiselect({ droppable: 'none', sortable: 'none' });
		            ";
		        
		        $product_field_list_items = array();
    			$product_field_list_items[''] = '';
		        $this->EE->db->select('field_id, field_label');
    			$this->EE->db->distinct();
    			$this->EE->db->from('channels');
		        $this->EE->db->join('channel_fields', 'channels.field_group=channel_fields.group_id', 'left');
		        $this->EE->db->where_in('channel_id', $cartthrob_config['product_channels']);
		        $q = $this->EE->db->get();
		        foreach ($q->result_array() as $row)
		        {
		            $product_field_list_items[$row['field_id']] = $row['field_label'];
		        }
		        
		        
		        $this->EE->db->where_in('channel_id', $cartthrob_config['product_channels']);
		        $total_products = $this->EE->db->count_all_results('channel_titles');
		       	if ($total_members > $this->multiselect_fetch_limit)
		        {
		            $act = $this->EE->db->query("SELECT action_id FROM exp_actions WHERE class='Affiliate_plus' AND method='find_products'");
		            $remoteUrl = trim($this->EE->config->item('site_url'), '/').'/?ACT='.$act->row('action_id').'&system=cartthrob';
		            $js .= "
		            $('#rule_product_ids').multiselect({ droppable: 'none', sortable: 'none', remoteUrl: '$remoteUrl' });
		            ";
		        }
		        else
		        {
		            $js .= "
		            $('#rule_product_ids').multiselect({ droppable: 'none', sortable: 'none' });
		            ";
		        }
		        
		        $product_ids_list_items = array();
		        $this->EE->db->select('entry_id, title');
		        $this->EE->db->from('channel_titles');
		        $this->EE->db->where_in('channel_id', $cartthrob_config['product_channels']);
		        if ($total_members > $this->multiselect_fetch_limit)
		        {
		            $this->EE->db->limit($this->multiselect_fetch_limit);
		        }
		        $q = $this->EE->db->get();
		        foreach ($q->result_array() as $row)
		        {
		            $product_ids_list_items[$row['entry_id']] = $row['title'];
		        }       
				
				$gateways_list = array();
				foreach ($cartthrob_config['available_gateways'] as $gateway_class=>$i)
				{
					$this->EE->lang->loadfile(strtolower($gateway_class), 'cartthrob', FALSE);
					$gw_name = str_replace("Cartthrob_", "", $gateway_class);
					$title = $this->EE->lang->line(str_replace("Cartthrob_", "", $gw_name).'_title');
					if ($title==$gw_name.'_title')
					{
						$title = str_replace("_", " ", $gw_name); 
					}	
					$gateways_list["$gateway_class"] = $title;
				}
		        
		        break;

		}
		
		$commission_types_list = array(
			'percent'	=> lang('percent'),
			'credit'	=> lang('credits_dollars')
		);
		
		$priorities = array(
			'0'	=> 'Lowest',
			'1'	=> 'Lower',
			'2'	=> 'Medium',
			'3'	=> 'Higher',
			'4'	=> 'Highest'
		);
        
		$data['main_data'] = array();
		$data['main_data']['show'] = true;
		$data['main_data']['rule_title'] = form_input('rule_title', $values['rule_title'], 'style="width: 95%"').form_hidden('rule_id', $values['rule_id']);
		$data['main_data']['commission_type'] = form_dropdown('commission_type', $commission_types_list, $values['commission_type']);
		$data['main_data']['commission_rate'] = form_input('commission_rate', $values['commission_rate']);
		$data['main_data']['rule_priority'] = form_dropdown('rule_priority', $priorities, $values['rule_priority']);
		$data['main_data']['rule_terminator'] = form_checkbox('rule_terminator', 'y', ($values['rule_terminator']=='y')?true:false);
		
		$data['restrictions'] = array();
		$data['restrictions']['show'] = ($values['rule_require_purchase']=='y' || $values['commission_aplied_maxamount']!=0 || $values['commission_aplied_maxpurchases']!=0 || $values['commission_aplied_maxtime']!=0 )?true:false;
		$data['restrictions']['rule_require_purchase'] = form_checkbox('rule_require_purchase', 'y', ($values['rule_require_purchase']=='y')?true:false);
		$data['restrictions']['commission_aplied_maxamount'] = form_input('commission_aplied_maxamount', ($values['commission_aplied_maxamount']!=0)?$values['commission_aplied_maxamount']:'');
		$data['restrictions']['commission_aplied_maxpurchases'] = form_input('commission_aplied_maxpurchases', ($values['commission_aplied_maxpurchases']!=0)?$values['commission_aplied_maxpurchases']:'');
		$data['restrictions']['commission_aplied_maxtime'] = form_input('commission_aplied_maxtime', ($values['commission_aplied_maxtime']!=0)?$values['commission_aplied_maxtime']:'');
		
		if (isset($gateways_list))
		{
			$data['rule_gateways'] = array();
			$data['rule_gateways']['show'] = (!empty($values['rule_gateways']))?true:false;
			$data['rule_gateways']['rule_gateways'] = '';
			foreach ($gateways_list as $id=>$title)
			{
				$data['rule_gateways']['rule_gateways'] .= form_checkbox('rule_gateways[]', $id, in_array($id, $values['rule_gateways']), 'id="rule_gateways_'.$id.'"').NBS.form_label($title, 'rule_gateways_'.$id).BR;
			}
		}
		
		
		$data['products'] = array();
		$data['products']['show'] = (!empty($values['rule_product_ids']) || !empty($values['rule_product_groups']) || !empty($values['rule_product_by_custom_field']))?true:false;
		$data['products']['rule_product_ids'] = form_multiselect('rule_product_ids[]', $product_ids_list_items, $values['rule_product_ids'], 'id="rule_product_ids"');
		$data['products']['rule_product_groups'] = form_multiselect('rule_product_groups[]', $product_groups_list_items, $values['rule_product_groups'], 'id="rule_product_groups"');
		$data['products']['rule_product_by_custom_field'] = form_dropdown('rule_product_by_custom_field', $product_field_list_items, $values['rule_product_by_custom_field']);
		/*
		foreach ($product_field_list_items as $id=>$title)
		{
			$data['product_custom_fields']['rule_product_by_custom_field'] .= form_label($title, 'rule_product_by_custom_field_'.$id).NBS.form_checkbox('rule_product_by_custom_field[]', $id, in_array($id, $values['rule_product_by_custom_field']), 'id="rule_product_by_custom_field_'.$id.'"').BR;
		}*/
		
		$data['members'] = array();
		$data['members']['show'] = (!empty($values['rule_participant_members']) || !empty($values['rule_participant_member_groups']) || !empty($values['rule_participant_by_profile_field']) || !empty($values['rule_participant_member_categories']))?true:false;
		$data['members']['rule_participant_members'] = form_multiselect('rule_participant_members[]', $members_list_items, $values['rule_participant_members'], 'id="rule_participant_members"');
		$data['members']['rule_participant_member_groups'] = form_multiselect('rule_participant_member_groups[]', $member_groups_list_items, $values['rule_participant_member_groups'], 'id="rule_participant_member_groups"');
		$data['members']['rule_participant_by_profile_field'] = form_dropdown('rule_participant_by_profile_field', $member_profile_fields_list_items, $values['rule_participant_by_profile_field']);
		
		$query = $this->EE->db->select('settings')->from('modules')->where('module_name', 'Member_categories')->limit(1)->get(); 
        if ($query->num_rows() > 0)
        {
        	$member_categories_installed = true;
			$settings = unserialize($query->row('settings'));   
        	$member_categories_list_items = array();
			$this->EE->db->select('cat_id, cat_name');
	        $this->EE->db->from('categories');
	        $this->EE->db->where('site_id', $this->EE->config->item('site_id')); 
	        $this->EE->db->where_in('group_id', implode(',', $settings[$this->EE->config->item('site_id')]['category_groups'])); 
	        $this->EE->db->order_by('cat_order', 'asc'); 
	        $query = $this->EE->db->get();
	        foreach ($query->result() as $obj)
	        {
	           $member_categories_list_items[$obj->cat_id] = $obj->cat_name;
	        }

			$data['members']['rule_participant_member_categories'] = form_multiselect('rule_participant_member_categories[]', $member_categories_list_items, $values['rule_participant_member_categories'], 'id="rule_participant_member_categories"');
        	$js .= "
	            $('#rule_participant_member_categories').multiselect({ droppable: 'none', sortable: 'none' });
	        ";
        }
        
        $js .= '
				var draft_target = "";

			$("<div id=\"rule_delete_warning\">'.$this->EE->lang->line('rule_delete_warning').'</div>").dialog({
				autoOpen: false,
				resizable: false,
				title: "'.$this->EE->lang->line('confirm_deleting').'",
				modal: true,
				position: "center",
				minHeight: "0px", 
				buttons: {
					Cancel: function() {
					$(this).dialog("close");
					},
				"'.$this->EE->lang->line('delete_rule').'": function() {
					location=draft_target;
				}
				}});

			$(".rule_delete_warning").click( function (){
				$("#rule_delete_warning").dialog("open");
				draft_target = $(this).attr("href");
				$(".ui-dialog-buttonpane button:eq(2)").focus();	
				return false;
		});';
		
		$js .= "
            $(\".editAccordion\").css(\"borderTop\", $(\".editAccordion\").css(\"borderBottom\")); 
            $(\".editAccordion h3\").click(function() {
                if ($(this).hasClass(\"collapsed\")) { 
                    $(this).siblings().slideDown(\"fast\"); 
                    $(this).removeClass(\"collapsed\").parent().removeClass(\"collapsed\"); 
                } else { 
                    $(this).siblings().slideUp(\"fast\"); 
                    $(this).addClass(\"collapsed\").parent().addClass(\"collapsed\"); 
                }
            }); 
        ";

        $this->EE->javascript->output($js);
        
        $vars['data'] = $data;
        
    	return $this->EE->load->view('rule_edit', $vars, TRUE);
	
    }
    
    
    
    
	
	
	
	function payouts()
	{
        $ext_settings = $this->EE->affiliate_plus_lib->_get_ext_settings();
        if (empty($ext_settings))
        {
        	$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=settings');
			return;
        }
		
		$this->EE->load->helper('form');
    	$this->EE->load->library('table');  
        $this->EE->load->library('javascript');

    	$vars = array();
        
        $vars['selected']['rownum']=($this->EE->input->get_post('rownum')!='')?$this->EE->input->get_post('rownum'):0;

        $this->EE->db->select('affiliate_commissions.*, screen_name');
        $this->EE->db->from('affiliate_commissions');
        
		//$this->EE->db->start_cache();
        $this->EE->db->where('method', 'withdraw');
        //$this->EE->db->stop_cache();
        
        $this->EE->db->join('members', 'affiliate_commissions.member_id=members.member_id', 'left');
        $this->EE->db->order_by('commission_id', 'desc');

        $query = $this->EE->db->get();
        $vars['total_count'] = $query->num_rows();
        
        $date_fmt = ($this->EE->session->userdata('time_format') != '') ? $this->EE->session->userdata('time_format') : $this->EE->config->item('time_format');
       	$date_format = ($date_fmt == 'us')?'%m/%d/%y %h:%i %a':'%Y-%m-%d %H:%i';
        
        $vars['table_headings'] = array(
                        lang('date'),
                        lang('member'),
                        lang('amount'),
                        lang('status'),
                        lang('')
        			);		
		   
		$i = 0;
        foreach ($query->result_array() as $row)
        {
           	$vars['data'][$i]['date'] = $this->EE->localize->decode_date($date_format, $row['payout_date']);
           	$vars['data'][$i]['member'] = "<a href=\"".BASE.AMP.'C=myaccount'.AMP.'id='.$row['member_id']."\">".$row['screen_name']."</a> (<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=stats'.AMP.'stats_type=member'.AMP.'id='.$row['member_id']."\">".lang('view_stats')."</a>)";
           	$vars['data'][$i]['amount'] = ($row['credits']!=0)?(-$row['credits']):(-$row['credits_pending']);
           	switch ($row['order_id'])
           	{
  				case '0':
				   	$row['status'] = 'requested';
				   	break;
	   			case '-1':
			   		$row['status'] = 'cancelled';
			   		break;
		   		default:
			   		$row['status'] = 'processed';
			   		break;
  			}
           	$vars['data'][$i]['status'] = '<span class="'.$row['status'].'">'.lang($row['status']).'</span>';    
           	if ($row['order_id']==0)
           	{
           		//pending
           		$vars['data'][$i]['link'] = "<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=process_payout'.AMP.'id='.$row['commission_id']."\">".lang('process_payout')."</a> | <a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=cancel_payout'.AMP.'id='.$row['commission_id']."\" class=\"cancel_payout\">".lang('cancel_payout')."</a>";
           	}
           	elseif ($row['order_id']>0)
           	{
           		$vars['data'][$i]['link'] =  "<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=view_payout'.AMP.'id='.$row['order_id']."\">".lang('view_transaction')."</a>";
           	}
           	else
           	{
           		$vars['data'][$i]['link'] = '';
           	}
           	$i++;
 			
        }
        
        $js = '
				var draft_target = "";

			$("<div id=\"cancel_payout_warning\">'.$this->EE->lang->line('cancel_payout').'</div>").dialog({
				autoOpen: false,
				resizable: false,
				title: "'.$this->EE->lang->line('confirm_cancel_payout').'",
				modal: true,
				position: "center",
				minHeight: "0px", 
				buttons: {
					"'.lang('no').'": function() {
					$(this).dialog("close");
					},
					"'.lang('yes').'": function() {
					location=draft_target;
				}
				}});

			$(".cancel_payout").click( function (){
				$("#cancel_payout_warning").dialog("open");
				draft_target = $(this).attr("href");
				$(".ui-dialog-buttonpane button:eq(2)").focus();	
				return false;
		});';
        
        
        $q = $this->EE->db->select('COUNT(commission_id) AS qty')
    		->from('affiliate_commissions')
    		->where('order_id', 0)
    		->get();
    	
    	$vars = array();
    	$vars['masspay_button'] = false;
    	
    	if ($q->row('qty') > 2)
    	{
    		$masspay_text = lang('masspay_text');
			$vars['masspay_button'] = true;
			
			if ($q->row('qty') > 250)
    		{

    			$masspay_text .= BR.lang('masspay_quantity_high');
    		
    		}
		
			$js .= '
				var draft_target = "";

			$("<div id=\"masspay_warning\">'.$masspay_text.'</div>").dialog({
				autoOpen: false,
				resizable: false,
				title: "'.$this->EE->lang->line('pay_with_masspay').'",
				modal: true,
				position: "center",
				minHeight: "0px", 
				buttons: {
					"'.lang('no').'": function() {
					$(this).dialog("close");
					},
					"'.lang('yes').'": function() {
					location=draft_target;
				}
				}});

			$(".masspay").click( function (){
				$("#masspay_warning").dialog("open");
				draft_target = $(this).attr("href");
				$(".ui-dialog-buttonpane button:eq(2)").focus();	
				return false;
		});';

		
		}
        
        
        $this->EE->jquery->tablesorter('.mainTable', '{
			headers: {0: {sorter: false}, 7: {sorter: false}},
			widgets: ["zebra"]
		}');

		if ($vars['total_count'] > $this->perpage)
		{
        	$this->EE->db->select('COUNT(commission_id) AS cnt');
        	$this->EE->db->from('affiliate_commissions');
        	$this->EE->db->where('method', 'withdraw');
        	$query = $this->EE->db->get();
        	$vars['total_count'] = $query->row('cnt');
 		}
 		
 		$this->EE->db->flush_cache();

        $this->EE->load->library('pagination');

        $base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=payouts';

        $p_config = $this->_p_config($base_url, $this->perpage, $vars['total_count']);

		$this->EE->pagination->initialize($p_config);
        
		$vars['pagination'] = $this->EE->pagination->create_links();
        
    	return $this->EE->load->view('payouts', $vars, TRUE);
	
    }
    
    
    
    function process_payout()
    {

		if ($this->EE->input->get('id')=='')
		{
			show_error(lang('unauthorized_access'));
		}
		
		$this->EE->load->helper('form');
    	$this->EE->load->library('table');  

       	$commission_q = $this->EE->db->select()
       			->from('affiliate_commissions')
       			->where('commission_id', $this->EE->input->get('id'))
       			->where('order_id', 0)
       			->where('method', 'withdraw')
       			->get();
		
		if ($commission_q->num_rows()==0)
		{
			show_error(lang('unauthorized_access'));
		}
       	
       	$row = $q->row_array();
       	
       	//correct the amount, if needed
		$q = $this->EE->db->select('SUM(credits) as credits_total')
					->from('affiliate_commissions')
					->where('member_id', $commission_q->row('member_id'));
		$amount_avail = 0;
		if ($q->num_rows()>0)
		{
			$amount_avail = $q->row('credits_total');
		}

		if ($this->settings['devdemon_credits']=='y')
		{
			$q = $this->EE->db->select('SUM(credits) as credits_total')
					->from('credits')
					->where('member_id', $commission_q->row('member_id'));
			if ($q->num_rows()>0)
			{
				if ($q->row('credits_total') < $amount_avail)
				{
					$amount_avail = $q->row('credits_total');
				}
			}
		}
		
		$credits = abs($commission_q->row('credits_pending'));
		
		if ($credits > $amount_avail)
		{
			$credits = $amount_avail;
		}
		
		$insert = array(
			'method'			=> $this->EE->input->post('method'),
			'member_id'			=> $commission_q->row('member_id'),
			'amount'			=> $credits,
			'transaction_id'	=> $this->EE->input->post('transaction_id'),
			'comment'			=> $this->EE->input->post('comment'),
			'payout_date'		=> $this->EE->localize->now
		);
		$this->EE->db->insert('affiliate_payouts', $insert);
		$payout_id = $this->EE->db->insert_id();
		
       	$data = array(
		   	'order_id'	=> $payout_id,
		   	'credits'	=> -$credits
		   );
  		$this->EE->db->where('commission_id', $commission_q->row('commission_id'));
  		$this->EE->db->update('affiliate_commissions', $data);
  		
  		
  		if ($this->settings['devdemon_credits']=='y')
		{
			$credits_action_q = $this->EE->db->select('action_id, enabled')
									->from('exp_credits_actions')
									->where('action_name', 'affiliate_plus_withdraw')
									->get();
			if ($credits_action_q->num_rows()>0 && $credits_action_q->row('enabled')==1)
	    	{
				$pData = array(
					'action_id'			=> $credits_action_q->row('action_id'),
					'site_id'			=> $this->EE->config->item('site_id'),
					'credits'			=> -$credits,
					'receiver'			=> $commission_q->row('member_id'),
					'item_id'			=> $payout_id,
					'item_parent_id' 	=> $commission_q->row('commission_id')
				);
				
				$this->EE->affiliate_plus_lib->_save_credits($pData);
			}
		}
    	
    	$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('request_processed'));
        
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=payouts');
	
    }
    
    
    function process_payout_action()
    {

		if ($this->EE->input->get('id')=='')
		{
			show_error(lang('unauthorized_access'));
		}
		
		$this->EE->load->helper('form');
    	$this->EE->load->library('table');  

       	$q = $this->EE->db->select('commission_id')
       			->from('affiliate_commissions')
       			->where('commission_id', $this->EE->input->get('id'))
       			->where('order_id', 0)
       			->where('method', 'withdraw')
       			->get();
		
		if ($q->num_rows()==0)
		{
			show_error(lang('unauthorized_access'));
		}
       	
       	$data = array(
		   	'order_id'	=> -1
		   );
  		$this->EE->db->where('commission_id', $q->row('commission_id'));
  		$this->EE->db->update('affiliate_commissions', $data);
    	
    	$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('request_cancelled'));
        
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=payouts');
	
    }

    
    function process_masspay_action()
	{
		$q = $this->EE->db->select('affiliate_commissions.*, email')
    		->from('affiliate_commissions')
    		->join('members', 'affiliate_commissions.referral_id=member.member_id', 'left')
    		->where('order_id', 0)
    		->order_by('order_id', 'asc')
    		->limit(250)
    		->get();
		
		$i = 0;
		
		// Set request-specific fields.
		$emailSubject =urlencode($this->EE->config->item('site_name').NBS.lang('affiliate_payout'));
		$receiverType = urlencode('EmailAddress');
		$currency = urlencode('USD');							// or other currency ('GBP', 'EUR', 'JPY', 'CAD', 'AUD')
		
		// Add request-specific fields to the request string.
		$nvpStr="&EMAILSUBJECT=$emailSubject&RECEIVERTYPE=$receiverType&CURRENCYCODE=$currency";
		
		if ($q->num_rows()>2)
		{
			foreach ($q->result_array() as $row)
			{
				$i++;
				$receiverEmail = urlencode($row['email']);
				$amount = urlencode($row['credits_pending']);
				$uniqueID = urlencode($row['commission_id']);
				$note = '';//urlencode($receiverData['note']);
				$nvpStr .= "&L_EMAIL$i=$receiverEmail&L_Amt$i=$amount&L_UNIQUEID$i=$uniqueID&L_NOTE$i=$note";
			}
			
			// Execute the API operation; see the PPHttpPost function above.
			$httpParsedResponseAr = $this->_PPHttpPost('MassPay', $nvpStr);
			
			if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) {
				foreach($receiversArray as $i => $receiverData) {
					$DB->query("UPDATE exp_payouts SET status=1, transaction_date='".$now."', approver_id='".$SESS->userdata['member_id']."', approver_ip='".$SESS->userdata['ip_address']."' WHERE id=".$receiverData['uniqueID']);	
					//$q = $DB->query("SELECT deposits_avail, payout_avail FROM exp_deposits WHERE member_id='".$SESS->userdata['member_id']."' ORDER BY cts DESC LIMIT 1");
					//$DB->query("INSERT INTO exp_deposits SET member_id='".$receiverData['member_id']."', order_id='".$receiverData['uniqueID']."', method='Payout', deposits_used='".$receiverData['amount']."', deposits_avail='".($q->row['deposits_avail']-$receiverData['amount'])."', payout_avail='".($q->row['payout_avail']-$receiverData['amount'])."',  cts='".date("Y-m-d H:i:s", $now)."'");	
					
				}
				exit('MassPay Completed Successfully: '.print_r($httpParsedResponseAr, true));
			} else  {
				exit('MassPay failed: ' . print_r($httpParsedResponseAr, true));
			}
		}
		
		$this->EE->session->set_flashdata('message_success', str_replace("%x", $i, lang('masspay_processed')));
        
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=payouts');
		

	}
    
    
    
    
    function cancel_payout()
    {

		if ($this->EE->input->get('id')=='')
		{
			show_error(lang('unauthorized_access'));
		}
		
		$this->EE->load->helper('form');
    	$this->EE->load->library('table');  

       	$q = $this->EE->db->select('commission_id')
       			->from('affiliate_commissions')
       			->where('commission_id', $this->EE->input->get('id'))
       			->where('order_id', 0)
       			->where('method', 'withdraw')
       			->get();
		
		if ($q->num_rows()==0)
		{
			show_error(lang('unauthorized_access'));
		}
       	
       	$data = array(
		   	'order_id'	=> -1
		   );
  		$this->EE->db->where('commission_id', $q->row('commission_id'));
  		$this->EE->db->update('affiliate_commissions', $data);
    	
    	$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('request_cancelled'));
        
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=payouts');
	
    }
    
    
    
    function view_payout()
    {
    	if ($this->EE->input->get('id')=='')
		{
			show_error(lang('unauthorized_access'));
		}
		
		$this->EE->load->helper('form');
    	$this->EE->load->library('table');  
    	
    	$date_fmt = ($this->EE->session->userdata('time_format') != '') ? $this->EE->session->userdata('time_format') : $this->EE->config->item('time_format');
       	$date_format = ($date_fmt == 'us')?'%m/%d/%y %h:%i %a':'%Y-%m-%d %H:%i';
       	
       	$q = $this->EE->db->select('affiliate_payouts.*, screen_name')
       			->from('affiliate_payouts')
       			->join('members', 'affiliate_payouts.member_id=members.member_id', 'left')
       			->where('payout_id', $this->EE->input->get('id'))
       			->get();
		
		if ($q->num_rows()==0)
		{
			show_error(lang('unauthorized_access'));
		}
       	
       	$row = $q->row_array();
    	
    	$vars['data'] = array(
			'date'			=> 	$this->EE->localize->decode_date($date_format, $row['payout_date']),
			'member'		=>	"<a href=\"".BASE.AMP.'C=myaccount'.AMP.'id='.$row['member_id']."\">".$row['screen_name']."</a> (<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=stats'.AMP.'stats_type=member'.AMP.'id='.$row['member_id']."\">".lang('view_stats')."</a>)",
			'amount'			=> $row['amount'],
			'method'			=> lang($row['method']),
			'transaction_id'	=> $row['transaction_id'],
			'comment'			=> $row['comment'],
			
		);
        
    	return $this->EE->load->view('view_payout', $vars, TRUE);
	
    }
    
    
    
    function save_rule()
    {
    	if (empty($_POST))
    	{
    		show_error($this->EE->lang->line('unauthorized_access'));
    	}
    	
    	if (trim($this->EE->input->post('rule_title'))=='')
    	{
    		show_error(lang('name_this_rule'));
    	}   	
    	

        unset($_POST['submit']);
        $data = array();

		foreach ($_POST as $key=>$val)
        {
        	if (is_array($val))
        	{
        		$data[$key] = serialize($val);
        	}
        	else
        	{
        		$data[$key] = $val;
        	}
        }
        
        $db_fields = $this->EE->db->list_fields('affiliate_rules');
        foreach ($db_fields as $id=>$field)
        {
        	if (!isset($data[$field])) $data[$field] = '';
        }
      	
		if ($this->EE->input->post('rule_id')!='')
        {
            $this->EE->db->where('rule_id', $this->EE->input->post('rule_id'));
            $this->EE->db->update('affiliate_rules', $data);
        }
        else
        {
            $this->EE->db->insert('affiliate_rules', $data);
        }
        
        $this->EE->session->set_flashdata('message_success', $this->EE->lang->line('updated'));
        
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=index');
	
    }
    
    
    
    function delete_rule()
    {
		$success = false;
        if ($this->EE->input->get_post('id')!='')
        {
            $this->EE->db->where('rule_id', $this->EE->input->get_post('id'));
            $this->EE->db->delete('affiliate_rules');
            
            $success = $this->EE->db->affected_rows();
            
        }
        
        
        if ($success != false)
        {
            $this->EE->session->set_flashdata('message_success', $this->EE->lang->line('success')); 
        }
        else
        {
            $this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('error'));  
        }

        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=index');
        
        
    }
	
	 

    function stats()
    {
        $ext_settings = $this->EE->affiliate_plus_lib->_get_ext_settings();
        if (empty($ext_settings))
        {
        	$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=settings');
			return;
        }
		
		$this->EE->load->helper('form');
    	$this->EE->load->library('table');  
        $this->EE->load->library('javascript');
        
        $date_fmt = ($this->EE->session->userdata('time_format') != '') ? $this->EE->session->userdata('time_format') : $this->EE->config->item('time_format');
       	$date_format = ($date_fmt == 'us')?'%m/%d/%y %h:%i %a':'%Y-%m-%d %H:%i';
       	$date_format_picker = ($date_fmt == 'us')?'mm/dd/y':'yy-mm-dd';

    	$vars = array();
        
        if ($this->EE->input->get_post('perpage')!==false)
        {
        	$this->perpage = $this->EE->input->get_post('perpage');	
        }
        $vars['selected']['perpage'] = $this->perpage;
        
        $vars['selected']['rownum']=($this->EE->input->get_post('rownum')!='')?$this->EE->input->get_post('rownum'):0;
        
        $vars['selected']['member_id']=$this->EE->input->get_post('member_id');
        
        $vars['selected']['date_from']=($this->EE->input->get_post('date_from')!='')?$this->EE->input->get_post('date_from'):'';
        
        $vars['selected']['date_to']=($this->EE->input->get_post('date_to')!='')?$this->EE->input->get_post('date_to'):'';

        $this->EE->cp->add_js_script('ui', 'datepicker'); 
        $this->EE->javascript->output(' $("#date_from").datepicker({ dateFormat: "'.$date_format_picker.'" }); '); 
        $this->EE->javascript->output(' $("#date_to").datepicker({ dateFormat: "'.$date_format_picker.'" }); '); 
        
        $q = $this->EE->db->select('affiliate_commissions.member_id, screen_name')
        		->distinct()
        		->from('affiliate_commissions')
        		->join('members', 'affiliate_commissions.member_id=members.member_id', 'left')
        		->order_by('screen_name', 'asc')
        		->get();
   		$members_list = array('' => '');
   		foreach ($q->result_array() as $row)
   		{
   			$members_list[$row['member_id']] = $row['screen_name'];
   		}
   		$vars['member_select'] = form_dropdown('member_id', $members_list, $vars['selected']['member_id']);
        
        switch ($ext_settings['ecommerce_solution'])
        {
        	case 'simplecommerce':
        		$this->EE->db->select('affiliate_commissions.*, referrers.screen_name AS referrer_screen_name, referrals.screen_name AS referral_screen_name')
        			->from('affiliate_commissions')
					->join('members AS referrers', 'affiliate_commissions.member_id=referrers.member_id', 'left')
					->join('members AS referrals', 'affiliate_commissions.referral_id=referrals.member_id', 'left');
        		break;
			case 'cartthrob':
        	default:
        		$this->EE->load->add_package_path(PATH_THIRD.'cartthrob/');
				$this->EE->load->model('cartthrob_settings_model');
				$cartthrob_config = $this->EE->cartthrob_settings_model->get_settings();
				$this->EE->load->remove_package_path(PATH_THIRD.'cartthrob/');
        		$this->EE->db->select('affiliate_commissions.*, referrers.screen_name AS referrer_screen_name, referrals.screen_name AS referral_screen_name, title AS order_title')
        			->from('affiliate_commissions')
					->join('members AS referrers', 'affiliate_commissions.member_id=referrers.member_id', 'left')
					->join('members AS referrals', 'affiliate_commissions.referral_id=referrals.member_id', 'left')
        			->join('channel_titles', 'affiliate_commissions.order_id=channel_titles.entry_id', 'left');
        		break;
        }
		
		if ($vars['selected']['member_id']!='' || $vars['selected']['date_from']!='' || $vars['selected']['date_to']!='')
		{
			$this->EE->db->start_cache();
			if ($vars['selected']['member_id']!='')
			{
				$this->EE->db->where('referrers.member_id', $vars['selected']['member_id']);
			}
			if ($vars['selected']['date_from']!='')
			{
				$this->EE->db->where('record_date >= ', $this->EE->localize->convert_human_date_to_gmt($vars['selected']['date_from']));
			}
			if ($vars['selected']['date_to']!='')
			{
				$this->EE->db->where('record_date <= ', $this->EE->localize->convert_human_date_to_gmt($vars['selected']['date_to']));
			}
			$this->EE->db->stop_cache();
		}
		
		if ($this->perpage!=0)
		{
        	$this->EE->db->limit($this->perpage, $vars['selected']['rownum']);
 		}

        $query = $this->EE->db->get();
        
        $vars['table_headings'] = array(
                        lang('date'),
                        lang('affiliate'),
                        lang('order'),
                        lang('customer'),
                        lang('commission'),
                        ''
        			);		
		   
		
		   
		$i = 0;
        foreach ($query->result_array() as $row)
        {
           $vars['data'][$i]['date'] = $this->EE->localize->decode_date($date_format, $row['record_date']);
           $vars['data'][$i]['affiliate'] = "<a href=\"".BASE.AMP.'C=myaccount'.AMP.'id='.$row['member_id']."\">".$row['referrer_screen_name']."</a>";   
           switch ($ext_settings['ecommerce_solution'])
	        {
	        	case 'simplecommerce':
	        		$vars['data'][$i]['order'] = "<a href=\"".BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'entry_id='.$row['order_id']."\">".lang('order').NBS.$row['order_id']."</a>";   
	        		break;
        		case 'cartthrob':
        		default:
					$vars['data'][$i]['customer'] = ($row['referral_id']!=0)?"<a href=\"".BASE.AMP.'C=myaccount'.AMP.'id='.$row['referral_id']."\">".$row['referral_screen_name']."</a>":lang('guest');  
					break;
			}
           $vars['data'][$i]['commission'] = $row['credits']; 
           $vars['data'][$i]['other1'] = '';    
           $i++;
 			
        }
        
        $this->EE->jquery->tablesorter('.mainTable', '{
			headers: {0: {sorter: false}, 7: {sorter: false}},
			widgets: ["zebra"]
		}');

		if (($vars['selected']['rownum']==0 && $this->perpage > $query->num_rows()) || $this->perpage==0)
		{
        	$vars['total_count'] = $query->num_rows();
 		}
 		else
 		{
 			
  			$this->EE->db->select("COUNT('*') AS count")
  				->from('affiliate_commissions');
	        
	        $q = $this->EE->db->get();
	        
	        $vars['total_count'] = $q->row('count');
 		}
 		
 		$this->EE->db->flush_cache();

        $this->EE->load->library('pagination');

        $base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=stats';
        $base_url .= AMP.'perpage='.$vars['selected']['perpage'];
        if ($vars['selected']['member_id']!='')
		{
        	$base_url .= AMP.'member_id='.$vars['selected']['member_id'];
 		}

        $p_config = $this->_p_config($base_url, $vars['selected']['perpage'], $vars['total_count']);

		$this->EE->pagination->initialize($p_config);
        
		$vars['pagination'] = $this->EE->pagination->create_links();
        
    	return $this->EE->load->view('stats', $vars, TRUE);
	
    }
	
	
	function _product_stats($product_id)
	{
        $ext_settings = $this->EE->affiliate_plus_lib->_get_ext_settings();
        if (empty($ext_settings))
        {
        	$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=settings');
			return;
        }
        
        if ($this->EE->input->get('stats_type')===false || $this->EE->input->get('id')===false)
    	{
    		show_error(lang('invalid_parameter'));
    	}
		
		$this->EE->load->helper('form');
    	$this->EE->load->library('table');  
        $this->EE->load->library('javascript');

    	$vars = array();
        
        if ($this->EE->input->get_post('perpage')!==false)
        {
        	$this->perpage = $this->EE->input->get_post('perpage');	
        }
        $vars['selected']['perpage'] = $this->perpage;
        
        $vars['selected']['rownum']=($this->EE->input->get_post('rownum')!='')?$this->EE->input->get_post('rownum'):0;
        
        $vars['selected']['search']=$this->EE->input->get_post('search');
        
        switch ($ext_settings['ecommerce_solution'])
        {
        	case 'cartthrob':
        	default:
        		$this->EE->load->add_package_path(PATH_THIRD.'cartthrob/');
				$this->EE->load->model('cartthrob_settings_model');
				$cartthrob_config = $this->EE->cartthrob_settings_model->get_settings();
				$this->EE->load->remove_package_path(PATH_THIRD.'cartthrob/');
        		$this->EE->db->select('entry_id AS product_id, title AS product_title, commission_rate, require_purchase, SUM(credits) AS total_commission')
        			->from('channel_titles')
        			->join('affiliate_product_settings', "channel_titles.entry_id=affiliate_product_settings.product_id AND ".$this->EE->db->dbprefix('affiliate_product_settings').".system='".$ext_settings['ecommerce_solution']."'", 'left')
        			->join('affiliate_commissions', "channel_titles.entry_id=affiliate_commissions.product_id AND ".$this->EE->db->dbprefix('affiliate_commissions').".method='".$ext_settings['ecommerce_solution']."'", 'left')
					->where_in('channel_id', $cartthrob_config['product_channels']);
					//->where_in('status', $cartthrob_config['status']);
        		break;
        }
		
		if ($vars['selected']['search']!='')
		{
			$this->EE->db->where("product_title LIKE '%".$this->EE->db->escape_str($vars['selected']['search'], true)."%'");
		}
		
		if ($this->perpage!=0)
		{
        	$this->EE->db->limit($this->perpage, $vars['selected']['rownum']);
 		}

        $query = $this->EE->db->get();
        $vars['total_count'] = $query->num_rows();
        
        $vars['table_headings'] = array(
                        lang('id'),
                        lang('product'),
                        lang('total_commission'),
                        lang('commission_rate'),
                        lang('purchase_required'),
                        lang('view_stats'),
                        lang('edit_product_settings')
        			);		
		   
		$i = 0;
        foreach ($query->result_array() as $row)
        {
           $vars['data'][$i]['id'] = $row['product_id'];
           $vars['data'][$i]['product'] = $row['product_title'];
           $vars['data'][$i]['total_commission'] = $row['total_commission'];
           $vars['data'][$i]['commission_rate'] = $row['commission_rate'].'%'; 
           $vars['data'][$i]['purchase_required'] = ($row['require_purchase']=='y')?lang('yes'):lang('no');    
           $vars['data'][$i]['stats_link'] = "<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=stats'.AMP.'stats_type=product'.AMP.'id='.$row['product_id']."\" title=\"".$this->EE->lang->line('view_stats')."\"><img src=\"".$this->EE->config->slash_item('theme_folder_url')."third_party/affiliate_plus/stats.png\" alt=\"".$this->EE->lang->line('view_stats')."\"></a>";
           $vars['data'][$i]['edit_product_settings'] = "<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=edit_product_settings'.AMP.'id='.$row['product_id']."\" title=\"".$this->EE->lang->line('edit')."\"><img src=\"".$this->EE->cp->cp_theme_url."images/icon-edit.png\" alt=\"".$this->EE->lang->line('edit')."\"></a>";
           $i++;
 			
        }
        
        $this->EE->jquery->tablesorter('.mainTable', '{
			headers: {0: {sorter: false}, 7: {sorter: false}},
			widgets: ["zebra"]
		}');

		if ($this->perpage==0)
		{
        	$vars['total_count'] = $query->num_rows();
 		}
 		else
 		{
 			switch ($ext_settings['ecommerce_solution'])
	        {
	        	case 'cartthrob':
	        	default:
	        		$this->EE->db->select("COUNT('".$this->EE->db->dbprefix('channel_titles').".*') AS count")
	        			->from('channel_titles')
	        			->join('affiliate_product_settings', "channel_titles.entry_id=affiliate_product_settings.product_id AND ".$this->EE->db->dbprefix('affiliate_product_settings').".system='".$ext_settings['ecommerce_solution']."'", 'left')
						->where_in('channel_id', $cartthrob_config['product_channels']);
	        		break;
	        }
			
			if ($vars['selected']['search']!='')
			{
				$this->EE->db->where("title LIKE '%".$this->EE->db->escape_str($vars['selected']['search'], true)."%'");
			}
	        
	        $q = $this->EE->db->get();
	        
	        $vars['total_count'] = $q->row('count');
 		}

        $this->EE->load->library('pagination');

        $base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=products';
        $base_url .= AMP.'perpage='.$vars['selected']['perpage'];
        $base_url .= AMP.'search='.$vars['selected']['search'];

        $p_config = $this->_p_config($base_url, $vars['selected']['perpage'], $vars['total_count']);

		$this->EE->pagination->initialize($p_config);
        
		$vars['pagination'] = $this->EE->pagination->create_links();
        
    	return $this->EE->load->view('stats', $vars, TRUE);
	
    }
    
    
    
    
    
    
    
    
    function notification_templates()
    {

        $this->EE->load->helper('form');
    	$this->EE->load->library('table');
 
        $query = $this->EE->db->where('template_name', 'affiliate_plus_withdraw_request_admin_notification')->get('specialty_templates');
        foreach ($query->result_array() as $row)
        {
            $vars['data'][$row['template_name']] = array(	
                'data_title'	=> form_input("{$row['template_name']}"."[data_title]", $row['data_title'], 'style="width: 100%"'),
                'template_data'	=> form_textarea("{$row['template_name']}"."[template_data]", $row['template_data'])
        		);
    	}

    	return $this->EE->load->view('notification_templates', $vars, TRUE);
	
    }    
    
    function save_notification_templates()
    {
        
        $templates = array('affiliate_plus_withdraw_request_admin_notification');

        foreach ($templates as $template)
        {
            $data_title = (isset($_POST[$template]['data_title']))?$this->EE->security->xss_clean($_POST[$template]['data_title']):$this->EE->lang->line(str_replace('reeservation', 'subject', $template));
            $template_data = (isset($_POST[$template]['template_data']))?$this->EE->security->xss_clean($_POST[$template]['template_data']):$this->EE->lang->line(str_replace('reeservation', 'message', $template));
            
            $this->EE->db->where('template_name', $template);
            $this->EE->db->update('specialty_templates', array('data_title' => $data_title, 'template_data' => $template_data));
        }       

        $this->EE->session->set_flashdata('message_success', $this->EE->lang->line('updated'));
        
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=notification_templates');
    }
    
    
    
    
    
    
    
    
    
    function settings()
    {
		$ext_settings = $this->EE->affiliate_plus_lib->_get_ext_settings();
		
        $this->EE->load->helper('form');
    	$this->EE->load->library('table');
    	
    	$ecommerce_solutions = array(
			'cartthrob'			=>	lang('cartthrob'),
			//'brilliantretail'	=>	lang('brilliantretail'),
			'simplecommerce'	=>	lang('simplecommerce'),
			//'store'				=>	lang('expressostore')
		);
    	
 
        $vars['settings'] = array(	
            'ecommerce_solution'			=> form_dropdown('ecommerce_solution', $ecommerce_solutions, $ext_settings['ecommerce_solution']),
            'withdraw_minimum'				=> form_input('withdraw_minimum', $ext_settings['withdraw_minimum']),
            'integrate_devdemon_credits'	=> form_checkbox('devdemon_credits', 'y', (isset($ext_settings['devdemon_credits']) && $ext_settings['devdemon_credits']=='y')?true:false),
            'masspay_mode'					=> form_dropdown('masspay_mode', array('sandbox'=>lang('sandbox'), 'live'=>lang('live')), $ext_settings['masspay_mode']),
            'masspay_api_username'			=> form_input('masspay_api_username', $ext_settings['masspay_api_username']),
            'masspay_api_password'			=> form_input('masspay_api_password', $ext_settings['masspay_api_password']),
            'masspay_api_signature'			=> form_input('masspay_api_signature', $ext_settings['masspay_api_signature']),
            
    		);
		if ($this->EE->db->table_exists('credits_actions') == FALSE)
    	{
    		$vars['settings']['integrate_devdemon_credits'] = form_hidden('devdemon_credits', '').lang('not_available');
   		}
        
    	return $this->EE->load->view('settings', $vars, TRUE);
	
    }    
    
    function save_settings()
    {
		
		if (empty($_POST))
    	{
    		show_error($this->EE->lang->line('unauthorized_access'));
    	}

        unset($_POST['submit']);
        
        if ($this->EE->input->post('devdemon_credits')=='y')
        {
        	$enable = $this->EE->affiliate_plus_lib->install_credits_action();
        	if ($enable==false)
        	{
        		$_POST['devdemon_credits'] = '';
        	}
		}
        
        $this->EE->db->where('class', 'Affiliate_plus_ext');
    	$this->EE->db->update('extensions', array('settings' => serialize($_POST)));
    	
    	$this->EE->session->set_flashdata(
    		'message_success',
    	 	$this->EE->lang->line('preferences_updated')
    	);
        
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=index');
    }
    
    function _p_config($base_url, $per_page, $total_rows)
    {
        $p_config = array();
        $p_config['base_url'] = $base_url;
        $p_config['total_rows'] = $total_rows;
		$p_config['per_page'] = $per_page;
		$p_config['page_query_string'] = TRUE;
		$p_config['query_string_segment'] = 'rownum';
		$p_config['full_tag_open'] = '<p id="paginationLinks">';
		$p_config['full_tag_close'] = '</p>';
		$p_config['prev_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_prev_button.gif" width="13" height="13" alt="&lt;" />';
		$p_config['next_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_next_button.gif" width="13" height="13" alt="&gt;" />';
		$p_config['first_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_first_button.gif" width="13" height="13" alt="&lt; &lt;" />';
		$p_config['last_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_last_button.gif" width="13" height="13" alt="&gt; &gt;" />';
        return $p_config;
    }
    
    
    
    function _PPHttpPost($methodName_, $nvpStr_) {

		$ext_settings = $this->EE->affiliate_plus_lib->_get_ext_settings();
	
		// Set up your API credentials, PayPal end point, and API version.
		$API_UserName = urlencode($ext_settings['masspay_api_username']);
		$API_Password = urlencode($ext_settings['masspay_api_password']);
		$API_Signature = urlencode($ext_settings['masspay_api_signature']);
		$API_Endpoint = "https://api-3t.paypal.com/nvp";
		if($ext_settings['masspay_mode']=="sandbox") {
			$API_Endpoint = "https://api-3t.$this->environment.paypal.com/nvp";
		}

		$version = urlencode('51.0');
	
		// Set the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
	
		// Turn off the server and peer verification (TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
	
		// Set the API operation, version, and API signature in the request.
		$nvpreq = "METHOD=$methodName_&VERSION=$version&PWD=$API_Password&USER=$API_UserName&SIGNATURE=$API_Signature$nvpStr_";
	
		// Set the request as a POST FIELD for curl.
		curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);
	
		// Get response from the server.
		$httpResponse = curl_exec($ch);
	
		if(!$httpResponse) {
			exit("$methodName_ failed: ".curl_error($ch).'('.curl_errno($ch).')');
		}
	
		// Extract the response details.
		$httpResponseAr = explode("&", $httpResponse);
	
		$httpParsedResponseAr = array();
		foreach ($httpResponseAr as $i => $value) {
			$tmpAr = explode("=", $value);
			if(sizeof($tmpAr) > 1) {
				$httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
			}
		}
	
		if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {
			exit("Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.");
		}
	
		return $httpParsedResponseAr;
	}
    
    
  
  

}
/* END */
?>