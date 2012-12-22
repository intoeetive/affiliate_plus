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
		        $return = $this->EE->functions->create_url($this->EE->TMPL->fetch_param('return'));
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
					->where('member_id', $this->EE->session->userdata('member_id'));
		$amount_avail = 0;
		if ($q->num_rows()>0)
		{
			$amount_avail = $q->row('credits_total');
		}

		if ($ext_settings['devdemon_credits']=='y')
		{
			$q = $this->EE->db->select('SUM(credits) as credits_total')
					->from('credits')
					->where('member_id', $this->EE->session->userdata('member_id'));
			if ($q->num_rows()>0)
			{
				if ($q->row('credits_total') < $amount_avail)
				{
					$amount_avail = $q->row('credits_total');
				}
			}
		}
		
		$tagdata = $this->EE->TMPL->swap_var_single('amount_min', $ext_settings['withdraw_minimum'], $tagdata);
		$tagdata = $this->EE->TMPL->swap_var_single('amount', $amount_avail, $tagdata);
		
        $out = $this->EE->functions->form_declaration($data).$tagdata."\n"."</form>";
        
        return $out;
    }
    
    
    function process_withdraw_request()
    {
		if ($this->EE->input->post('amount')==0)
		{
			$this->EE->output->show_user_error('general', lang('please_provide_amount_to_withdraw'));
		}
		
		$ext_settings = $this->EE->affiliate_plus_lib->_get_ext_settings();
		if ($this->EE->input->post('amount') < $ext_settings['withdraw_minimum'])
		{
			$this->EE->output->show_user_error('general', lang('requested_amount_less_minimum'));
		}
		
		$q = $this->EE->db->select('commission_id')
				->from('affiliate_commissions')
				->where('method', 'withdraw')
				->where('order_id', '0')
				->get();
		if ($q->num_rows()>0)
		{
			$this->EE->output->show_user_error('general', lang('already_have_withdraw_request_pending'));
		}
        
		$q = $this->EE->db->select('SUM(credits) as credits_total')
					->from('affiliate_commissions')
					->where('member_id', $this->EE->session->userdata('member_id'));
		$amount_avail = 0;
		if ($q->num_rows()>0)
		{
			$amount_avail = $q->row('credits_total');
		}

		if ($this->settings['devdemon_credits']=='y')
		{
			$q = $this->EE->db->select('SUM(credits) as credits_total')
					->from('credits')
					->where('member_id', $this->EE->session->userdata('member_id'));
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
		
		if ($this->EE->input->post('skip_success_message')=='y')
        {
        	$this->EE->functions->redirect($_POST['RET']);
        }
        
        $message = array(	
						'title' 	=> lang('success'),
        				'heading'	=> lang('success'),
        				'content'	=> lang('withdraw_request_accepted'),
        				'redirect'	=> $_POST['RET'],
        				'link'		=> array($_POST['RET'], $site_name),
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
			'referrer_server'	=> $_SERVER['HTTP_REFERER'],
			'hit_date'			=> $this->EE->localize->now
		);
		
		$this->EE->db->insert('affiliate_hits', $data);

		$this->EE->functions->set_cookie('affiliate_plus_hit_id', $this->EE->db->insert_id(), $expire);	

    	$this->_redirect($url);
	
	}
	
	
	
	function balance()
	{
		$ext_settings = $this->EE->affiliate_plus_lib->_get_ext_settings();
		
		$q = $this->EE->db->select('SUM(credits) as credits_total')
					->from('affiliate_commissions')
					->where('member_id', $this->EE->session->userdata('member_id'));
		$amount_avail = 0;
		if ($q->num_rows()>0)
		{
			$amount_avail = $q->row('credits_total');
		}

		if ($ext_settings['devdemon_credits']=='y')
		{
			$q = $this->EE->db->select('SUM(credits) as credits_total')
					->from('credits')
					->where('member_id', $this->EE->session->userdata('member_id'));
			if ($q->num_rows()>0)
			{
				if ($q->row('credits_total') < $amount_avail)
				{
					$amount_avail = $q->row('credits_total');
				}
			}
		}
		
		return $amount_avail;
		
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
			$basepath = $this->EE->functions->remove_double_slashes(str_replace($match[0], '', $basepath));
		}

    	$vars = array();
    	$global_vars = array();
       
        switch ($ext_settings['ecommerce_solution'])
        {
        	case 'cartthrob':
        	default:
        		$this->EE->load->add_package_path(PATH_THIRD.'cartthrob/');
				$this->EE->load->model('cartthrob_settings_model');
				$cartthrob_config = $this->EE->cartthrob_settings_model->get_settings();
				$this->EE->load->remove_package_path(PATH_THIRD.'cartthrob/');
        		$this->EE->db->select('affiliate_commissions.*, referrals.screen_name AS referral_screen_name, title AS order_title')
        			->from('affiliate_commissions')
        			->where('affiliate_commissions.member_id', $this->EE->session->userdata('member_id'))
					->join('members AS referrals', 'affiliate_commissions.referral_id=referrals.member_id', 'left')
        			->join('channel_titles', 'affiliate_commissions.order_id=channel_titles.entry_id', 'left');
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
        
		   
		$i = 0;
        foreach ($query->result_array() as $row)
        {
           $vars['data'][$i]['date'] = $this->EE->localize->decode_date($date_format, $row['record_date']);
           $vars['data'][$i]['affiliate'] = "<a href=\"".BASE.AMP.'C=myaccount'.AMP.'id='.$row['member_id']."\">".$row['referrer_screen_name']."</a>";   
           $vars['data'][$i]['order'] = "<a href=\"".BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'entry_id='.$row['order_id']."\">".$row['order_title']."</a>";   
           $vars['data'][$i]['customer'] = ($row['referral_id']!=0)?"<a href=\"".BASE.AMP.'C=myaccount'.AMP.'id='.$row['referral_id']."\">".$row['referral_screen_name']."</a>":lang('guest');  
           $vars['data'][$i]['commission'] = $row['credits']; 
           $vars['data'][$i]['other1'] = '';    
           $vars[] = $row;
 			
        }
        
        $tagdata = $this->EE->TMPL->parse_variables($tagdata, $vars);

		if (($start==0 && $perpage > $query->num_rows()))
		{
        	$global_vars['total_commission_records'] = $query->num_rows();
 		}
 		else
 		{
 			
  			$this->EE->db->select("COUNT('*') AS count")
  				->from('affiliate_commissions')
				 ->where('member_id', $this->EE->session->userdata('member_id'));
	        
	        $q = $this->EE->db->get();
	        
	        $global_vars['total_commission_records']  = $q->row('count');
 		}
 		
 		$global_vars['balance'] = $this->balance();
 		$global_vars['withdraw_minimum'] = $ext_settings['withdraw_minimum'];
 		
 		
 		$tagdata = $this->EE->TMPL->parse_variables_row($tagdata, $global_vars);

        if ($global_vars['total_commission_records'] > $perpage)
        {
            $this->EE->load->library('pagination');

			$config['base_url']		= $basepath;
			$config['prefix']		= 'P';
			$config['total_rows'] 	= $global_vars['total_commission_records'];
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
                $tagdata = $paginate_tagdata.$tagdata;
                break;
            case 'both':
                $tagdata = $paginate_tagdata.$tagdata.$paginate_tagdata;
                break;
            case 'bottom':
            default:
                $tagdata = $tagdata.$paginate_tagdata;
        }
        
        
        return $tagdata;
        
	
    }
	
	
	



}
/* END */
?>