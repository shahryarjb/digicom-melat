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

class plgDigiCom_PayTrangellMellatHelper
{

	public static function Storelog($name,$data)
	{
		$my = JFactory::getUser();
		jimport('joomla.log.log');
		JLog::addLogger(
			 array(
						'text_file' => 'com_digicom.trangellmellat.errors.php'
			 ),
			 JLog::ALL,
			 array('com_digicom.trangellmellat')
		 );
		 $msg = 'StoreLog >>  user:'.$my->name.'('.$my->id.'), desc: ' . json_encode($data['raw_data']);
		 JLog::add($msg, JLog::WARNING, 'com_digicom.trangellmellat');

	}

	public static function getGateMsg ($msgId) {
		switch($msgId){
			case '0': $out =  'تراکنش با موفقیت انجام شد'; break;
			case '11': $out =  'شماره کارت نامعتبر است'; break;
			case '12': $out =  'موجودی کافی نیست'; break;
			case '13': $out =  'رمز نادرست است'; break;
			case '14': $out =  'تعداد دفعات وارد کردن رمز بیش از حد مجاز است'; break;
			case '15': $out =  'کارت نامعتبر است'; break;
			case '16': $out =  'دفعات برداشت وجه بیش از حد مجاز است'; break;
			case '17': $out =  'کاربر از انجام تراکنش منصرف شده است'; break;
			case '18': $out =  'تاریخ انقضای کارت گذشته است'; break;
			case '19': $out =  'مبلغ برداشت وجه بیش از حد مجاز است'; break;
			case '21': $out =  'پذیرنده نامعتبر است'; break;
			case '23': $out =  'خطای امنیتی رخ داده است'; break;
			case '24': $out =  'اطلاعات کاربری پذیرنده نادرست است'; break;
			case '25': $out =  'مبلغ نامتعبر است'; break;
			case '31': $out =  'پاسخ نامتعبر است'; break;
			case '32': $out =  'فرمت اطلاعات وارد شده صحیح نمی باشد'; break;
			case '33': $out =  'حساب نامعتبر است'; break;
			case '34': $out =  'خطای سیستمی'; break;
			case '35': $out =  'تاریخ نامعتبر است'; break;
			case '41': $out =  'شماره درخواست تکراری است'; break;
			case '42': $out =  'تراکنش Sale‌ یافت نشد'; break;
			case '43': $out =  'قبلا درخواست Verify‌ داده شده است'; break;
			case '44': $out =  'درخواست Verify‌ یافت نشد'; break;
			case '45': $out =  'تراکنش Settle‌ شده است'; break;
			case '46': $out =  'تراکنش Settle‌ نشده است'; break;
			case '47': $out =  'تراکنش  Settle یافت نشد'; break;
			case '48': $out =  'تراکنش Reverse شده است'; break;
			case '49': $out =  'تراکنش Refund یافت نشد'; break;
			case '51': $out =  'تراکنش تکراری است'; break;
			case '54': $out =  'تراکنش مرجع موجود نیست'; break;
			case '55': $out =  'تراکنش نامعتبر است'; break;
			case '61': $out =  'خطا در واریز'; break;
			case '111': $out =  'صادر کننده کارت نامعتبر است'; break;
			case '112': $out =  'خطا سوییج صادر کننده کارت'; break;
			case '113': $out =  'پاسخی از صادر کننده کارت دریافت نشد'; break;
			case '114': $out =  'دارنده کارت مجاز به انجام این تراکنش نیست'; break;
			case '412': $out =  'شناسه قبض نادرست است'; break;
			case '413': $out =  'شناسه پرداخت نادرست است'; break;
			case '414': $out =  'سازمان صادر کننده قبض نادرست است'; break;
			case '415': $out =  'زمان جلسه کاری به پایان رسیده است'; break;
			case '416': $out =  'خطا در ثبت اطلاعات'; break;
			case '417': $out =  'شناسه پرداخت کننده نامعتبر است'; break;
			case '418': $out =  'اشکال در تعریف اطلاعات مشتری'; break;
			case '419': $out =  'تعداد دفعات ورود اطلاعات از حد مجاز گذشته است'; break;
			case '421': $out =  'IP‌ نامعتبر است';  break;
			case 'error': $out ='خطا غیر منتظره رخ داده است';break;
			case 'hck2': $out = 'لطفا از کاراکترهای مجاز استفاده کنید';break;
			case 'notff': $out = 'سفارش پیدا نشد';break;
			case 'price': $out = 'مبلغ وارد شده کمتر از ۱۰۰۰ ریال می باشد';break;
			default: $out ='خطا غیر منتظره رخ داده است';break;
		}
		return $out;
	}

	public static function getPayerPrice ($id) {
		$user = JFactory::getUser();
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('amount')
			->from($db->qn('#__digicom_orders'));
		$query->where(
			$db->qn('userid') . ' = ' . $db->q($user->id) 
							. ' AND ' . 
			$db->qn('id') . ' = ' . $db->q($id)
		);
		$db->setQuery((string)$query); 
		$result = $db->loadResult();
		return $result;
	}


}
