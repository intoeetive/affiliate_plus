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


class Affiliate_plus {

    var $return_data	= ''; 	
    
    var $settings = array();

    /** ----------------------------------------
    /**  Constructor
    /** ----------------------------------------*/

    function __construct()
    {        
    	$this->EE =& get_instance(); 
    	
    	$this->EE->load->library('affiliate_plus_lib');  

		$this->EE->lang->loadfile('affiliate_plus');  
    }
    /* END */
    
    
    
    function find_members()
    { 
        $str = urldecode($this->EE->input->get_post('q'));
        if (strlen($str)<3)
        {
            exit();
        }
        $this->EE->db->select('member_id, screen_name');
        $this->EE->db->from('members');
        $this->EE->db->where('screen_name LIKE "%'.$str.'%"');
        $q = $this->EE->db->get();
        $out = '';
        foreach ($q->result_array() as $row)
        {
            $out .= $row['member_id']."=".$row['screen_name']."\n";
        }
        echo trim($out);
        exit();
    }
    
    function find_products()
    { 
        $out = '';
        $str = urldecode($this->EE->input->get_post('q'));
        if (strlen($str)<3)
        {
            exit();
        }
		switch ($this->EE->input->get_post('system'))
        {
			case 'simplecommerce':				
		        $this->EE->db->select('simple_commerce_items.entry_id, title');
		        $this->EE->db->from('simple_commerce_items');
		        $this->EE->db->join('channel_titles', 'simple_commerce_items.entry_id=channel_titles.entry_id', 'left');
		        $this->EE->db->where('item_enabled', 'y');
		        $this->EE->db->where('title LIKE "%'.$str.'%"');
		        $q = $this->EE->db->get();
		        foreach ($q->result_array() as $row)
		        {
		            $out .= $row['entry_id']."=".$row['title']."\n";
		        }
		        break;
		        
   			case 'store':				
		        $this->EE->db->select('store_products.entry_id, title');
		        $this->EE->db->from('store_products');
		        $this->EE->db->join('channel_titles', 'store_products.entry_id=channel_titles.entry_id', 'left');
		        $this->EE->db->where('title LIKE "%'.$str.'%"');
		        $q = $this->EE->db->get();
		        foreach ($q->result_array() as $row)
		        {
		            $out .= $row['entry_id']."=".$row['title']."\n";
		        }
		        break;
		        
			case 'cartthrob':
			default:
				$this->EE->load->add_package_path(PATH_THIRD.'cartthrob/');
				$this->EE->load->model('cartthrob_settings_model');
				$cartthrob_config = $this->EE->cartthrob_settings_model->get_settings();
				$this->EE->load->remove_package_path(PATH_THIRD.'cartthrob/');
				
		        $this->EE->db->select('entry_id, title');
		        $this->EE->db->from('channel_titles');
		        $this->EE->db->where_in('channel_id', $cartthrob_config['product_channels']);
		        $this->EE->db->where('title LIKE "%'.$str.'%"');
		        $q = $this->EE->db->get();
		        foreach ($q->result_array() as $row)
		        {
		            $out .= $row['entry_id']."=".$row['title']."\n";
		        }
		        break;
   		}
   		echo trim($out);
     	exit();
    }
    


    
    function link()
    {
		if ($this->EE->session->userdata('member_id')==0) 
		{
			return $this->EE->TMPL->no_results();
		}
		
		if ($this->EE->TMPL->fetch_param('return')!='')
        {
		    if ($this->EE->TMPL->fetch_param('return')=='SAME_PAGE')
		    {
		        $return = $this->EE->functions->fetch_current_uri();
		    }
		    else if (strpos($this->EE->TMPL->fetch_param('return'), "http://")!==FALSE || strpos($this->EE->TMPL->fetch_param('return'), "https://")!==FALSE)
		    {
		        $return = $this->EE->TMPL->fetch_param('return');
		    }
		    else
		    {
		        $return = $this->EE->functions->create_url($this->EE->TMPL->parse_globals($this->EE->TMPL->fetch_param('return')));
		    }
        }
        
        $act = $this->EE->db->select('action_id')
						->from('exp_actions')
						->where('class', 'Affiliate_plus')
						->where('method', 'register_hit')
						->get();
        $link = rtrim($this->EE->config->item('site_url'), '/').'/?ACT='.$act->row('action_id').'&from='.$this->EE->session->userdata('username');
        if (isset($return))
        {
        	$link .= '&go='.base64_encode($return);
        }
        
        return $link;
		
    }
    
    
    
    function withdraw_request_form()
    {
		$ext_settings = $this->EE->affiliate_plus_lib->_get_ext_settings();
		
		if ($this->EE->TMPL->fetch_param('return')=='')
        {
            $return = $this->EE->functions->fetch_site_index();
        }
        else if ($this->EE->TMPL->fetch_param('return')=='SAME_PAGE')
        {
            $return = $this->EE->functions->fetch_current_uri();
        }
        else if (strpos($this->EE->TMPL->fetch_param('return'), "http://")!==FALSE || strpos($this->EE->TMPL->fetch_param('return'), "https://")!==FALSE)
        {
            $return = $this->EE->TMPL->fetch_param('return');
        }
        else
        {
            $return = $this->EE->functions->create_url($this->EE->TMPL->fetch_param('return'));
        }
        
        $data['hidden_fields']['ACT'] = $this->EE->functions->fetch_action_id('Affiliate_plus', 'process_withdraw_request');
		$data['hidden_fields']['RET'] = $return;
        $data['hidden_fields']['PRV'] = $this->EE->functions->fetch_current_uri();
        
        if ($this->EE->TMPL->fetch_param('ajax')=='yes') $data['hidden_fields']['ajax'] = 'yes';
									      
        $data['id']		= ($this->EE->TMPL->fetch_param('id')!='') ? $this->EE->TMPL->fetch_param('id') : 'affiliate_plus_form';
        $data['name']		= ($this->EE->TMPL->fetch_param('name')!='') ? $this->EE->TMPL->fetch_param('name') : 'affiliate_plus_form';
        $data['class']		= ($this->EE->TMPL->fetch_param('class')!='') ? $this->EE->TMPL->fetch_param('class') : 'affiliate_plus_form';
		
		$tagdata = $this->EE->TMPL->tagdata;
		
		$q = $this->EE->db->select('SUM(credits) as credits_total')
					->from('affiliate_commissions')
					->where('member_id', $this->EE->session->userdata('member_id'))
					->get();
		$amount_avail = 0;
		if ($q->num_rows()>0)
		{
			$amount_avail = $q->row('credits_total');
		}

		if (isset($ext_settings['devdemon_credits']) && $ext_settings['devdemon_credits']=='y')
		{
			$q = $this->EE->db->select('SUM(credits) as credits_total')
					->from('credits')
					->where('member_id', $this->EE->session->userdata('member_id'))
					->get();
			if ($q->num_rows()>0)
			{
				if ($q->row('credits_total') < $amount_avail)
				{
					$amount_avail = $q->row('credits_total');
				}
			}
		}
		
		$tagdata = $this->EE->TMPL->swap_var_single('amount_min', (float)$ext_settings['withdraw_minimum'], $tagdata);
		$tagdata = $this->EE->TMPL->swap_var_single('amount', (float)$amount_avail, $tagdata);
		
        $out = $this->EE->functions->form_declaration($data).$tagdata."\n"."</form>";
        
        return $out;
    }
    
    
    function process_withdraw_request()
    {
		if ($this->EE->input->post('amount')==0)
		{
			$this->EE->output->show_user_error('general', lang('please_provide_amount_to_withdraw'));
		}
		
		if ($this->EE->session->userdata('member_id')==0)
		{
			$this->EE->output->show_user_error('general', lang('please_log_in'));
		}
		
		if ($this->EE->security->secure_forms_check($this->EE->input->post('XID')) == FALSE)
		{
			$this->EE->output->show_user_error('submission', lang('security_check_failed'));
		}
		
		$ext_settings = $this->EE->affiliate_plus_lib->_get_ext_settings();
		if ($ext_settings['withdraw_minimum']!='' && $this->EE->input->post('amount') < $ext_settings['withdraw_minimum'])
		{
			$this->EE->output->show_user_error('general', lang('requested_amount_less_minimum'));
		}
		
		$q = $this->EE->db->select('commission_id')
				->from('affiliate_commissions')
				->where('member_id', $this->EE->session->userdata('member_id'))
				->where('method', 'withdraw')
				->where('order_id', '0')
				->get();
		if ($q->num_rows()>0)
		{
			$this->EE->output->show_user_error('general', lang('already_have_withdraw_request_pending'));
		}
        
		$q = $this->EE->db->select('SUM(credits) as credits_total')
					->from('affiliate_commissions')
					->where('member_id', $this->EE->session->userdata('member_id'))
					->get();
		$amount_avail = 0;
		if ($q->num_rows()>0)
		{
			$amount_avail = $q->row('credits_total');
		}

		if (isset($this->settings['devdemon_credits']) && $this->settings['devdemon_credits']=='y')
		{
			$q = $this->EE->db->select('SUM(credits) as credits_total')
					->from('credits')
					->where('member_id', $this->EE->session->userdata('member_id'))
					->get();
			if ($q->num_rows()>0)
			{
				if ($q->row('credits_total') < $amount_avail)
				{
					$amount_avail = $q->row('credits_total');
				}
			}
		}
		
		if ($this->EE->input->post('amount') > $amount_avail)
		{
			$this->EE->output->show_user_error('general', lang('amount_is_greater_than_balance'));
		}
		
		$insert = array(
			'method'			=> 'withdraw',
			'member_id'			=> $this->EE->session->userdata('member_id'),
			'credits_pending'	=> (0-$this->EE->input->post('amount')),
			'record_date'		=> $this->EE->localize->now
		);
		$this->EE->db->insert('affiliate_commissions', $insert);
		
		//notify site admin
		$this->EE->load->library('email');
        $this->EE->load->helper('string');
        $this->EE->load->helper('text');
        
        $swap = array(
			'site_name'	=> $this->EE->config->item('site_name'),
			'site_url'	=> $this->EE->config->item('site_url'),
			'amount'	=> $this->EE->input->post('amount'),
			'member_id'	=> $this->EE->session->userdata('member_id'),
			'username'	=> $this->EE->session->userdata('username'),
			'screen_name'	=> $this->EE->session->userdata('screen_name'),
			'cp_link'	=> $this->EE->config->item('cp_url').'?D=cp&C=addons_modules&M=show_module_cp&module=affiliate_plus&method=payouts'
		);
		$template = $this->EE->functions->fetch_email_template('affiliate_plus_withdraw_request_admin_notification');
		$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
		$email_msg = $this->EE->functions->var_swap($template['data'], $swap);
        
        
        $this->EE->email->initialize();
		$this->EE->email->wordwrap = false;
		$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
		$this->EE->email->to($this->EE->config->item('webmaster_email')); 
		$this->EE->email->subject($email_tit);	
		$this->EE->email->message(entities_to_ascii($email_msg));		
		$this->EE->email->Send();
		
		
		//redirect
		if ($this->EE->input->post('skip_success_message')=='y')
        {
        	$this->EE->functions->redirect($_POST['RET']);
        }
        
        $message = array(	
						'title' 	=> lang('success'),
        				'heading'	=> lang('success'),
        				'content'	=> lang('withdraw_request_accepted'),
        				'redirect'	=> $_POST['RET'],
        				'link'		=> array($_POST['RET'], $swap['site_name']),
                        'rate'		=> 5
        			 );
		
		$this->EE->output->show_message($message);
		
    }
    
    
    
    function _redirect($url='')
    {
    	if ($url=='')
    	{
    		$url = $this->EE->functions->fetch_site_index(1);
    	}
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: $url");
		exit;
    }

    
    function register_hit()
    {
    	//prepare URL and make sure it's valid
		$url = ($this->EE->input->get('go')!='')?base64_decode($this->EE->input->get('go')):$this->EE->functions->fetch_site_index(1);
    	if ($this->EE->input->get('go')!='' && preg_match("/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i", $url)===false)
    	{
    		$url = $this->EE->functions->fetch_site_index(1);
    	}    
		
		//referres is missing?
		if ($this->EE->input->get('from')=='')
    	{
    		$this->_redirect($url);
    	}	
    	
		//is logged in already? no cookies for you
    	if ($this->EE->session->userdata('member_id')!=0)
    	{
    		$this->_redirect($url);
    	}
    	
    	//checking 'last_visit' cookie
    	//at first visit it is set '10 years ago', so if it's older than 15 sec
    	//then user has visited us before
    	if ($this->EE->input->cookie('last_visit')!='' && $this->EE->input->cookie('last_visit') < ($this->EE->localize->now - 15))
    	{
    		$this->_redirect($url);
    	}
    	
		//cookie already set?
		if ($this->EE->input->cookie('affiliate_plus_referrer_id')!='')
		{
			$this->_redirect($url);
		}
		
		//is referrer valid user? also make sure is not blocked/guest/pending
		$user_q = $this->EE->db->select('member_id, group_id')
					->from('members')
					->where('username', $this->EE->input->get('from'))
					->get();
    	if ($user_q->num_rows()==0)
    	{
    		$this->_redirect($url);
    	}
    	if (in_array($user_q->row('group_id'), array(2,3,4)))
    	{
    		$this->_redirect($url);
    	}
    	
    	//set cookie
    	$expire = 60*60*24*365; //1 year
    	
    	$this->EE->functions->set_cookie('affiliate_plus_referrer_id', $user_q->row('member_id'), $expire);	
    	
    	//insert data
    	$data = array(
			'ip_address'		=> $this->EE->input->ip_address(),
			'referrer_id'		=> $user_q->row('member_id'),
			'referrer_server'	=> (isset($_SERVER['HTTP_REFERER']))?$_SERVER['HTTP_REFERER']:'',
			'hit_date'			=> $this->EE->localize->now
		);
		
		$this->EE->db->insert('affiliate_hits', $data);

		$this->EE->functions->set_cookie('affiliate_plus_hit_id', $this->EE->db->insert_id(), $expire);	

    	$this->_redirect($url);
	
	}
	
	
	
	function balance()
	{
		if ($this->EE->session->userdata('member_id')==0)
		{
			return $this->EE->TMPL->no_results();
		}
		
		$ext_settings = $this->EE->affiliate_plus_lib->_get_ext_settings();
		
		$q = $this->EE->db->select('SUM(credits) as credits_total')
					->from('affiliate_commissions')
					->where('member_id', $this->EE->session->userdata('member_id'))
					->get();
		$amount_avail = 0;
		if ($q->num_rows()>0)
		{
			$amount_avail = $q->row('credits_total');
		}

		if (isset($ext_settings['devdemon_credits']) && $ext_settings['devdemon_credits']=='y')
		{
			$q = $this->EE->db->select('SUM(credits) as credits_total')
					->from('credits')
					->where('member_id', $this->EE->session->userdata('member_id'))
					->get();
			if ($q->num_rows()>0)
			{
				if ($q->row('credits_total') < $amount_avail)
				{
					$amount_avail = $q->row('credits_total');
				}
			}
		}
		
		return (float)$amount_avail;
		
	}
	
	
	
	
	function referrer()
	{
		$member_id = $this->EE->session->userdata('member_id');
		
		if ($this->EE->TMPL->fetch_param('member_id')!=false)
		{
			$member_id = $this->EE->TMPL->fetch_param('member_id');
		}
		if ($this->EE->TMPL->fetch_param('username')!=false)
		{
			$username = $this->EE->TMPL->fetch_param('username');
		}
		if ($member_id==0 && !isset($username))
		{
			return $this->EE->TMPL->no_results();
		}

		$this->EE->db->select('members.member_id AS affiliate_member_id, username AS affiliate_username, screen_name AS affiliate_screen_name')
			->from('affiliate_hits')
			->join('members', 'affiliate_hits.referrer_id=members.member_id', 'left');
		if (!isset($username))
		{
			$this->EE->db->where('affiliate_hits.member_id', $member_id);
			
		}
		else
		{
			$this->EE->db->join('members AS m2', 'affiliate_hits.member_id=m2.member_id', 'left');
			$this->EE->db->where('m2.username', $username);
		}
		$this->EE->db->limit(1);
		//echo $this->EE->db->_compile_select();
		$q = $this->EE->db->get();
		
		if ($q->num_rows()==0)
		{
			return $this->EE->TMPL->no_results();
		}
		
		$tagdata = $this->EE->TMPL->parse_variables_row($this->EE->TMPL->tagdata, $q->row_array());
		
		return $tagdata;
		
	}
	
	
	
	
	
	function stats()
	{
        if ($this->EE->session->userdata('member_id')==0)
        {
        	return $this->EE->TMPL->no_results();
        }
		
		$ext_settings = $this->EE->affiliate_plus_lib->_get_ext_settings();
        
        $paginate = ($this->EE->TMPL->fetch_param('paginate')=='top')?'top':(($this->EE->TMPL->fetch_param('paginate')=='both')?'both':'bottom');
        $perpage = ($this->EE->TMPL->fetch_param('limit')!='') ? $this->EE->TMPL->fetch_param('limit') : '25';
        
        $start = 0;
        $basepath = $this->EE->functions->create_url($this->EE->uri->uri_string);
        $query_string = ($this->EE->uri->page_query_string != '') ? $this->EE->uri->page_query_string : $this->EE->uri->query_string;

		if (preg_match("#^P(\d+)|/P(\d+)#", $query_string, $match))
		{
			$start = (isset($match[2])) ? $match[2] : $match[1];
            if (version_compare(APP_VER, '2.6.0', '<'))
			{
				$basepath = $this->EE->functions->remove_double_slashes(str_replace($match[0], '', $basepath));
			}
			else
			{
				$this->EE->load->helper('string');
                $basepath = reduce_double_slashes(str_replace($match[0], '', $basepath));
			}
		}

    	$vars = array();
    	$global_vars = array();
    	
    	$q = $this->EE->db->select("COUNT('*') AS count")
  				->from('affiliate_hits')
				 ->where('referrer_id', $this->EE->session->userdata('member_id'))
				 ->get();
		$global_vars['total_hits'] = $q->row('count');
		
		if ($global_vars['total_hits']==0)
        {
            return $this->EE->TMPL->no_results();
        }
        
        $global_vars['balance'] = $this->balance();
 		$global_vars['withdraw_minimum'] = (float)$ext_settings['withdraw_minimum'];
 		$global_vars['total_commission_records'] = 0;
 		
 		$tagdata = $this->EE->TMPL->tagdata;
 		
 		$paginate_tagdata = '';
	        
        if ( preg_match_all("/".LD."paginate".RD."(.*?)".LD."\/paginate".RD."/s", $tagdata, $tmp)!=0)
        {
            $paginate_tagdata = $tmp[1][0];
            $tagdata = str_replace($tmp[0][0], '', $tagdata);
        }
       
        switch ($ext_settings['ecommerce_solution'])
        {
   			case 'simplecommerce':
   			case 'store':
			case 'cartthrob':
        	default:
        		$this->EE->db->select('affiliate_commissions.*, referrals.member_id AS referral_member_id, referrals.username AS referral_username, referrals.screen_name AS referral_screen_name')
        			->from('affiliate_commissions')
        			->where('affiliate_commissions.member_id', $this->EE->session->userdata('member_id'))
        			->where('affiliate_commissions.order_id != ', 0)
					->join('members AS referrals', 'affiliate_commissions.referral_id=referrals.member_id', 'left');
        		break;
        }
		
		$this->EE->db->limit($perpage, $start);
        $query = $this->EE->db->get();

        if ($query->num_rows()>0)
        {
	        
	        foreach ($query->result_array() as $row)
	        {
	           $row['commission'] = $row['credits']; 
	           $row['method'] = $this->EE->lang->line($row['method']); 
	           $vars[] = $row;
	        }
	        
	        $tagdata = $this->EE->TMPL->parse_variables($tagdata, $vars);
	
			if ($start==0 && $perpage > $query->num_rows())
			{
	        	$global_vars['total_commission_records'] = $query->num_rows();
	 		}
	 		else
	 		{
	 			
	  			$this->EE->db->select("COUNT('*') AS count")
	  				->from('affiliate_commissions')
					 ->where('member_id', $this->EE->session->userdata('member_id'))
					 ->where('order_id != ', 0);
		        
		        $q = $this->EE->db->get();
		        
		        $global_vars['total_commission_records']  = $q->row('count');
	 		}
 		}
 		else
 		{
 			$global_vars['count'] = 1;
 			$global_vars['total_results'] = 1;
 		}
 		
 		$tagdata = $this->EE->TMPL->parse_variables_row($tagdata, $global_vars);
        
        $tagdata = $this->_process_pagination($global_vars['total_commission_records'], $perpage, $start, $basepath, $tagdata, $paginate, $paginate_tagdata);
        
        return $tagdata;
        
	
    }
	
	
	
	
	function withdraw_history()
	{
        if ($this->EE->session->userdata('member_id')==0)
        {
        	return $this->EE->TMPL->no_results();
        }
		
		$ext_settings = $this->EE->affiliate_plus_lib->_get_ext_settings();
        
        $paginate = ($this->EE->TMPL->fetch_param('paginate')=='top')?'top':(($this->EE->TMPL->fetch_param('paginate')=='both')?'both':'bottom');
        $perpage = ($this->EE->TMPL->fetch_param('limit')!='') ? $this->EE->TMPL->fetch_param('limit') : '25';
        
        $start = 0;
        $basepath = $this->EE->functions->create_url($this->EE->uri->uri_string);
        $query_string = ($this->EE->uri->page_query_string != '') ? $this->EE->uri->page_query_string : $this->EE->uri->query_string;

		if (preg_match("#^P(\d+)|/P(\d+)#", $query_string, $match))
		{
			$start = (isset($match[2])) ? $match[2] : $match[1];
            if (version_compare(APP_VER, '2.6.0', '<'))
			{
				$basepath = $this->EE->functions->remove_double_slashes(str_replace($match[0], '', $basepath));
			}
			else
			{
				$this->EE->load->helper('string');
                $basepath = reduce_double_slashes(str_replace($match[0], '', $basepath));
			}
		}

    	$vars = array();
    	$global_vars = array();
       
        switch ($ext_settings['ecommerce_solution'])
        {
   			case 'simplecommerce':
   			case 'store':
			case 'cartthrob':
        	default:
        		$this->EE->db->select('affiliate_commissions.order_id, affiliate_commissions.record_date AS request_date, affiliate_commissions.credits_pending AS amount_pending, affiliate_payouts.*')
        			->from('affiliate_commissions')
        			->where('affiliate_commissions.member_id', $this->EE->session->userdata('member_id'))
        			->where('affiliate_commissions.method', 'withdraw')
					->join('affiliate_payouts', 'affiliate_commissions.order_id=affiliate_payouts.payout_id', 'left');
        		break;
        }
		
		$this->EE->db->limit($perpage, $start);
        $query = $this->EE->db->get();
        
        if ($query->num_rows()==0)
        {
            return $this->EE->TMPL->no_results();
        }
        
        
        $tagdata = $this->EE->TMPL->tagdata;
        $paginate_tagdata = '';
        
        if ( preg_match_all("/".LD."paginate".RD."(.*?)".LD."\/paginate".RD."/s", $tagdata, $tmp)!=0)
        {
            $paginate_tagdata = $tmp[1][0];
            $tagdata = str_replace($tmp[0][0], '', $tagdata);
        }
        
        $this->EE->load->library('typography');
        $this->EE->typography->initialize();
        
        foreach ($query->result_array() as $row)
        {
			switch ($row['order_id'])
			{
				case '0':
				   	$row['status'] = lang('requested');
				   	break;
				case '-1':
			   		$row['status'] = lang('cancelled');
			   		break;
				default:
			   		$row['status'] = lang('processed');
			   		break;
			}
			if (!isset($row['payout_id']) || $row['payout_id']=='')
			{
				$row['payout_id'] = '';
				$row['method'] = '';
				$row['amount'] = 0;
				$row['amount_pending'] = -$row['amount_pending'];
				$row['transaction_id'] = '';
				$row['comment'] = '';
				$row['request_date'] = '';
				$row['payment_date'] = '';
			}
			else
			{
				$row['comment'] = $this->EE->typography->parse_type($row['comment']); 
	           	$row['method'] = $this->EE->lang->line($row['method']); 
	           	$row['payment_date'] = $row['payout_date'];
   			}
           	$vars[] = $row;
        }
        
        $tagdata = $this->EE->TMPL->parse_variables($tagdata, $vars);

		if ($start==0 && $perpage > $query->num_rows())
		{
        	$global_vars['total_withdraw_records'] = $query->num_rows();
 		}
 		else
 		{
 			
  			$this->EE->db->select("COUNT('*') AS count")
				->from('affiliate_commissions')
				 ->where('member_id', $this->EE->session->userdata('member_id'))
				 ->where('method', 'withdraw')
				 ->get();
	        
	        $q = $this->EE->db->get();
	        
	        $global_vars['total_withdraw_records']  = $q->row('count');
 		}
 		
 		$global_vars['balance'] = $this->balance();
 		$global_vars['withdraw_minimum'] = (float)$ext_settings['withdraw_minimum'];
 		
 		
 		$tagdata = $this->EE->TMPL->parse_variables_row($tagdata, $global_vars);
        
        $tagdata = $this->_process_pagination($global_vars['total_withdraw_records'], $perpage, $start, $basepath, $tagdata, $paginate, $paginate_tagdata);
        
        return $tagdata;
        
	
    }
	
    
    
    
    
    function _process_pagination($total, $perpage, $start, $basepath='', $out='', $paginate='bottom', $paginate_tagdata='')
    {
        if (version_compare(APP_VER, '2.4.0', '>='))
		{
	        $this->EE->load->library('pagination');
	        if (version_compare(APP_VER, '2.6.0', '>='))
	        {
	        	$pagination = $this->EE->pagination->create(__CLASS__);
	        }
	        else
	        {
	        	$pagination = new Pagination_object(__CLASS__);
	        }
            if (version_compare(APP_VER, '2.8.0', '>='))
            {
                $this->EE->TMPL->tagdata = $pagination->prepare($this->EE->TMPL->tagdata);
                $pagination->build($total, $perpage);
            }
            else
            {
                $pagination->get_template();
    	        $pagination->per_page = $perpage;
    	        $pagination->total_rows = $total;
    	        $pagination->offset = $start;
    	        $pagination->build($pagination->per_page);
            }
	        
	        $out = $pagination->render($out);
  		}
  		else
  		{
        
	        if ($total > $perpage)
	        {
	            $this->EE->load->library('pagination');
	
				$config['base_url']		= $basepath;
				$config['prefix']		= 'P';
				$config['total_rows'] 	= $total;
				$config['per_page']		= $perpage;
				$config['cur_page']		= $start;
				$config['first_link'] 	= $this->EE->lang->line('pag_first_link');
				$config['last_link'] 	= $this->EE->lang->line('pag_last_link');
	
				$this->EE->pagination->initialize($config);
				$pagination_links = $this->EE->pagination->create_links();	
	            $paginate_tagdata = $this->EE->TMPL->swap_var_single('pagination_links', $pagination_links, $paginate_tagdata);			
	        }
	        else
	        {
	            $paginate_tagdata = $this->EE->TMPL->swap_var_single('pagination_links', '', $paginate_tagdata);		
	        }
	        
	        switch ($paginate)
	        {
	            case 'top':
	                $out = $paginate_tagdata.$out;
	                break;
	            case 'both':
	                $out = $paginate_tagdata.$out.$paginate_tagdata;
	                break;
	            case 'bottom':
	            default:
	                $out = $out.$paginate_tagdata;
	        }
	        
    	}
        
        return $out;
    }
    
	



}
/* END */
?>