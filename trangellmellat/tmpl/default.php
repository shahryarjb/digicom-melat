<?php
/**
 * @package     Joomla - > Site and Administrator payment info
 * @subpackage  com_Digicom
 * @subpackage 	trangell_Mellat
 * @copyright   trangell team => https://trangell.com
 * @copyright   Copyright (C) 20016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::_('jquery.framework');
JHTML::_('behavior.formvalidation');
$configs = JComponentHelper::getComponent('com_digicom')->params;
?>

<div class="digicom-payment-form">
	<form action="<?php echo $vars->urls; ?>" name="adminForm" id="adminForm" class="form-validate form-horizontal"  method="post">
		<div>
		
			<input type="hidden" name="RefId" value="<?php echo $vars->refid ?>" />
			<div class="form-actions">
					<input type="submit" name="submit"
						class="btn btn-success btn-large btn-lg"
						value="<?php echo 'پرداخت'; ?>" 
						alt="<?php echo $this->params->get('title'); ?>" />	
			</div>
		</div>
		<?php echo JHtml::_('form.token');?>
	</form>
</div>
