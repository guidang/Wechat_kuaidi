<?php
/**
 * 	微信公众平台基础类 For Fshare
 * 	@author:	Skiychan
 * 	@contact:	QQ:1005043848
 * 	@website:	www.zzzzy.com
 * 	@created:	2013.11.19
 */

include_once 'class.base.php';

define('TOKEN', 'fshare');

$wx = new Wechat();

//$wx->valid();
$wx->responseMsg();