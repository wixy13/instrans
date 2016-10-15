<?php

header('content-type:application:json;charset=utf8');  
header('Access-Control-Allow-Origin:*');  
header('Access-Control-Allow-Methods:POST');  
header('Access-Control-Allow-Headers:x-requested-with,content-type');

require_once __DIR__ . '/autoload.php';//自动加载
require_once __DIR__ . '/config.php';//配置文件
require_once __DIR__ . '/function.php';//函数文件

// 引入鉴权类
use Qiniu\Auth;

// 引入上传类
use Qiniu\Storage\UploadManager;

if( isset($_POST['token']) )
{
	if( $_POST['token'] == $TOKEN )
	{
		// 七牛的 Access Key 和 Secret Key
		$accessKey = $QINIU_ACCESSKEY;
		$secretKey = $QINIU_SECRETKEY;

		// 构建鉴权对象
		$auth = new Auth($accessKey, $secretKey);

		// 要上传的空间
		$bucket = $QINIU_BUCKET;

		// instagram的API地址
		$url = "https://api.instagram.com/v1/users/self/media/recent/?access_token=".$INS_ACCESS_TOKEN;

		// 获得ins API返回的json
		$info = http_curl($url, 'get', 'json');

		// 读取配置文件
		$json_string = file_get_contents('config.json');
		$conf = json_decode($json_string, true);

		// 取得上次同步的最后一条记录id
		$log_id = $conf['log_id'];

		//echo $log_id;//上次同步id

		// 初始化
		$sign = 0;
		$new_id = $conf['log_id'];

		// 开始同步

		// 链接数据库
		$pdo = new PDO("mysql:host=$DBHOST;dbname=$DBNAME;charset=$CHTYPE",$DBUSER,$DBPSWD); 

		// 设置编码
		$pdo->exec('set names utf8');

		foreach($info['data'] as $log)
		{
		    if( strlen($log_id) > strlen($log['id']) || strcmp($log_id, $log['id']) >= 0 )
		    {
		        break;
		    }
		    else
		    {
		    	// 判断根据条件更新log_id
		    	if( strlen($log['id']) > strlen($new_id) || strcmp($log['id'], $new_id) >= 0 )
		    	{
		    		$new_id = $log['id'];
		    	}

				$sign++;

				// 开始下载
				$img = file_get_contents($log['images']['standard_resolution']['url']);
				$filename = dirname(__FILE__).'/file/'.$log['id'].'.jpg';

				// 保存到本地
				file_put_contents($filename, $img);

				// 开始上传

				// 生成上传 Token
				$token = $auth->uploadToken($bucket);

				// 上传文件的本地路径
				$filePath = './file/'.$log['id'].'.jpg';

				// 上传到七牛后保存的文件名
				$key = 'instagram/'.$log['id'].'.jpg';

				// 初始化 UploadManager 对象并进行文件的上传。
				$uploadMgr = new UploadManager();

				// 调用 UploadManager 的 putFile 方法进行文件的上传。
				list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);

				/*if ($err !== null) {
				    $data['error'] = 1;
				    $data['msg'] = '同步错误！';
				} else {
				    $data['error'] = 0;
				    $data['msg'] = '同步成功！';
				}*/

				//echo "<img src='/file/".$log['id'].'.jpg'."'>";

				//构造新的图片链接
				$picurl = 'http://qiniu.iwangyu.cn'.'/instagram/'.$log['id'].'.jpg';

				//取图片描述
				$text = $log['caption']['text'];

				//取发布时间
				$creattime = $log['created_time'];

				//SQL语句
				$sql = "insert into album(time,type,picurl,description)values('$creattime','instagram','$picurl','$text')";

				//插入到博客数据库
				$pdo -> exec($sql);

			}
		}

		// 更新配置文件参数
		$conf['log_id'] = $new_id;
		$conf['last_sync_time'] = time();

		// json编码
		$json_in_conf = json_encode($conf);

		// 写入配置文件
		file_put_contents('config.json', $json_in_conf);

		$data['error'] = 0;
		if( $sign > 0)
			$data['msg'] = '成功同步'.$sign.'条记录！';
		else
			$data['msg'] = '您的相册已经是最新的！';
	}
	else
	{
		$data['error'] = 1;
		$data['msg'] = 'Token Error!';
	}
}
else
{
	$data['error'] = 1;
	$data['msg'] = '提交方式错误!';
}

echo json_encode($data);