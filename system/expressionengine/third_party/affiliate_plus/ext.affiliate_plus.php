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

class Affiliate_plus_ext {

	var $name	     	= AFFILIATE_PLUS_ADDON_NAME;
	var $version 		= AFFILIATE_PLUS_ADDON_VERSION;
	var $description	= 'Referrals system that works well';
	var $settings_exist	= 'y';
	var $docs_url		= 'http://www.intoeetive.com/docs/affiliate_plus.html';
    
    var $settings 		= array();
    var $site_id		= 1;
    
	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 */
	function __construct($settings = '')
	{
		$this->EE =& get_instance();
		
		$this->settings = $settings;

		$this->EE->load->library('affiliate_plus_lib');  
	}
    
    /**
     * Activate Extension
     */
    function activate_extension()
    {
        
        $hooks = array(
    		//update hit
			array(
    			'hook'		=> 'member_member_register',
    			'method'	=> 'update_hit_record',
    			'priority'	=> 5
    		),
    		array(
    			'hook'		=> 'zoo_visitor_register_end',
    			'method'	=> 'update_hit_record',
    			'priority'	=> 5
    		),
    		array(
    			'hook'		=> 'user_register_end',
    			'method'	=> 'update_hit_record',
    			'priority'	=> 5
    		),
    		array(
    			'hook'		=> 'cartthrob_create_member',
    			'method'	=> 'update_hit_record_ct',
    			'priority'	=> 5
    		),
    		//record commission
    		array(
    			'hook'		=> 'cartthrob_on_authorize',
    			'method'	=> 'cartthrob_purchase',
    			'priority'	=> 10
    		),
    		array(
    			'hook'		=> 'simple_commerce_perform_actions_end',
    			'method'	=> 'simplecommerce_purchase',
    			'priority'	=> 10
    		),
    		array(
    			'hook'		=> 'store_order_payment_end',
    			'method'	=> 'store_purchase',
    			'priority'	=> 10
    		),
    		
    		
            
    	);
    	
        foreach ($hooks AS $hook)
    	{
    		$data = array(
        		'class'		=> __CLASS__,
        		'method'	=> $hook['method'],
        		'hook'		=> $hook['hook'],
        		'settings'	=> '',
        		'priority'	=> $hook['priority'],
        		'version'	=> $this->version,
        		'enabled'	=> 'y'
        	);
            $this->EE->db->insert('extensions', $data);
    	}	
        
    }
    
    /**
     * Update Extension
     */
    function update_extension($current = '')
    {
    	if ($current == '' OR $current == $this->version)
    	{
    		return FALSE;
    	}
    	
    	$this->EE->db->where('class', __CLASS__);
    	$this->EE->db->update(
    				'extensions', 
    				array('version' => $this->version)
    	);
    }
    
    
    /**
     * Disable Extension
     */
    function disable_extension()
    {
    	$this->EE->db->where('class', __CLASS__);
    	$this->EE->db->delete('extensions');
    }
    
    
    
    function settings()
    {
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=settings');
		return true;
    }
    

    
	function update_hit_record($data, $member_id)
	{
		return $this->EE->affiliate_plus_lib->update_hit_record($member_id);

    }
    
    function update_hit_record_ct($data, $this_obj)
    {
    	return $this->EE->affiliate_plus_lib->update_hit_record($data['member_id']);
    }
    
    
    
    
    
    function store_purchase($order, $payment)
    {
		$referrer_data = $this->EE->affiliate_plus_lib->get_referrer_data();
		if ($referrer_data===false) return;
		
		var_dump($order);
		var_dump($payment);
		
		// the commission is calculated based on items cost only (shipping and taxed not included)
		// plus, some items have comission rate set specificly for them
		// so we need to parse what's in the order
		
		if ($order['is_order_paid']!=true) return false;

		/*
		$purchased_items = array();
		foreach ($order_data['items'] as $item)
		{
			$purchased_items[$item['product_id']] = $item['product_id'];
		}
		if (empty($purchased_items))
		{
			return;
		}
		*/
		if ($order['order_total_val']==0) return false;
		
		//get all rules
		$rules_q = $this->EE->db->select()
					->from('affiliate_rules')
					->get();
		if ($rules_q->num_rows()==0) return false;
		$rules = array();
		
		foreach ($rules_q->result_array() as $key => $row)
		{
			$rules[] = $row;
		}	
		$priorities = array();
		foreach ($rules as $key => $row)
		{
		    $priorities[$key] = $row['rule_priority'];
		}
		array_multisort($priorities, SORT_DESC, $rules);
		
		if (empty($rules)) return false;
		
		
		//calculate order discout
		$order_discount = $order['order_discount_val'];
		
		//loop through the rules
		//we'll filter out the rules that can't be applied
		$commission_amount = 0;
		
		$referrer_group_id = 0;
		
		foreach ($rules as $id=>$row)
		{
			
			if ($row['commission_rate']==0) continue;
			
			//member checks
			
			if ($row['rule_participant_members']!='')
			{
				$arr = unserialize($row['rule_participant_members']);
				if (!empty($arr))
				{
					if (!in_array($referrer_data['referrer_id'], $arr))
					{
						unset($rules[$id]);
						continue;
					}
				}
			}
			//echo 'rule_participant_members';
			
			if ($row['rule_participant_member_groups']!='')
			{
				$arr = unserialize($row['rule_participant_member_groups']);
				if (!empty($arr))
				{
					if ($referrer_group_id==0)
					{
						$q = $this->EE->db->select('group_id')
								->from('members')
								->where('member_id', $referrer_data['referrer_id'])
								->get();
						$referrer_group_id = $q->row('group_id');
					}
					if (!in_array($referrer_group_id, $arr))
					{
						unset($rules[$id]);
						continue;
					}
				}
			}
			//echo 'rule_participant_member_groups';
			
			if ($row['rule_participant_member_categories']!='')
			{
				$arr = unserialize($row['rule_participant_member_categories']);
				if (!empty($arr))
				{
					if ($this->EE->db->table_exists('category_members') != FALSE)
					{
						$q = $this->EE->db->select('cat_id')
								->from('category_members')
								->where('member_id', $referrer_data['referrer_id'])
								->where_in('cat_id', $arr)
								->get();
						if ($q->num_rows()==0)
						{
							unset($rules[$id]);
							continue;
						}
					}
				}
			}
			//echo 'rule_participant_member_categories';
			
			if ($row['rule_participant_by_profile_field']!='')
			{
				$field = 'm_field_id_'.$row['rule_participant_by_profile_field'];
				$q = $this->EE->db->select($field)
						->from('member_data')
						->where('member_id', $referrer_data['referrer_id'])
						->get();
				if (!in_array($q->row("$field"), array("y", "Y", "yes", "Yes", "1")))
				{
					unset($rules[$id]);
					continue;
				}
			}
			//echo 'rule_participant_by_profile_field';
			
			//prev purchases rules check
			
			if ($row['commission_aplied_maxamount']>0 && $this->EE->session->userdata('member_id')!=0)
			{
				$q = $this->EE->db->select("SUM(order_subtotal) AS prev_purchases_total")
						->from('store_orders')
						->where('member_id', $this->EE->session->userdata('member_id'))
						->where('order_paid_date != NULL')
						->get();
				$purchases_total = 0;
				if ($q->num_rows() > 0)
				{
					$purchases_total += $q->row("prev_purchases_total");
					if ($q->row("prev_purchases_total")>=$row['commission_aplied_maxamount'])
					{
						unset($rules[$id]);
						continue;
					}
				}
			}
			//echo 'commission_aplied_maxamount';
			
			
			if ($row['commission_aplied_maxpurchases']>0 && $this->EE->session->userdata('member_id')!=0)
			{
				$q = $this->EE->db->select('COUNT(*) AS purchases_count')
						->from('store_orders')
						->where('author_id', $this->EE->session->userdata('member_id'))
						->where('order_paid_date != NULL')
						->get();
				if ($q->num_rows() > 0 && $q->row('purchases_count') > 0)
				{		
					if ($q->row('purchases_count') >= $row['commission_aplied_maxpurchases'])
					{
						unset($rules[$id]);
						continue;
					}
				}
			}
			//echo 'commission_aplied_maxpurchases';
			
			if ($row['commission_aplied_maxtime']>0)
			{
				if (($referrer_data['hit_date']+$row['commission_aplied_maxtime']) > $this->EE->localize->now)
				{
					unset($rules[$id]);
					continue;
				}
			}
			//echo 'commission_aplied_maxtime';
			
			//gateway check
			if ($row['rule_gateways']!='')
			{
				$arr = unserialize($row['rule_gateways']);
				if (!empty($arr))
				{
					if (!in_array($order['payment_method'], $arr))
					{
						continue;
					}
				}
			} 
			
			//echo "basic check passed";

			//per-product checks
			foreach ($order['items'] as $item_row_id=>$item)
			{
			
			
				if ($row['rule_product_ids']!='')
				{
					$arr = unserialize($row['rule_product_ids']);
					if (!empty($arr))
					{
						//if (count(array_intersect($purchased_items, $arr)) == 0)
						if (!in_array($item['entry_id'], $arr))
						{
							continue;
						}
					}
				}
			//echo 'rule_product_ids';
			
				if ($row['rule_product_groups']!='')
				{
					$arr = unserialize($row['rule_product_groups']);
					if (!empty($arr))
					{
						$q = $this->EE->db->select('channel_id')
								->from('channel_titles')
								->where('entry_id', $item['entry_id'])
								->get();
						/*$purchased_items_groups = array();
						foreach ($q->result_array() as $row)
						{
							$purchased_items_groups[] = $row['channel_id'];
						}
						if (count(array_intersect($purchased_items_groups, $arr)) == 0)*/
						if (!in_array($q->row('channel_id'), $arr))
						{
							continue;
						}
					}
				}
			//echo 'rule_product_groups';
				if ($row['rule_product_by_custom_field']!='')
				{
					$field = 'field_id_'.$row['rule_product_by_custom_field'];
					$q = $this->EE->db->select($field)
							->from('channel_data')
							->where_in('entry_id', $item['entry_id'])
							->get();
					if (!in_array($q->row("$field"), array("y", "Y", "yes", "Yes", "1")))
					{
						continue;
					}
				}
			
			//echo 'rule_product_by_custom_field';
				
				if ($row['rule_require_purchase']=='y')
				{
					$q = $this->EE->db->select('order_id')
							->from('store_order_items')
							->join('store_orders', 'store_order_items.order_id=store_orders.order_id', 'left')
							->where('store_order_items.entry_id', $item['entry_id'])
							->where('author_id', $referrer_data['referrer_id'])
							->where('order_paid_date != NULL')
							->get();
					if ($q->num_rows()==0)
					{
						continue;
					}
				}
				//echo 'rule_require_purchase';
				//passed all checks, just about to apply the rule to the product
				
				//get the amount
				$paid_for_product = $item['price_val'];
				
				//divide order discount_processing
				if ($order_discount!=0)
				{
					switch ($row['commission_type'])
					{
						case 'dividebyprice':
							$divided_discount = ($order_discount / $order['order_subtotal_val']) * $paid_for_product;
							$paid_for_product -= $divided_discount;
							break;
						case 'dividebyqty':
							$divided_discount = ($order_discount / count($items));
							$paid_for_product -= $divided_discount;
							break;
						case 'firstitem':
							break;
					}
				}					
				
				if ($paid_for_product<=0) return false;
				
				//and check if it's not too big...
				if (isset($purchases_total))
				{
					if ($purchases_total>=$row['commission_aplied_maxamount'])
					{
						unset($rules[$id]);
						continue 2; //this rule cannot be applied anymore, go no next one
					}
					$purchases_total += $paid_for_product;
					if ($purchases_total>=$row['commission_aplied_maxamount'])
					{
						$paid_for_product = $row['commission_aplied_maxamount'] - $purchases_total;
					}
				}
				
				//so looks like all is fine? calculate the commission
				
				//echo 'looks like all is fine';
				switch ($row['commission_type'])
				{
					case 'credit':
						$commission_amount += $row['commission_rate'];
						break;			
					case 'percent':
					default:
						$commission_amount += $paid_for_product*$row['commission_rate']/100;
						break;
				}


			}
			
			//if the rule it terminator, do not process other rules
			if ($row['rule_terminator']=='y')
			{
				break;
			}
			
				
		}

		if ($commission_amount==0) return false;
		
		
		//ok, time to add the commission record!
		$insert = array(
			'order_id'			=> $order['order_id'],
			'method'			=> 'store',
			'member_id'			=> $referrer_data['referrer_id'],
			'hit_id'			=> $referrer_data['hit_id'],
			'referral_id'		=> $this->EE->session->userdata('member_id'),
			'credits'			=> $commission_amount,
			'record_date'		=> $this->EE->localize->now
		);
		$this->EE->db->insert('affiliate_commissions', $insert);
		
		if (isset($this->settings['devdemon_credits']) && $this->settings['devdemon_credits']=='y')
		{
			$credits_action_q = $this->EE->db->select('action_id, enabled')
									->from('exp_credits_actions')
									->where('action_name', 'affiliate_plus_reward')
									->get();
			if ($credits_action_q->num_rows()>0 && $credits_action_q->row('enabled')==1)
	    	{
				$pData = array(
					'action_id'			=> $credits_action_q->row('action_id'),
					'site_id'			=> $this->EE->config->item('site_id'),
					'credits'			=> $commission_amount,
					'receiver'			=> $referrer_data['referrer_id'],
					'item_id'			=> $referrer_data['hit_id'],
					'item_parent_id' 	=> $this->EE->session->userdata('member_id')
				);
				
				$this->EE->affiliate_plus_lib->_save_credits($pData);
			}
		}
    
    }
    
    
    
    
    
    
    function simplecommerce_purchase($this_obj, $order_data)
    {

		
		$referrer_data = $this->EE->affiliate_plus_lib->get_referrer_data();
		if ($referrer_data===false) return;
		
		// the commission is calculated based on items cost only (shipping and taxed not included)
		// plus, some items have comission rate set specificly for them
		// so we need to parse what's in the order
		
		
		//get all rules
		$rules_q = $this->EE->db->select()
					->from('affiliate_rules')
					->get();
		if ($rules_q->num_rows()==0) return false;
		$rules = array();
		
		foreach ($rules_q->result_array() as $key => $row)
		{
			$rules[] = $row;
		}	
		$priorities = array();
		foreach ($rules as $key => $row)
		{
		    $priorities[$key] = $row['rule_priority'];
		}
		array_multisort($priorities, SORT_DESC, $rules);
		
		if (empty($rules)) return false;
		
		
		//loop through the rules
		//we'll filter out the rules that can't be applied
		$commission_amount = 0;
		
		$referrer_group_id = 0;
		
		foreach ($rules as $id=>$row)
		{
			
			if ($row['commission_rate']==0) continue;
			
			//member checks
			
			if ($row['rule_participant_members']!='')
			{
				$arr = unserialize($row['rule_participant_members']);
				if (!empty($arr))
				{
					if (!in_array($referrer_data['referrer_id'], $arr))
					{
						unset($rules[$id]);
						continue;
					}
				}
			}
			//echo 'rule_participant_members';
			
			if ($row['rule_participant_member_groups']!='')
			{
				$arr = unserialize($row['rule_participant_member_groups']);
				if (!empty($arr))
				{
					if ($referrer_group_id==0)
					{
						$q = $this->EE->db->select('group_id')
								->from('members')
								->where('member_id', $referrer_data['referrer_id'])
								->get();
						$referrer_group_id = $q->row('group_id');
					}
					if (!in_array($referrer_group_id, $arr))
					{
						unset($rules[$id]);
						continue;
					}
				}
			}
			//echo 'rule_participant_member_groups';
			
			if ($row['rule_participant_member_categories']!='')
			{
				$arr = unserialize($row['rule_participant_member_categories']);
				if (!empty($arr))
				{
					if ($this->EE->db->table_exists('category_members') != FALSE)
					{
						$q = $this->EE->db->select('cat_id')
								->from('category_members')
								->where('member_id', $referrer_data['referrer_id'])
								->where_in('cat_id', $arr)
								->get();
						if ($q->num_rows()==0)
						{
							unset($rules[$id]);
							continue;
						}
					}
				}
			}
			//echo 'rule_participant_member_categories';
			
			if ($row['rule_participant_by_profile_field']!='')
			{
				$field = 'm_field_id_'.$row['rule_participant_by_profile_field'];
				$q = $this->EE->db->select($field)
						->from('member_data')
						->where('member_id', $referrer_data['referrer_id'])
						->get();
				if (!in_array($q->row("$field"), array("y", "Y", "yes", "Yes", "1")))
				{
					unset($rules[$id]);
					continue;
				}
			}
			//echo 'rule_participant_by_profile_field';
			
			//prev purchases rules check
			
			if ($row['commission_aplied_maxamount']>0)
			{
				$q = $this->EE->db->select("SUM(item_cost) AS prev_purchases_total")
						->from('simple_commerce_purchases')
						->where('member_id', $this->EE->session->userdata('member_id'))
						->get();
				$purchases_total = 0;
				if ($q->num_rows() > 0)
				{
					$purchases_total += $q->row("prev_purchases_total");
					if ($q->row("prev_purchases_total")>=$row['commission_aplied_maxamount'])
					{
						unset($rules[$id]);
						continue;
					}
				}
			}
			//echo 'commission_aplied_maxamount';
			
			if ($row['commission_aplied_maxpurchases']>0 && $this->EE->session->userdata('member_id')!=0)
			{
				$q = $this->EE->db->select('COUNT(*) AS purchases_count')
						->from('simple_commerce_purchases')
						->where('member_id', $this->EE->session->userdata('member_id'))
						->get();
				if ($q->num_rows() > 0 && $q->row('purchases_count') > 0)
				{		
					if ($q->row('purchases_count') >= $row['commission_aplied_maxpurchases'])
					{
						unset($rules[$id]);
						continue;
					}
				}
			}
			//echo 'commission_aplied_maxpurchases';
			
			if ($row['commission_aplied_maxtime']>0)
			{
				if (($referrer_data['hit_date']+$row['commission_aplied_maxtime']) > $this->EE->localize->now)
				{
					unset($rules[$id]);
					continue;
				}
			}
			//echo 'commission_aplied_maxtime';
			
			//echo "basic check passed";
			
			//per-product checks
			
			if ($row['rule_product_ids']!='')
			{
				$arr = unserialize($row['rule_product_ids']);
				if (!empty($arr))
				{
					//if (count(array_intersect($purchased_items, $arr)) == 0)
					if (!in_array($order_data['entry_id'], $arr))
					{
						continue;
					}
				}
			}
			//echo 'rule_product_ids';
		
			if ($row['rule_product_groups']!='')
			{
				$arr = unserialize($row['rule_product_groups']);
				if (!empty($arr))
				{
					$q = $this->EE->db->select('channel_id')
							->from('channel_titles')
							->where('entry_id', $order_data['entry_id'])
							->get();
					/*$purchased_items_groups = array();
					foreach ($q->result_array() as $row)
					{
						$purchased_items_groups[] = $row['channel_id'];
					}
					if (count(array_intersect($purchased_items_groups, $arr)) == 0)*/
					if (!in_array($q->row('channel_id'), $arr))
					{
						continue;
					}
				}
			}
		//echo 'rule_product_groups';
			if ($row['rule_product_by_custom_field']!='')
			{
				$field = 'field_id_'.$row['rule_product_by_custom_field'];
				$q = $this->EE->db->select($field)
						->from('channel_data')
						->where_in('entry_id', $order_data['entry_id'])
						->get();
				if (!in_array($q->row("$field"), array("y", "Y", "yes", "Yes", "1")))
				{
					continue;
				}
			}
		
			//echo 'rule_product_by_custom_field';
			
			if ($row['rule_require_purchase']=='y')
			{
				$q = $this->EE->db->select('purchase_id')
						->from('simple_commerce_purchases')
						->where('item_id', $order_data['item_id'])
						->where('member_id', $referrer_data['referrer_id'])
						->get();
				if ($q->num_rows()==0)
				{
					continue;
				}
			}
			//echo 'rule_require_purchase';
			//passed all checks, just about to apply the rule to the product
			
			//get the amount
			$paid_for_product = $order_data['item_cost'];

			if ($paid_for_product<=0) return false;
			
			//and check if it's not too big...
			if (isset($purchases_total))
			{
				if ($purchases_total>=$row['commission_aplied_maxamount'])
				{
					unset($rules[$id]);
					continue 2; //this rule cannot be applied anymore, go no next one
				}
				$purchases_total += $paid_for_product;
				if ($purchases_total>=$row['commission_aplied_maxamount'])
				{
					$paid_for_product = $row['commission_aplied_maxamount'] - $purchases_total;
				}
			}
			
			//so looks like all is fine? calculate the commission
			
			//echo 'looks like all is fine';
			switch ($row['commission_type'])
			{
				case 'credit':
					$commission_amount += $row['commission_rate'];
					break ($row['rule_terminator']=='y')?2:1;	
				case 'percent':
				default:
					$commission_amount += $paid_for_product*$row['commission_rate']/100;
					break ($row['rule_terminator']=='y')?2:1;	
			}

				
		}

		if ($commission_amount==0) return false;
		
		
		//ok, time to add the commission record!
		$insert = array(
			'order_id'			=> $order_data['purchase_id'],
			'method'			=> 'carttrob',
			'member_id'			=> $referrer_data['referrer_id'],
			'hit_id'			=> $referrer_data['hit_id'],
			'referral_id'		=> $this->EE->session->userdata('member_id'),
			'credits'			=> $commission_amount,
			'record_date'		=> $this->EE->localize->now
		);
		$this->EE->db->insert('affiliate_commissions', $insert);
		
		if (isset($this->settings['devdemon_credits']) && $this->settings['devdemon_credits']=='y')
		{
			$credits_action_q = $this->EE->db->select('action_id, enabled')
									->from('exp_credits_actions')
									->where('action_name', 'affiliate_plus_reward')
									->get();
			if ($credits_action_q->num_rows()>0 && $credits_action_q->row('enabled')==1)
	    	{
				$pData = array(
					'action_id'			=> $credits_action_q->row('action_id'),
					'site_id'			=> $this->EE->config->item('site_id'),
					'credits'			=> $commission_amount,
					'receiver'			=> $referrer_data['referrer_id'],
					'item_id'			=> $referrer_data['hit_id'],
					'item_parent_id' 	=> $this->EE->session->userdata('member_id')
				);
				
				$this->EE->affiliate_plus_lib->_save_credits($pData);
			}
		}
    
    }
    
    
    
    
    function cartthrob_purchase()
    {
		$referrer_data = $this->EE->affiliate_plus_lib->get_referrer_data();
		if ($referrer_data===false) return;
		
		// the commission is calculated based on items cost only (shipping and taxed not included)
		// plus, some items have comission rate set specificly for them
		// so we need to parse what's in the order
		
		$order_data = $this->EE->cartthrob->cart->order(); 
		if ($order_data['auth']['authorized']!=true) return false;

		/*
		$purchased_items = array();
		foreach ($order_data['items'] as $item)
		{
			$purchased_items[$item['product_id']] = $item['product_id'];
		}
		if (empty($purchased_items))
		{
			return;
		}
		*/
		if ($order_data['subtotal']==0) return false;
		

		$this->EE->load->add_package_path(PATH_THIRD.'cartthrob/');
		$this->EE->load->model('cartthrob_settings_model');
		$cartthrob_config = $this->EE->cartthrob_settings_model->get_settings();
		$this->EE->load->remove_package_path(PATH_THIRD.'cartthrob/');
		
		//get all rules
		$rules_q = $this->EE->db->select()
					->from('affiliate_rules')
					->get();
		if ($rules_q->num_rows()==0) return false;
		$rules = array();
		
		foreach ($rules_q->result_array() as $key => $row)
		{
			$rules[] = $row;
		}	
		$priorities = array();
		foreach ($rules as $key => $row)
		{
		    $priorities[$key] = $row['rule_priority'];
		}
		array_multisort($priorities, SORT_DESC, $rules);
		
		if (empty($rules)) return false;
		
		
		//calculate order discout
		$order_discount = 0;
		if (isset($order_data['discounts']) && !empty($order_data['discounts']))
		{
			//individual item discount
			foreach ($order_data['discounts'] as $discount)
			{
				$order_discount += $discount['amount'];
			}
		}
		
		//loop through the rules
		//we'll filter out the rules that can't be applied
		$commission_amount = 0;
		
		$referrer_group_id = 0;
		
		foreach ($rules as $id=>$row)
		{
			
			if ($row['commission_rate']==0) continue;
			
			//member checks
			
			if ($row['rule_participant_members']!='')
			{
				$arr = unserialize($row['rule_participant_members']);
				if (!empty($arr))
				{
					if (!in_array($referrer_data['referrer_id'], $arr))
					{
						unset($rules[$id]);
						continue;
					}
				}
			}
			//echo 'rule_participant_members';
			
			if ($row['rule_participant_member_groups']!='')
			{
				$arr = unserialize($row['rule_participant_member_groups']);
				if (!empty($arr))
				{
					if ($referrer_group_id==0)
					{
						$q = $this->EE->db->select('group_id')
								->from('members')
								->where('member_id', $referrer_data['referrer_id'])
								->get();
						$referrer_group_id = $q->row('group_id');
					}
					if (!in_array($referrer_group_id, $arr))
					{
						unset($rules[$id]);
						continue;
					}
				}
			}
			//echo 'rule_participant_member_groups';
			
			if ($row['rule_participant_member_categories']!='')
			{
				$arr = unserialize($row['rule_participant_member_categories']);
				if (!empty($arr))
				{
					if ($this->EE->db->table_exists('category_members') != FALSE)
					{
						$q = $this->EE->db->select('cat_id')
								->from('category_members')
								->where('member_id', $referrer_data['referrer_id'])
								->where_in('cat_id', $arr)
								->get();
						if ($q->num_rows()==0)
						{
							unset($rules[$id]);
							continue;
						}
					}
				}
			}
			//echo 'rule_participant_member_categories';
			
			if ($row['rule_participant_by_profile_field']!='')
			{
				$field = 'm_field_id_'.$row['rule_participant_by_profile_field'];
				$q = $this->EE->db->select($field)
						->from('member_data')
						->where('member_id', $referrer_data['referrer_id'])
						->get();
				if (!in_array($q->row("$field"), array("y", "Y", "yes", "Yes", "1")))
				{
					unset($rules[$id]);
					continue;
				}
			}
			//echo 'rule_participant_by_profile_field';
			
			//prev purchases rules check
			
			if ($row['commission_aplied_maxamount']>0 && $this->EE->session->userdata('member_id')!=0)
			{
				$field = 'field_id_'.$cartthrob_config['orders_total_field'];
				$q = $this->EE->db->select("SUM($field) AS prev_purchases_total")
						->from('channel_data')
						->where('author_id', $this->EE->session->userdata('member_id'))
						->where_in('channel_id', $cartthrob_config['product_channels'])
						->where('status', $cartthrob_config['orders_default_status'])
						->get();
				$purchases_total = 0;
				if ($q->num_rows() > 0)
				{
					$purchases_total += $q->row("prev_purchases_total");
					if ($q->row("prev_purchases_total")>=$row['commission_aplied_maxamount'])
					{
						unset($rules[$id]);
						continue;
					}
				}
			}
			//echo 'commission_aplied_maxamount';
			
			
			if ($row['commission_aplied_maxpurchases']>0 && $this->EE->session->userdata('member_id')!=0)
			{
				$q = $this->EE->db->select('COUNT(*) AS purchases_count')
						->from('channel_titles')
						->where('author_id', $this->EE->session->userdata('member_id'))
						->where_in('channel_id', $cartthrob_config['product_channels'])
						->where('status', $cartthrob_config['orders_default_status'])
						->get();
				if ($q->num_rows() > 0 && $q->row('purchases_count') > 0)
				{		
					if ($q->row('purchases_count') >= $row['commission_aplied_maxpurchases'])
					{
						unset($rules[$id]);
						continue;
					}
				}
			}
			//echo 'commission_aplied_maxpurchases';
			
			if ($row['commission_aplied_maxtime']>0)
			{
				if (($referrer_data['hit_date']+$row['commission_aplied_maxtime']) > $this->EE->localize->now)
				{
					unset($rules[$id]);
					continue;
				}
			}
			//echo 'commission_aplied_maxtime';
			
			//gateway check
			if ($row['rule_gateways']!='')
			{
				$arr = unserialize($row['rule_gateways']);
				if (!empty($arr))
				{
					if (!in_array($order_data['payment_gateway'], $arr))
					{
						continue;
					}
				}
			} 
			
			//echo "basic check passed";

			//per-product checks
			foreach ($order_data['items'] as $item_row_id=>$item)
			{
			
			
				if ($row['rule_product_ids']!='')
				{
					$arr = unserialize($row['rule_product_ids']);
					if (!empty($arr))
					{
						//if (count(array_intersect($purchased_items, $arr)) == 0)
						if (!in_array($item['product_id'], $arr))
						{
							continue;
						}
					}
				}
			//echo 'rule_product_ids';
			
				if ($row['rule_product_groups']!='')
				{
					$arr = unserialize($row['rule_product_groups']);
					if (!empty($arr))
					{
						$q = $this->EE->db->select('channel_id')
								->from('channel_titles')
								->where('entry_id', $item['product_id'])
								->get();
						/*$purchased_items_groups = array();
						foreach ($q->result_array() as $row)
						{
							$purchased_items_groups[] = $row['channel_id'];
						}
						if (count(array_intersect($purchased_items_groups, $arr)) == 0)*/
						if (!in_array($q->row('channel_id'), $arr))
						{
							continue;
						}
					}
				}
			//echo 'rule_product_groups';
				if ($row['rule_product_by_custom_field']!='')
				{
					$field = 'field_id_'.$row['rule_product_by_custom_field'];
					$q = $this->EE->db->select($field)
							->from('channel_data')
							->where_in('entry_id', $item['product_id'])
							->get();
					if (!in_array($q->row("$field"), array("y", "Y", "yes", "Yes", "1")))
					{
						continue;
					}
				}
			
			//echo 'rule_product_by_custom_field';
				
				if ($row['rule_require_purchase']=='y')
				{
					$q = $this->EE->db->select('order_id')
							->from('cartthrob_order_items')
							->join('channel_titles', 'cartthrob_order_items.order_id=channel_titles.entry_id', 'left')
							->where('cartthrob_order_items.entry_id', $item['product_id'])
							->where('author_id', $referrer_data['referrer_id'])
							->where('status', $cartthrob_config['orders_default_status'])
							->get();
					if ($q->num_rows()==0)
					{
						continue;
					}
				}
				//echo 'rule_require_purchase';
				//passed all checks, just about to apply the rule to the product
				
				//get the amount
				$paid_for_product = $item['price'];
				
				if (isset($item['discounts']) && !empty($item['discounts']))
				{
					//individual item discount
					foreach ($item['discounts'] as $discount)
					{
						$paid_for_product -= $discount['amount'];
					}
				}
				
				//divide order discount_processing
				if ($order_discount!=0)
				{
					switch ($row['commission_type'])
					{
						case 'dividebyprice':
							$divided_discount = ($order_discount / $order_data['subtotal']) * $paid_for_product;
							$paid_for_product -= $divided_discount;
							break;
						case 'dividebyqty':
							$divided_discount = ($order_discount / count($items));
							$paid_for_product -= $divided_discount;
							break;
						case 'firstitem':
							break;
					}
				}					
				
				if ($paid_for_product<=0) return false;
				
				//and check if it's not too big...
				if (isset($purchases_total))
				{
					if ($purchases_total>=$row['commission_aplied_maxamount'])
					{
						unset($rules[$id]);
						continue 2; //this rule cannot be applied anymore, go no next one
					}
					$purchases_total += $paid_for_product;
					if ($purchases_total>=$row['commission_aplied_maxamount'])
					{
						$paid_for_product = $row['commission_aplied_maxamount'] - $purchases_total;
					}
				}
				
				//so looks like all is fine? calculate the commission
				
				//echo 'looks like all is fine';
				switch ($row['commission_type'])
				{
					case 'credit':
						$commission_amount += $row['commission_rate'];
						break;			
					case 'percent':
					default:
						$commission_amount += $paid_for_product*$row['commission_rate']/100;
						break;
				}


			}
			
			//if the rule it terminator, do not process other rules
			if ($row['rule_terminator']=='y')
			{
				break;
			}
			
				
		}

		if ($commission_amount==0) return false;
		
		
		//ok, time to add the commission record!
		$insert = array(
			'order_id'			=> $order_data['order_id'],
			'method'			=> 'carttrob',
			'member_id'			=> $referrer_data['referrer_id'],
			'hit_id'			=> $referrer_data['hit_id'],
			'referral_id'		=> $this->EE->session->userdata('member_id'),
			'credits'			=> $commission_amount,
			'record_date'		=> $this->EE->localize->now
		);
		$this->EE->db->insert('affiliate_commissions', $insert);
		
		if (isset($this->settings['devdemon_credits']) && $this->settings['devdemon_credits']=='y')
		{
			$credits_action_q = $this->EE->db->select('action_id, enabled')
									->from('exp_credits_actions')
									->where('action_name', 'affiliate_plus_reward')
									->get();
			if ($credits_action_q->num_rows()>0 && $credits_action_q->row('enabled')==1)
	    	{
				$pData = array(
					'action_id'			=> $credits_action_q->row('action_id'),
					'site_id'			=> $this->EE->config->item('site_id'),
					'credits'			=> $commission_amount,
					'receiver'			=> $referrer_data['referrer_id'],
					'item_id'			=> $referrer_data['hit_id'],
					'item_parent_id' 	=> $this->EE->session->userdata('member_id')
				);
				
				$this->EE->affiliate_plus_lib->_save_credits($pData);
			}
		}
    
    }
   
    
  

}
// END CLASS
