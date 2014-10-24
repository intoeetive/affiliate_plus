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

class Affiliate_plus_upd {

    var $version = AFFILIATE_PLUS_ADDON_VERSION;
    
    function __construct() { 
        // Make a local reference to the ExpressionEngine super object 
        $this->EE =& get_instance(); 
    } 
    
    function install() { 
  
        $this->EE->lang->loadfile('affiliate_plus');  
		
		$this->EE->load->dbforge(); 
        
        //----------------------------------------
		// EXP_MODULES
		// The settings column, Ellislab should have put this one in long ago.
		// No need for a seperate preferences table for each module.
		//----------------------------------------
		if ($this->EE->db->field_exists('settings', 'modules') == FALSE)
		{
			$this->EE->dbforge->add_column('modules', array('settings' => array('type' => 'TEXT') ) );
		}

        $data = array( 'module_name' => 'Affiliate_plus' , 'module_version' => $this->version, 'has_cp_backend' => 'y', 'has_publish_fields' => 'n'); 
        $this->EE->db->insert('modules', $data); 
        
        $data = array( 'class' => 'Affiliate_plus' , 'method' => 'register_hit' ); 
        $this->EE->db->insert('actions', $data); 
        
        $data = array( 'class' => 'Affiliate_plus' , 'method' => 'find_members' ); 
        $this->EE->db->insert('actions', $data); 
        
        $data = array( 'class' => 'Affiliate_plus' , 'method' => 'find_products' ); 
        $this->EE->db->insert('actions', $data); 
        
        $data = array( 'class' => 'Affiliate_plus' , 'method' => 'process_withdraw_request' ); 
        $this->EE->db->insert('actions', $data); 
        
        
        //exp_affiliate_rules
        $fields = array(
			'rule_id'							=> array('type' => 'INT',		'unsigned' => TRUE, 'auto_increment' => TRUE),
			'rule_title'						=> array('type' => 'VARCHAR',	'constraint'=> 150,	'default' => ''), 
			
			'rule_type'							=> array('type' => 'ENUM',		'constraint'=> "'open','restricted'",	'default' => 'open'),
			
			'rule_terminator'					=> array('type' => 'CHAR',		'constraint'=> 1,	'default' => 'n'),
			'discount_processing'				=> array('type' => 'ENUM',		'constraint'=> "'dividebyprice','dividebyqty','firstitem'",	'default' => 'dividebyprice'),
						
			'rule_participant_members'						=> array('type' => 'TEXT',		'default' => ''),
			'rule_participant_member_groups'				=> array('type' => 'TEXT',		'default' => ''),
			'rule_participant_member_categories'			=> array('type' => 'TEXT',		'default' => ''),
			'rule_participant_by_profile_field'				=> array('type' => 'TEXT',		'default' => ''),
			
			'rule_product_ids'					=> array('type' => 'TEXT',		'default' => ''),
			'rule_product_groups'				=> array('type' => 'TEXT',		'default' => ''),
			'rule_product_by_custom_field'		=> array('type' => 'TEXT',		'default' => ''),
            
            'rule_custom'		=> array('type' => 'TEXT',		'default' => ''),
									
			'commission_type'					=> array('type' => 'ENUM',		'constraint'=> "'percent','credit'",	'default' => 'percent'),
			'commission_rate'					=> array('type' => 'DECIMAL',	'constraint' => '7,2', 'default' => 0),
			
			'rule_require_purchase'				=> array('type' => 'CHAR',		'constraint'=> 1,	'default' => 'n'),
			
			'commission_aplied_maxamount'		=> array('type' => 'DECIMAL',	'constraint' => '7,2', 'default' => 0),
			'commission_aplied_maxpurchases'	=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'commission_aplied_maxtime'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			
			'rule_gateways'						=> array('type' => 'TEXT',		'default' => ''),
			
			'rule_priority'						=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 2),
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('rule_id', TRUE);
		$this->EE->dbforge->create_table('affiliate_rules', TRUE);
		

        
        //exp_affiliate_hits
        $fields = array(
			'hit_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'auto_increment' => TRUE),
			'member_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'ip_address'		=> array('type' => 'VARCHAR',	'constraint'=> 45,	'default' => ''),
			'referrer_id'		=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'referrer_server'	=> array('type' => 'VARCHAR',	'constraint'=> 250,	'default' => ''),
			'hit_date'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'register_date'		=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0)
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('hit_id', TRUE);
		$this->EE->dbforge->add_key('member_id');
		$this->EE->dbforge->add_key('referrer_id');
		$this->EE->dbforge->create_table('affiliate_hits', TRUE);
		
		//exp_affiliate_commissions
		$fields = array(
			'commission_id'		=> array('type' => 'INT',		'unsigned' => TRUE, 'auto_increment' => TRUE),
			'order_id'			=> array('type' => 'INT',		'default' => 0),
			'method'			=> array('type' => 'VARCHAR',	'constraint'=> 50,	'default' => ''),//carttrob, brilliantretail, store, simplecommerce, withdraw
			'rules_used'		=> array('type' => 'TEXT',		'default' => ''),
			'member_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'hit_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'referral_id'		=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'credits'			=> array('type' => 'DECIMAL',	'constraint' => '7,2', 'default' => 0),
			'credits_pending'	=> array('type' => 'DECIMAL',	'constraint' => '7,2', 'default' => 0), //for withdraw requests
			'record_date'		=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0)
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('commission_id', TRUE);
		$this->EE->dbforge->add_key('order_id');
		$this->EE->dbforge->add_key('method');
		$this->EE->dbforge->add_key('member_id');
		$this->EE->dbforge->add_key('hit_id');
		$this->EE->dbforge->add_key('referral_id');
		$this->EE->dbforge->create_table('affiliate_commissions', TRUE);
		
		
		//exp_affiliate_payouts
		$fields = array(
			'payout_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'auto_increment' => TRUE),
			'method'			=> array('type' => 'VARCHAR',	'constraint'=> 50,	'default' => 'other'),//paypal,masspay,bank,other
			'member_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'amount'			=> array('type' => 'DECIMAL',	'constraint' => '7,2', 'default' => 0),
			'transaction_id'	=> array('type' => 'VARCHAR',	'constraint'=> 50,	'default' => 'other'),
			'comment'			=> array('type' => 'TEXT',		'default' => ''),
			'payout_date'		=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0)
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('payout_id', TRUE);
		$this->EE->dbforge->add_key('member_id');
		$this->EE->dbforge->create_table('affiliate_payouts', TRUE);
		
        //notification templates
        $data = array( 
			'site_id' => $this->EE->config->item('site_id'), 
			'enable_template' => 'y', 
			'template_name' => 'affiliate_plus_withdraw_request_admin_notification', 
			'data_title'=> $this->EE->lang->line('withdraw_request_admin_notification_subject'), 
			'template_data'=> $this->EE->lang->line('withdraw_request_admin_notification_message') 
		); 
        $this->EE->db->insert('specialty_templates', $data); 
        
        
        return TRUE; 
        
    } 
    
    
    function uninstall() { 

        $this->EE->load->dbforge(); 
		
		$this->EE->db->select('module_id'); 
        $query = $this->EE->db->get_where('modules', array('module_name' => 'Affiliate_plus')); 
        
        $this->EE->db->where('module_id', $query->row('module_id')); 
        $this->EE->db->delete('module_member_groups'); 
        
        $this->EE->db->where('module_name', 'Affiliate_plus'); 
        $this->EE->db->delete('modules'); 
        
        $this->EE->db->where('class', 'Affiliate_plus'); 
        $this->EE->db->delete('actions'); 
        
        $this->EE->dbforge->drop_table('affiliate_rules');
        $this->EE->dbforge->drop_table('affiliate_hits');
        $this->EE->dbforge->drop_table('affiliate_commissions');
        $this->EE->dbforge->drop_table('affiliate_payouts');

        return TRUE; 
    } 
    
    function update($current='') 
	{ 
        $this->EE->load->dbforge(); 
		
		if ($current < 0.02)
        {
        	$data = array( 
				'site_id' => $this->EE->config->item('site_id'), 
				'enable_template' => 'y', 
				'template_name' => 'affiliate_plus_withdraw_request_admin_notification', 
				'data_title'=> $this->EE->lang->line('withdraw_request_admin_notification_subject'), 
				'template_data'=> $this->EE->lang->line('withdraw_request_admin_notification_message') 
			); 
	        $this->EE->db->insert('specialty_templates', $data); 
        }
        
        if ($current < 0.03)
        {
			if ($this->EE->db->field_exists('rule_terminator', 'affiliate_rules') == FALSE)
			{
				$this->EE->dbforge->add_column('affiliate_rules', array('rule_terminator' => array('type' => 'CHAR', 'constraint'=> 1, 'default' => 'n') ) );
			}
        }
        
        if ($current < 0.05)
        {
			if ($this->EE->db->field_exists('discount_processing', 'affiliate_rules') == FALSE)
			{
				$this->EE->dbforge->add_column('affiliate_rules', array('discount_processing' => array('type' => 'ENUM', 'constraint'=> "'dividebyprice','dividebyqty','firstitem'",	'default' => 'dividebyprice') ) );
			}
        }
        
        if ($current < 0.06)
        {
			if ($this->EE->db->field_exists('discount_processing', 'affiliate_commissions') == FALSE)
			{
				$this->EE->dbforge->add_column('affiliate_commissions', array('rules_used' => array('type' => 'TEXT',		'default' => '') ) );
			}
        }
        
        if ($current < 0.20)
        {
			if ($this->EE->db->field_exists('rule_custom', 'affiliate_rules') == FALSE)
			{
				$this->EE->dbforge->add_column('affiliate_rules', array('rule_custom' => array('type' => 'TEXT',		'default' => '') ) );
			}
        }
		
		return TRUE; 
    } 
	

}
/* END */
?>