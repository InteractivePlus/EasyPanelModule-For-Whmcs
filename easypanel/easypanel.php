<?php
/* 老秋风基于官网(www.kanglesoft.com)API修改, 修复部分功能不能使用的Bug
 * 修复团队地址 www.xsyds.cn
 * 转载请说明原作者
*/
require_once('whm.lib.php');

function easypanel_ConfigOptions() {

	# Should return an array of the module options for each product - maximum of 24

    $configarray = array(
		 "CDN"				=> array("Type" => "yesno", "Description" => "是否为CDN网站"),				//1
		 "空间类型(模块名)"			=> array("Type" => "dropdown", "Options" => "php,iis"),							//2
		 "web配额" 			=> array( "Type" => "text", "Size" => "5", "Description" => "MB" ),	//3
		 "数据库类型"			=> array( "Type" => "dropdown", "Options" => "mysql,sqlsrv"),		//4
		 "数据库配额" 		=> array( "Type" => "text", "Size" => "5", "Description" => "MB" ),	//5
		 "FTP" 				=> array( "Type" => "yesno", "Description" => "是否允许ftp" ),		//6
		 "独立日志" 			=> array( "Type" => "yesno", "Description" => "是否开启独立日志" ),	//7
	     "绑定域名数" 		=> array( "Type" => "text", "Size" => "5"),							//8
	     "连接数"			=> array( "Type" => "text", "Size" => "5"),							//9
	     "带宽限制"			=> array( "Type" => "text", "Size" => "5","Description" => "K/S"),	//10
		 "默认绑定到子目录" 	=> array( "Type" => "text","Size"=>"20"),							//11
		 "允许绑定子目录" 		=> array( "Type" => "yesno", "Description" => "是否允许绑定域名到子目录"),//12
		 "最多绑定子目录数" 	=> array( "Type" => "text","Size"=>"5"),							//13
    	 "流量限制"			=> array( "Type" => "text","Size"=>"5","Description" => "GB/月"),	//14
    	 "管理变量"			=> array( "Type" => "text","Size"=>"12"),							//15
    	 "工作数"			=> array( "Type" => "text","Size"=>"5"),							//16
    	 "附加参数"			=> array("Type" => "text",'Size'=>'12'),							//17
		 "SSL"				=> array("Type" => "yesno", "Description"=>"支持SSL"),          //18
		 "定时任务"			=> array("Type" => "text", 'Size'=>"5", "Description"=>"条"),       //19
	);
	return $configarray;
}
function easypanel_make_whm($params) {
	$whm = new WhmClient();
	$whm->setUrl('http://'.$params["serverip"].':3312/');
	$whm->setSecurityKey($params["serveraccesshash"]);
	return $whm;	
}
function easypanel_call($whmCall,$params)
{
	$whm = easypanel_make_whm($params);
    $result = $whm->call($whmCall,300);
    if ($result===false) {
    	return "不能连接到主机";
    }
    if ($result->getCode()==200) {
    	return "success";
    }
    return $result->status;
}
function easypanel_update_account($params,$edit)
{
	$whmCall = new WhmCall('add_vh');
    $whmCall->addParam('name', $params["username"]);
    $whmCall->addParam('passwd',$params["password"]);
	$whmCall->addParam('cdn',($params["configoption1"]== 'on'?1:0));
	//$whmCall->addParam('templete',$params["configoption2"]); //原先的
	$whmCall->addParam('module',$params["configoption2"]); //新的
    $whmCall->addParam('web_quota',$params["configoption3"]);
	$whmCall->addParam('db_type',$params["configoption4"]);
    $whmCall->addParam('db_quota',$params["configoption5"]);
    $whmCall->addParam('ftp',($params["configoption6"]== 'on'?1:0));
	$whmCall->addParam('log_file',($params["configoption7"]== 'on'?1:0));
    $whmCall->addParam('domain',$params["configoption8"]);
    $whmCall->addParam('max_connect',$params["configoption9"]);
    //$whmCall->addParam('speed_limit',intval($params["configoption10"])*1024);	//原先以B为单位
	$whmCall->addParam('speed_limit', intval($params["configoption10"]));
	$whmCall->addParam('subdir',$params["configoption11"]);
	$whmCall->addParam('subdir_flag',($params["configoption12"]== 'on'?1:0));
	$whmCall->addParam('max_subdir',$params["configoption13"]);
	//$whmCall->addParam('flow',$params["configoption14"]);
	$whmCall->addParam('flow_limit',$params["configoption14"]);
	$whmCall->addParam('envs',$params["configoption15"]);
	$whmCall->addParam('max_worker',$params["configoption16"]);
    $whmCall->addParam('vhost_domains',$params["domain"]);
    $whmCall->addParam('htaccess',1);
    $whmCall->addParam('access',1);
    if (trim($params["configoption17"]) != "") {
    	/*附加参数处理*/
    		$explode = explode('&', $params["configoption17"]);
    		//多个参数
    		if (is_array($explode)) {
    			foreach($explode as $e) {
    				$k = explode('=', $e);
    				if ($k[0]=='c' || $k[0]=='a' || $k[0] == 's' || $k[0] == 'r') {
    					continue;
    				}
    				$whmCall->addParam($k[0],$k[1]);
    			}
    			//一个参数
    		}else {
    			$k = explode('=',$params["configoption17"]);
    			if (is_array($k)) {
    				if ($k[0]=='c' || $k[0]=='a' || $k[0] == 's' || $k[0] == 'r') {
						//continue;
					}else{
						$whmCall->addParam($k[0],$k[1]);
					}
    			}
    		}
    }
    //$whmCall->addParam('port',$params['configoption18']);
	$whmCall->addParam('port',($params["configoption18"] == 'on'?"80,443s":"80"));
	if(!empty($params['configoption19'])){
		$whmCall->addParam('cron',$params['configoption19']);
	}
	
    if ($edit) {
    	$whmCall->addParam('edit',1);
    }
    $whmCall->addParam('init',1);
   	return easypanel_call($whmCall,$params);
}
function easypanel_CreateAccount($params) {
	if ($params['username']=="") {
		return "username cann't be empty";
	}
	return easypanel_update_account($params,false);
}

function easypanel_TerminateAccount($params) {

	$whmCall = new WhmCall('del_vh');
	$whmCall->addParam('name', $params["username"]);
	return easypanel_call($whmCall,$params);
}

function easypanel_SuspendAccount($params) {

	$whmCall = new WhmCall('update_vh');
	$whmCall->addParam('name', $params["username"]);
	$whmCall->addParam('status',1);
	return easypanel_call($whmCall,$params);
}

function easypanel_UnsuspendAccount($params) {	
	$whmCall = new WhmCall('update_vh');
	$whmCall->addParam('name', $params["username"]);
	$whmCall->addParam('status',0);
	return easypanel_call($whmCall,$params);
}

function easypanel_ChangePassword($params) {

	$whmCall = new WhmCall('change_password');
	$whmCall->addParam('name', $params["username"]);
	$whmCall->addParam('passwd',$params["password"]);
	return easypanel_call($whmCall,$params);
}

function easypanel_ChangePackage($params) {
	return easypanel_update_account($params,true);
}

function easypanel_ClientArea($params) {

    # Output can be returned like this, or defined via a clientarea.tpl template file (see docs for more info)

	$code = '<form action="http://'.$params["serverip"].':3312/vhost/?c=session&a=login" method="post" target="_blank">
<input type="hidden" name="username" value="'.$params["username"].'" />
<input type="hidden" name="passwd" value="'.$params["password"].'" />
<input type="submit" value="登录" />
</form>';
	return $code;

}

function easypanel_AdminLink($params) {

	$code = '<form action="http://'.$params["serverip"].':3312/admin/?c=session&a=login" method="post" target="_blank">
<input type="hidden" name="username" value="'.$params["serverusername"].'" />
<input type="hidden" name="passwd" value="'.$params["serverpassword"].'" />
<input type="submit" value="登录管理" />
</form>';
	return $code;

}

function easypanel_LoginLink($params) {

	echo "<a href=\"http://".$params["serverip"].":3312/vhost/?username=".$params["username"]."\" target=\"_blank\" style=\"color:#cc0000\">登录easypanel</a>";

}

function easypanel_reboot($params) {

	# Code to perform reboot action goes here...

	return "还未实现";    

}

function easypanel_shutdown($params) {

	return "还未实现";

}

function easypanel_ClientAreaCustomButtonArray() {
    $buttonarray = array(
	 //"Reboot Server" => "reboot",
	);
	return $buttonarray;
}

function easypanel_AdminCustomButtonArray() {
    $buttonarray = array(
	 //"Reboot Server" => "reboot",
	 //"Shutdown Server" => "shutdown",
	);
	return $buttonarray;
}

function easypanel_extrapage($params) {
    $pagearray = array(
     'templatefile' => 'example',
     'breadcrumb' => ' > <a href="#">Example Page</a>',
     'vars' => array(
        'var1' => 'demo1',
        'var2' => 'demo2',
     ),
    );
	return $pagearray;
}

function easypanel_UsageUpdate($params) {

	//还没实现
	$serverid = $params['serverid'];
	$serverhostname = $params['serverhostname'];
	$serverip = $params['serverip'];
	$serverusername = $params['serverusername'];
	$serverpassword = $params['serverpassword'];
	$serveraccesshash = $params['serveraccesshash'];
	$serversecure = $params['serversecure'];

	# Run connection to retrieve usage for all domains/accounts on $serverid

	# Now loop through results and update DB

	foreach ($results AS $domain=>$values) {
        update_query("tblhosting",array(
         "diskused"=>$values['diskusage'],
         "dislimit"=>$values['disklimit'],
         "bwused"=>$values['bwusage'],
         "bwlimit"=>$values['bwlimit'],
         "lastupdate"=>"now()",
        ),array("server"=>$serverid,"domain"=>$values['domain']));
    }

}

function easypanel_AdminServicesTabFields($params) {

    $result = select_query("mod_customtable","",array("serviceid"=>$params['serviceid']));
    $data = mysql_fetch_array($result);
    $var1 = $data['var1'];
    $var2 = $data['var2'];
    $var3 = $data['var3'];
    $var4 = $data['var4'];

    $fieldsarray = array(
     'Field 1' => '<input type="text" name="modulefields[0]" size="30" value="'.$var1.'" />',
     'Field 2' => '<select name="modulefields[1]"><option>Val1</option</select>',
     'Field 3' => '<textarea name="modulefields[2]" rows="2" cols="80">'.$var3.'</textarea>',
     'Field 4' => $var4, # Info Output Only
    );
    return $fieldsarray;

}

function easypanel_AdminServicesTabFieldsSave($params) {
    update_query("mod_customtable",array(
        "var1"=>$_POST['modulefields'][0],
        "var2"=>$_POST['modulefields'][1],
        "var3"=>$_POST['modulefields'][2],
    ),array("serviceid"=>$params['serviceid']));
}

?>