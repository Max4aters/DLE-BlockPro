<?
/*
=============================================================================
Проверка статуса лицензии
=============================================================================
Автор:   ПафНутиЙ
URL:     http://pafnuty.name/
twitter: https://twitter.com/pafnuty_name
google+: http://gplus.to/pafnuty
email:   pafnuty10@gmail.com
=============================================================================
*/
if (!defined('DATALIFEENGINE')) die("Go fuck yourself!");
if(!class_exists('Protect')) {
	// Класс проверки лицензии -->

		class Protect{ public $status=false; public $errors=false; public $activation_key=''; public $activation_key_expires; public $secret_key='fdfbLhlLgnJDKJklblngkk6krtkghm565678kl78klkUUHtvdfdoghphj'; public $server=''; public $remote_port=80; public $remote_timeout=20; public $local_ua='PHP code protect'; public $use_localhost=false; public $use_expires=true; public $local_key_storage='filesystem'; public $local_key_path='./'; public $local_key_name='license.lic'; public $local_key_transport_order='scf'; public $local_key_delay_period=7; public $local_key_last; public $release_date='2014-10-24'; public $user_name=''; public $status_messages=array('status_1'=>'This activation key is active.','status_2'=>'Error: This activation key has expired.','status_3'=>'Activation key republished. Awaiting reactivation.','status_4'=>'Error: This activation key has been suspended.','localhost'=>'This activation key is active (localhost).','pending'=>'Error: This activation key is pending review.','download_access_expired'=>'Error: This version of the software was released after your download access expired. Please downgrade software or contact support for more information.','missing_activation_key'=>'Error: The activation key variable is empty.','could_not_obtain_local_key'=>'Error: I could not obtain a new local key.','maximum_delay_period_expired'=>'Error: The maximum local key delay period has expired.','local_key_tampering'=>'Error: The local key has been tampered with or is invalid.','local_key_invalid_for_location'=>'Error: The local key is invalid for this location.','missing_license_file'=>'Error: Please create the following file (and directories if they dont exist already): ','license_file_not_writable'=>'Error: Please make the following path writable: ','invalid_local_key_storage'=>'Error: I could not determine the local key storage on clear.','could_not_save_local_key'=>'Error: I could not save the local key.','activation_key_string_mismatch'=>'Error: The local key is invalid for this activation key.'); private $trigger_delay_period; public function __construct(){} public function validate(){if($this->use_localhost&&$this->getIpLocal()&&$this->isWindows()&&!file_exists("{$this->local_key_path}{$this->local_key_name}")){$this->status=true;return $this->errors=$this->status_messages['localhost'];}if(!$this->activation_key){return $this->errors=$this->status_messages['missing_activation_key'];}switch($this->local_key_storage){case 'filesystem':$local_key=$this->readLocalKey();break;default:return $this->errors=$this->status_messages['missing_activation_key'];}$this->trigger_delay_period=$this->status_messages['could_not_obtain_local_key'];if($this->errors==$this->trigger_delay_period&&$this->local_key_delay_period){$delay=$this->processDelayPeriod($this->local_key_last);if($delay['write']){if($this->local_key_storage=='filesystem'){$this->writeLocalKey($delay['local_key'],"{$this->local_key_path}{$this->local_key_name}");}}if($delay['errors']){return $this->errors=$delay['errors'];}$this->errors=false;return $this;}if($this->errors){return $this->errors;}return $this->validateLocalKey($local_key);} private function calcMaxDelay($local_key_expires,$delay){return ((integer)$local_key_expires+((integer)$delay*86400));} private function processDelayPeriod($local_key){$local_key_src=$this->decodeLocalKey($local_key);$parts=$this->splitLocalKey($local_key_src);$key_data=unserialize($parts[0]);$local_key_expires=(integer)$key_data['local_key_expires'];unset($parts,$key_data);$write_new_key=false;$parts=explode("\n\n",$local_key);$local_key=$parts[0];foreach($local_key_delay_period=explode(',',$this->local_key_delay_period) as $key=>$delay){if(!$key){$local_key.="\n";}if($this->calcMaxDelay($local_key_expires,$delay)>time()){continue;}$local_key.="\n{$delay}";$write_new_key=true;}if(time()>$this->calcMaxDelay($local_key_expires,array_pop($local_key_delay_period))){return array('write'=>false,'local_key'=>'','errors'=>$this->status_messages['maximum_delay_period_expired']);}return array('write'=>$write_new_key,'local_key'=>$local_key,'errors'=>false);} private function inDelayPeriod($local_key,$local_key_expires){$delay=$this->splitLocalKey($local_key,"\n\n");if(!isset($delay[1])){return -1;}return (integer)($this->calcMaxDelay($local_key_expires,array_pop(explode("\n",$delay[1])))-time());} private function decodeLocalKey($local_key){return base64_decode(str_replace("\n",'',urldecode($local_key)));} private function splitLocalKey($local_key,$token='{protect}'){return explode($token,$local_key);} private function validateAccess($key,$valid_accesses){return in_array($key,(array)$valid_accesses);} private function wildcardIp($key){$octets=explode('.',$key);array_pop($octets);$ip_range[]=implode('.',$octets).'.*';array_pop($octets);$ip_range[]=implode('.',$octets).'.*';array_pop($octets);$ip_range[]=implode('.',$octets).'.*';return $ip_range;} private function wildcardServerHostname($key){$hostname=explode('.',$key);unset($hostname[0]);$hostname=(!isset($hostname[1]))?array($key):$hostname;return '*.'.implode('.',$hostname);} private function extractAccessSet($instances,$enforce){foreach($instances as $key=>$instance){if($key!=$enforce){continue;}return $instance;}return array();} private function validateLocalKey($local_key){$local_key_src=$this->decodeLocalKey($local_key);$parts=$this->splitLocalKey($local_key_src);if(!isset($parts[1])){return $this->errors=$this->status_messages['local_key_tampering'];}if(md5((string)$this->secret_key.(string)$parts[0])!=$parts[1]){return $this->errors=$this->status_messages['local_key_tampering'];}unset($this->secret_key);$key_data=unserialize($parts[0]);$instance=$key_data['instance'];unset($key_data['instance']);$enforce=$key_data['enforce'];unset($key_data['enforce']);$this->user_name=$key_data['user_name'];if((string)$key_data['activation_key_expires']=='never'){$this->activation_key_expires=0;}else {$this->activation_key_expires=(integer)$key_data['activation_key_expires'];}if((string)$key_data['activation_key']!=(string)$this->activation_key){return $this->errors=$this->status_messages['activation_key_string_mismatch'];}if((integer)$key_data['status']!=1&&(integer)$key_data['status']!=2){return $this->errors=$this->status_messages['status_'.$key_data['status']];}if($this->use_expires==false&&(string)$key_data['activation_key_expires']!='never'&&(integer)$key_data['activation_key_expires']<time()){return $this->errors=$this->status_messages['status_2'];}if($this->use_expires==false&&(string)$key_data['local_key_expires']!='never'&&(integer)$key_data['local_key_expires']<time()){if($this->inDelayPeriod($local_key,$key_data['local_key_expires'])<0){$this->clearLocalKey();return $this->validate();}}if($this->use_expires==true&&(string)$key_data['activation_key_expires']!='never'&&(integer)$key_data['activation_key_expires']<strtotime($this->release_date)){return $this->errors=$this->status_messages['download_access_expired'];}if($this->use_expires==true&&(string)$key_data['local_key_expires']!='never'&&(integer)$key_data['local_key_expires']<time()&&(integer)$key_data['activation_key_expires']>(integer)$key_data['local_key_expires']+604800){if($this->inDelayPeriod($local_key,$key_data['local_key_expires'])<0){$this->clearLocalKey();return $this->validate();}}$conflicts=array();$access_details=$this->accessDetails();foreach((array)$enforce as $key){$valid_accesses=$this->extractAccessSet($instance,$key);if(!$this->validateAccess($access_details[$key],$valid_accesses)){$conflicts[$key]=true;if(in_array($key,array('ip','server_ip'))){foreach($this->wildcardIp($access_details[$key]) as $ip){if($this->validateAccess($ip,$valid_accesses)){unset($conflicts[$key]);break;}}}elseif(in_array($key,array('domain'))){if(isset($key_data['domain_wildcard'])){if($key_data['domain_wildcard']==1&&preg_match("/".$valid_accesses[0]."\z/i",$access_details[$key])){$access_details[$key]='*.'.$valid_accesses[0];}if($key_data['domain_wildcard']==2){$exp_domain=explode('.',$valid_accesses[0]);$exp_domain=$exp_domain[0];if(preg_match("/".$exp_domain."/i",$access_details[$key])){$access_details[$key]='*.'.$valid_accesses[0].'.*';}}if($key_data['domain_wildcard']==3){$exp_domain=explode('.',$valid_accesses[0]);$exp_domain=$exp_domain[0];if(preg_match("/\A".$exp_domain."/i",$access_details[$key])){$access_details[$key]=$valid_accesses[0].'.*';}}}if($this->validateAccess($access_details[$key],$valid_accesses)){unset($conflicts[$key]);}}elseif(in_array($key,array('server_hostname'))){if($this->validateAccess($this->wildcardServerHostname($access_details[$key]),$valid_accesses)){unset($conflicts[$key]);}}}}if(!empty($conflicts)){return $this->errors=$this->status_messages['local_key_invalid_for_location'];}$this->errors=$this->status_messages['status_1'];return $this->status=true;} public function readLocalKey(){if(!is_dir($this->local_key_path)){mkdir($this->local_key_path,0755,true);}if(!file_exists($path="{$this->local_key_path}{$this->local_key_name}")){$f=@fopen($path,'w');if(!$f){return $this->errors=$this->status_messages['missing_license_file'].$path;}else {fwrite($f,'');fclose($f);}}if(!is_writable($path)){@chmod($path,0777);if(!is_writable($path)){@chmod("$path",0755);if(!is_writable($path)){return $this->errors=$this->status_messages['license_file_not_writable'].$path;}}}if(!$local_key=@file_get_contents($path)){$local_key=$this->getServerLocalKey();if($this->errors){return $this->errors;}$this->writeLocalKey(urldecode($local_key),$path);}return $this->local_key_last=$local_key;} public function clearLocalKey(){if($this->local_key_storage=='filesystem'){$this->writeLocalKey('',"{$this->local_key_path}{$this->local_key_name}");}else {$this->errors=$this->status_messages['invalid_local_key_storage'];}} public function writeLocalKey($local_key,$path){$fp=@fopen($path,'w');if(!$fp){return $this->errors=$this->status_messages['could_not_save_local_key'];}@fwrite($fp,$local_key);@fclose($fp);return true;} private function getServerLocalKey(){$query_string='activation_key='.urlencode($this->activation_key).'&';$query_string.=http_build_query($this->accessDetails());if($this->errors){return false;}$priority=$this->local_key_transport_order;$result=false;while(strlen($priority)){$use=substr($priority,0,1);if($use=='s'){if($result=$this->useFsockopen($this->server,$query_string)){break;}}if($use=='c'){if($result=$this->useCurl($this->server,$query_string)){break;}}if($use=='f'){if($result=$this->useFopen($this->server,$query_string)){break;}}$priority=substr($priority,1);}if(!$result){$this->errors=$this->status_messages['could_not_obtain_local_key'];return false;}if(substr($result,0,7)=='Invalid'){$this->errors=str_replace('Invalid','Error',$result);return false;}if(substr($result,0,5)=='Error'){$this->errors=$result;return false;}return $result;} private function accessDetails(){$access_details=array();if(function_exists('phpinfo')){ob_start();phpinfo();$phpinfo=ob_get_contents();ob_end_clean();$list=strip_tags($phpinfo);$access_details['domain']=$this->scrapePhpInfo($list,'HTTP_HOST');$access_details['ip']=$this->scrapePhpInfo($list,'SERVER_ADDR');$access_details['directory']=$this->scrapePhpInfo($list,'SCRIPT_FILENAME');$access_details['server_hostname']=$this->scrapePhpInfo($list,'System');$access_details['server_ip']=@gethostbyname($access_details['server_hostname']);}$access_details['domain']=($access_details['domain'])?$access_details['domain']:$_SERVER['HTTP_HOST'];$access_details['ip']=($access_details['ip'])?$access_details['ip']:$this->serverAddr();$access_details['directory']=($access_details['directory'])?$access_details['directory']:$this->pathTranslated();$access_details['server_hostname']=($access_details['server_hostname'])?$access_details['server_hostname']:@gethostbyaddr($access_details['ip']);$access_details['server_hostname']=($access_details['server_hostname'])?$access_details['server_hostname']:'Unknown';$access_details['server_ip']=($access_details['server_ip'])?$access_details['server_ip']:@gethostbyaddr($access_details['ip']);$access_details['server_ip']=($access_details['server_ip'])?$access_details['server_ip']:'Unknown';foreach($access_details as $key=>$value){$access_details[$key]=($access_details[$key])?$access_details[$key]:'Unknown';}return $access_details;} private function pathTranslated(){$option=array('PATH_TRANSLATED','ORIG_PATH_TRANSLATED','SCRIPT_FILENAME','DOCUMENT_ROOT','APPL_PHYSICAL_PATH');foreach($option as $key){if(!isset($_SERVER[$key])||strlen(trim($_SERVER[$key]))<=0){continue;}if($this->isWindows()&&strpos($_SERVER[$key],'\\')){return @substr($_SERVER[$key],0,@strrpos($_SERVER[$key],'\\'));}return @substr($_SERVER[$key],0,@strrpos($_SERVER[$key],'/'));}return false;} private function serverAddr(){$options=array('SERVER_ADDR','LOCAL_ADDR');foreach($options as $key){if(isset($_SERVER[$key])){return $_SERVER[$key];}}return false;} private function scrapePhpInfo($all,$target){$all=explode($target,$all);if(count($all)<2){return false;}$all=explode("\n",$all[1]);$all=trim($all[0]);if($target=='System'){$all=explode(" ",$all);$all=trim($all[(strtolower($all[0])=='windows'&&strtolower($all[1])=='nt')?2:1]);}if($target=='SCRIPT_FILENAME'){$slash=($this->isWindows()?'\\':'/');$all=explode($slash,$all);array_pop($all);$all=implode($slash,$all);}if(substr($all,1,1)==']'){return false;}return $all;} private function useFsockopen($url,$query_string){if(!function_exists('fsockopen')){return false;}$url=parse_url($url);$fp=@fsockopen($url['host'],$this->remote_port,$errno,$errstr,$this->remote_timeout);if(!$fp){return false;}$header="POST {$url['path']} HTTP/1.0\r\n";$header.="Host: {$url['host']}\r\n";$header.="Content-type: application/x-www-form-urlencoded\r\n";$header.="User-Agent: ".$this->local_ua."\r\n";$header.="Content-length: ".@strlen($query_string)."\r\n";$header.="Connection: close\r\n\r\n";$header.=$query_string;$result=false;fputs($fp,$header);while(!feof($fp)){$result.=fgets($fp,1024);}fclose($fp);if(strpos($result,'200')===false){return false;}$result=explode("\r\n\r\n",$result,2);if(!$result[1]){return false;}return $result[1];} private function useCurl($url,$query_string){if(!function_exists('curl_init')){return false;}$curl=curl_init();$header[0]="Accept: text/xml,application/xml,application/xhtml+xml,";$header[0].="text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";$header[]="Cache-Control: max-age=0";$header[]="Connection: keep-alive";$header[]="Keep-Alive: 300";$header[]="Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";$header[]="Accept-Language: en-us,en;q=0.5";$header[]="Pragma: ";curl_setopt($curl,CURLOPT_URL,$url);curl_setopt($curl,CURLOPT_USERAGENT,$this->local_ua);curl_setopt($curl,CURLOPT_HTTPHEADER,$header);curl_setopt($curl,CURLOPT_ENCODING,'gzip,deflate');curl_setopt($curl,CURLOPT_AUTOREFERER,true);curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);curl_setopt($curl,CURLOPT_POST,1);curl_setopt($curl,CURLOPT_POSTFIELDS,$query_string);curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,$this->remote_timeout);curl_setopt($curl,CURLOPT_TIMEOUT,$this->remote_timeout);$result=curl_exec($curl);$info=curl_getinfo($curl);curl_close($curl);if((integer)$info['http_code']!=200){return false;}return $result;} private function useFopen($url,$query_string){if(!function_exists('file_get_contents')||!ini_get('allow_url_fopen')||!extension_loaded('openssl')){return false;}$stream=array('http'=>array('method'=>'POST','header'=>"Content-type: application/x-www-form-urlencoded\r\nUser-Agent: ".$this->local_ua,'content'=>$query_string));$context=null;$context=stream_context_create($stream);return @file_get_contents($url,false,$context);} private function isWindows(){return (strtolower(substr(php_uname(),0,7))=='windows');} private function getIpLocal(){$local_ip='';if(function_exists('phpinfo')){ob_start();phpinfo();$phpinfo=ob_get_contents();ob_end_clean();$list=strip_tags($phpinfo);$local_ip=$this->scrapePhpInfo($list,'SERVER_ADDR');}$local_ip=($local_ip)?$local_ip:$this->serverAddr();if($local_ip=='127.0.0.1')return true;return false;}}

	// <-- Класс проверки лицензии
}

$licenseStatus = false;
// Проверяем лицензию.	
	

$protect = new Protect();
$protect->secret_key = 'RdaDrhZFbf6cZqu';
$protect->use_localhost = true;
$protect->local_key_path = ENGINE_DIR . '/data/';
$protect->local_key_name = 'blockpro.lic';
$protect->server = 'http://api.pafnuty.name/api.php';
$protect->release_date = '2015-07-18'; // гггг-мм-дд
$protect->activation_key = @file_get_contents(ENGINE_DIR . '/data/blockpro.key');

$protect->status_messages = array(
	'status_1'                       => '<span style="color:green;">Активна</span>',
    'status_2'                       => '<span style="color:darkblue;">Внимание</span>, срок действия лицензии закончился.',
    'status_3'                       => '<span style="color:orange;">Внимание</span>, лицензия переиздана. Ожидает повторной активации.',
    'status_4'                       => '<span style="color:red;">Ошибка</span>, лицензия была приостановлена.',
    'localhost'                      => '<span style="color:orange;">Активна на localhost</span>, используется локальный компьютер, на реальном сервере произойдет активация.',
    'pending'                        => '<span style="color:red;">Ошибка</span>, лицензия ожидает рассмотрения.',
    'download_access_expired'        => '<span style="color:red;">Ошибка</span>, ключ активации не подходит для установленной версии. Пожалуйста поставьте более старую версию продукта.',
    'missing_license_key'            => '<span style="color:red;">Ошибка</span>, лицензионный ключ не указан.',
    'could_not_obtain_local_key'     => '<span style="color:red;">Ошибка</span>, невозможно получить новый локальный ключ.',
    'maximum_delay_period_expired'   => '<span style="color:red;">Ошибка</span>, льготный период локального ключа истек.',
    'local_key_tampering'            => '<span style="color:red;">Ошибка</span>, локальный лицензионный ключ поврежден или не действителен.',
    'local_key_invalid_for_location' => '<span style="color:red;">Ошибка</span>, локальный ключ не подходит к данному сайту.',
    'missing_license_file'           => '<span style="color:red;">Ошибка</span>, создайте следующий пустой файл и папки если его нет:<br />',
    'license_file_not_writable'      => '<span style="color:red;">Ошибка</span>, сделайте доступными для записи следующие пути:<br />',
    'invalid_local_key_storage'      => '<span style="color:red;">Ошибка</span>, невозможно удалить старый локальный ключ.',
    'could_not_save_local_key'       => '<span style="color:red;">Ошибка</span>, невозможно записать новый локальный ключ.',
    'license_key_string_mismatch'    => '<span style="color:red;">Ошибка</span>, локальный ключ не действителен для указанной лицензии.',
);

/**
 * Запускаем валидацию
 */
$protect->validate();

$license = false;
/**
 * Если истина, то лицензия в боевом состоянии
 */
if($protect->status) {
	$license = true;
}
// Если лицензия не проверилась - скжем об этом
$licenseStatus = (!$protect->errors) ? '<span style="color: red;">Ошибка лицензии, обратитесь к автору модуля.</span>' : $protect->errors;