<ul class="tab_menu" id="tab_menu_tabs">
<li class="content_tab<?php if (in_array($this->input->get('method'), array('', 'index', 'rule_edit'))) echo ' current';?>"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=index'?>"><?=lang('commission_rules')?></a>  </li> 
<li class="content_tab<?php if (in_array($this->input->get('method'), array('settings'))) echo ' current';?>"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=settings'?>"><?=lang('settings')?></a>  </li> 
<li class="content_tab<?php if (in_array($this->input->get('method'), array('stats'))) echo ' current';?>"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=stats'?>"><?=lang('commission_stats')?></a>  </li> 
<li class="content_tab<?php if (in_array($this->input->get('method'), array('referrals'))) echo ' current';?>"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=referrals'?>"><?=lang('referred_members')?></a>  </li> 
<li class="content_tab<?php if (in_array($this->input->get('method'), array('payouts', 'process_payout', 'process_payout_form', 'view_payout'))) echo ' current';?>"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=payouts'?>"><?=lang('payout')?></a>  </li> 
<li class="content_tab<?php if (in_array($this->input->get('method'), array('notification_templates'))) echo ' current';?>"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=notification_templates'?>"><?=lang('notification_templates')?></a>  </li> 


</ul> 
<div class="clear_left shun"></div>