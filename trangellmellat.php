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

require_once(dirname(__FILE__) . '/trangellmellat/helper.php');
if (!class_exists ('checkHack')) {
	require_once( dirname(__FILE__) . '/trangellmellat/trangell_inputcheck.php');
}

class plgDigiCom_PayTrangellMellat extends JPlugin
{
	protected $autoloadLanguage = true;

	function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->responseStatus= array (
			'Completed' => 'A',
			'Pending' 	=> 'P',
			'Failed' 		=> 'P',
			'Denied' 		=> 'P',
			'Refunded'	=> 'RF'
		);
	}
	
	public function onDigicomSidebarMenuItem()
	{
		$pluginid = $this->getPluginId('trangellmellat','digicom_pay','plugin');
		$params 	= $this->params;
		$link 		= JRoute::_("index.php?option=com_plugins&client_id=0&task=plugin.edit&extension_id=".$pluginid);
		return '<a target="_blank" href="' . $link . '" title="trangellmellat" id="plugin-'.$pluginid.'">' . 'trangellmellat' . '</a>';
	}
	
	function getPluginId($element,$folder, $type)
	{
	    $db = JFactory::getDBO();
	    $query = $db->getQuery(true);
	    $query
	        ->select($db->quoteName('a.extension_id'))
	        ->from($db->quoteName('#__extensions', 'a'))
	        ->where($db->quoteName('a.element').' = '.$db->quote($element))
	        ->where($db->quoteName('a.folder').' = '.$db->quote($folder))
	        ->where($db->quoteName('a.type').' = '.$db->quote($type));

	    $db->setQuery($query);
	    $db->execute();
	    if($db->getNumRows()){
	        return $db->loadResult();
	    }
	    return false;
	}

	function buildLayoutPath($layout)
	{
		if(empty($layout)) $layout = "default";
		$core_file 	= dirname(__FILE__) . '/' . $this->_name . '/tmpl/' . $layout . '.php';
		return $core_file;
	}

	function buildLayout($vars, $layout = 'default' )
	{
		ob_start();
		$layout = $this->buildLayoutPath($layout);
		include($layout);
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	function onDigicom_PayGetHTML($vars,$pg_plugin)
	{
		if($pg_plugin != $this->_name) return;
		$vars->custom_name= $this->params->get( 'plugin_name' );
		$configs = JComponentHelper::getComponent('com_digicom')->params;
		//$vars->custom_email=$this->params->get( 'plugin_mail' );
		$price = intval(str_replace(".","",plgDigiCom_PayTrangellMellatHelper::getPayerPrice($vars->order_id)));
		//=========================================================
		$app	= JFactory::getApplication();
		$dateTime = JFactory::getDate();
			
		$fields = array(
			'terminalId' => $this->params->get('melatterminalId'),
			'userName' => $this->params->get('melatuser'),
			'userPassword' => $this->params->get('melatpass'),
			'orderId' => time(),
			'amount' => $price,
			'localDate' => $dateTime->format('Ymd'),
			'localTime' => $dateTime->format('His'),
			'additionalData' => '',
			'callBackUrl' => $vars->return,
			'payerId' => 0,
			);
			
		try {
			$soap = new SoapClient('https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl');
			$response = $soap->bpPayRequest($fields);
			
			$response = explode(',', $response->return);
			if ($response[0] != '0') { // if transaction fail
				$msg= plgDigiCom_PayTrangellMellatHelper::getGateMsg($response[0]); 
				$app->enqueueMessage($msg , 'error');	
				return false;
			}
			else { // if success
				$refId = $response[1];
				$vars->urls = "https://bpm.shaparak.ir/pgwchannel/startpay.mellat";
				$vars->refid = $refId;
				$html = $this->buildLayout($vars);
				return $html;
			}
		}
		catch(\SoapFault $e) {
			$msg= plgDigiCom_PayTrangellMellatHelper::getGateMsg('error'); 
			$app->enqueueMessage($msg , 'error');	
			return false;
		}	
	}

	function onDigicom_PayGetInfo($config)
	{
		if(!in_array($this->_name,$config)) return;
		$obj 		= new stdClass;
		$obj->name 	= $this->params->get( 'plugin_name' );
		$obj->id	= $this->_name;
		return $obj;
	}

	function onDigicom_PayProcesspayment($data)
	{
		$processor = JFactory::getApplication()->input->get('processor','');
		if($processor != $this->_name) return;
		$app	= JFactory::getApplication();	
		$jinput = $app->input;
		$orderId = $jinput->get->get('order_id', '0', 'INT');
		$price = intval(str_replace(".","",plgDigiCom_PayTrangellMellatHelper::getPayerPrice($orderId)));
		$trackingCode = "";
		//========================================================================
		$ResCode = $jinput->post->get('ResCode', '1', 'INT'); 
		$SaleOrderId = $jinput->post->get('SaleOrderId', '1', 'INT'); 
		$SaleReferenceId = $jinput->post->get('SaleReferenceId', '1', 'INT'); 
		$RefId = $jinput->post->get('RefId', 'empty', 'STRING'); 
		if (checkHack::strip($RefId) != $RefId )
			$RefId = "illegal";
		$CardNumber = $jinput->post->get('CardHolderPan', 'empty', 'STRING'); 
		if (checkHack::strip($CardNumber) != $CardNumber )
			$CardNumber = "illegal";
			
		if (
			checkHack::checkNum($ResCode) &&
			checkHack::checkNum($SaleOrderId) &&
			checkHack::checkNum($SaleReferenceId) 
		){
			if ($ResCode != '0') {
				$msg= plgDigiCom_PayTrangellMellatHelper::getGateMsg($ResCode); 
				$app->enqueueMessage($msg, 'error');
				$payment_status = $this->translateResponse('Pending');
			}
			else {
				$fields = array(
				'terminalId' => $this->params->get('melatterminalId'),
				'userName' => $this->params->get('melatuser'),
				'userPassword' => $this->params->get('melatpass'),
				'orderId' => $SaleOrderId, 
				'saleOrderId' =>  $SaleOrderId, 
				'saleReferenceId' => $SaleReferenceId
				);
				try {
					$soap = new SoapClient('https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl');
					$response = $soap->bpVerifyRequest($fields);

					if ($response->return != '0') {
						$msg= plgDigiCom_PayTrangellMellatHelper::getGateMsg($response->return); 
						$app->enqueueMessage($msg, 'error');
						$payment_status = $this->translateResponse('Failed');	
					}
					else {	
						$response = $soap->bpSettleRequest($fields);
						if ($response->return == '0' || $response->return == '45') {
							$payment_status = $this->translateResponse('Completed');
							$db = JFactory::getDbo();
							$query = $db->getQuery(true);
							$fields = array($db->qn('amount_paid') . ' = ' .$db->q($price) );
							$conditions = array($db->qn('id') . ' = ' . $db->q(intval($orderId)));
							$query->update($db->qn('#__digicom_orders'))->set($fields)->where($conditions);
							$db->setQuery((string)$query); 
							$db->execute(); 
							$trackingCode = $SaleReferenceId;
							$msg= plgDigiCom_PayTrangellMellatHelper::getGateMsg($response->return); 
							$message = "کد پیگیری".$trackingCode;
							$app->enqueueMessage($msg.'<br/>' .$message, 'message');
						}
						else {
							$msg= plgDigiCom_PayTrangellMellatHelper::getGateMsg($response->return); 
							$app->enqueueMessage($msg, 'error');
							$payment_status = $this->translateResponse('Failed');	
						}
					}
				}
				catch(\SoapFault $e)  {
					$msg= plgDigiCom_PayTrangellMellatHelper::getGateMsg('error'); 
					$app->enqueueMessage($msg, 'error');
					$payment_status = $this->translateResponse('Failed');	
				}
			}
		}
		else {
			$msg= plgDigiCom_PayTrangellMellatHelper::getGateMsg('hck2'); 
			$app->enqueueMessage($msg, 'error');
			$payment_status = $this->translateResponse('Failed');
		}

		$data['payment_status'] = $payment_status;
		if(!isset($data['payment_status']))
		{
			$info = array('raw_data'	=>	$data);
			$this->onDigicom_PayStorelog($this->_name, $info);
		}

		$result = array(
			'transaction_id'	=>	$this->getUniqueTransactionId($orderId),
			'order_id'				=>	$orderId,
			'status'					=>	$payment_status,
			'raw_data'				=>	json_encode($data),
			'trackingcode'			=>	"کد پیگیری".$trackingCode,
			'card_Number'			=> 	"شماره کارت " . $CardNumber,
			'processor'				=>	$processor
		);
		return $result;
	}

	function translateResponse($invoice_status){

		foreach($this->responseStatus as $key=>$value)
		{
			if($key==$invoice_status)
			return $value;
		}
	}

	function onDigicom_PayStorelog($name, $data)
	{
		if($name != $this->_name) return;
		plgDigiCom_PayTrangellMellatHelper::Storelog($this->_name,$data);
	}

	function getUniqueTransactionId($order_id){
		$uniqueValue = $order_id.time();
		$long = md5(uniqid($uniqueValue, true));
		return substr($long, 0, 15);
	}

	
}
