<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>天翼云盘 Cloud189</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/uikit@3.20.8/dist/css/uikit.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.20.8/dist/js/uikit.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.20.8/dist/js/uikit-icons.min.js"></script>
</head>
<body>
<?php
    $access_token='';
    $folder_dict_path='./folder_dict_189.json';


    $defaultFolderId='';//为空则为全盘访问，否则请制定文件夹的id

    if(file_exists($folder_dict_path)===false){file_put_contents($folder_dict_path,'[]');}
    $folder_dict=json_decode(file_get_contents($folder_dict_path), true);
    if(!$folder_dict){$folder_dict=[];}
    
    $timestamp=time().'000';
    $header='User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 Edg/124.0.0.0';
    $header.="\r\nContent-type:application/json;charset=UTF-8";
    $header.="\r\nAccept:application/json;charset=UTF-8";
    $header.="\r\nOrigin:https://h5.cloud.189.cn";
    $header.="\r\nReferer:https://h5.cloud.189.cn/main.html";
    $header.="\r\nSign-Type:1";
    //exit($access_token);

    $shareCode=$_GET['shareCode']??'';
    $folderId=$_GET['folderId']??'-11';
    if(substr($folderId,0,4)=='-11/'){$folderId=substr($folderId,4);}
    if($defaultFolderId && $folderId=='-11'){$folderId=$defaultFolderId;}
    $fileId=$_GET['fileId']??'';

    if($shareCode){
        $json= @file_get_contents('https://cloud.189.cn/api/open/share/getShareInfoByCodeV2.action?shareCode='.$shareCode, false, stream_context_create(array('http'=>array('method'=>'GET','header'=>'Accept:application/json;charset=UTF-8','content'=>''),'ssl'=>array('verify_peer'=>false, 'verify_peer_name'=>false))));
        //{"res_code": 0, "res_message": "成功","accessCode":"","creator":{"iconURL":"","nickName":"173","oper":false,"ownerAccount":"173****1049@189.cn","superVip":0,"vip":0},"expireTime":6,"expireType":1,"fileCreateDate":"2024-05-09 11:38:51","fileId":"924221132626570583","fileLastOpTime":"2024-05-09 11:38:51","fileName":"其他文件","fileSize":0,"fileType":"","isFolder":true,"needAccessCode":1,"reviewStatus":1,"shareDate":1715225931000,"shareMode":1,"shareType":1}
        $json=json_decode($json, true);
        exit('<script>window.location.href="?folderId='.$json['fileId'].'"</script>');
    }elseif(!$fileId){
        $folders='<a href="?" class="uk-text-bottom">首页</a>';
        if($folderId && $folderId!='-11'){
            $folderIds='';
            foreach (explode('/', $folderId) as $key){
                $folderIds.='/'.$key;
                if(in_array($key,array_keys($folder_dict)) && $folder_dict[$key]){
                    $folders.=' / <a href="?folderId='.substr($folderIds,1).'" class="uk-text-bottom">'.$folder_dict[$key].'</a>';
                } 
            }
        }
        $last_folderId=$folderId;
        if(strpos($last_folderId,'/')){
            $last_folderId=substr($last_folderId,strrpos($last_folderId,'/')+1);
        }
        
        
        $query=[];
        $query['pageNum']='1';
        $query['pageSize']='30';
        $query['folderId']=$last_folderId;
        $query['iconOption']='5';
        $query['orderBy']='lastOpTime';
        $query['descending']='true';
        $url='https://api.cloud.189.cn/open/file/listFiles.action?'.http_build_query($query);
        $query['AccessToken']=$access_token;
        $query['Timestamp']=$timestamp;
        ksort($query);
        $signature=md5(http_build_query($query));
        $header.="\r\nAccessToken:".$access_token;
        $header.="\r\nTimestamp:".$timestamp;
        $header.="\r\nSignature:".$signature;

        $json=@file_get_contents($url, false, stream_context_create(array('http'=>array('method'=>'GET','header'=>$header,'content'=>''),'ssl'=>array('verify_peer'=>false, 'verify_peer_name'=>false))));
        //exit($json);
        //{"res_code": 0,"res_message": "成功","fileListAO": {
        $json=json_decode($json, true);
        if(!$json){exit('<script>window.alert("请重新获取accesstoken");</script>');}
        echo('<div class="uk-container">');
        echo('<div class="uk-text-lead uk-text-large"><i uk-icon="icon:home;ratio:1.4"></i> '.$folders.'</div>');
        echo('<table class="uk-table uk-table-small uk-table-divider uk-table-hover">');
        foreach ($json['fileListAO']['folderList'] as $item){
            $folder_dict[$item['id']]=$item['name'];
            echo('<tr><td><i uk-icon="folder"></i> <a class="uk-link-text uk-text-bold uk-text-primary" href="?folderId='.$folderId.'/'.$item['id'].'">'.$item['name'].'</a>('.$item['fileCount'].')<span class="uk-align-right">'.$item['lastOpTime'].'</span></td></tr>');
        }
        file_put_contents($folder_dict_path, json_encode($folder_dict, JSON_UNESCAPED_UNICODE));
        foreach ($json['fileListAO']['fileList'] as $item){
            $ext=substr($item['name'],strrpos($item['name'],'.')+1);
            echo('<tr><td><i uk-icon="file"></i> <a class="uk-link-text" href="?fileId='.$item['id'].'&name='.urlencode($item['name']).'">'.$item['name'].'</a>('.file_size($item['size']).')<span class="uk-align-right">'.$item['lastOpTime'].'</span></td></tr>');
        }
        echo('</table></div>');
    }else{
        $query=[];
        $query['fileId']=$fileId;
        $url='https://api.cloud.189.cn/open/file/getFileDownloadUrl.action?'.http_build_query($query);
        $query['AccessToken']=$access_token;
        $query['Timestamp']=$timestamp;
        ksort($query);
        $signature=md5(http_build_query($query));
        $header.="\r\nAccessToken:".$access_token;
        $header.="\r\nTimestamp:".$timestamp;
        $header.="\r\nSignature:".$signature;

        $json=@file_get_contents($url, false, stream_context_create(array('http'=>array('method'=>'GET','header'=>$header,'content'=>''),'ssl'=>array('verify_peer'=>false, 'verify_peer_name'=>false))));
        $json=json_decode($json, true);
        if(!$json){exit('<script>window.alert("请重新获取accesstoken");</script>');}
        //{"res_code": 0,"res_message": "成功","fileDownloadUrl": "https://download.cloud.189.cn/file/downloadFile.action?dt=51&expired=1715225292173&sk=237d0b41-6277-46b8-a0e9-0d350c95750c_app&ufi=124331132637818636&zyc=5&token=cloud15&sig=4zt1BWunePoR1sGG5vqMcLohwn4%3D"}
        $url=$json['fileDownloadUrl'];
        echo('<div class="uk-container uk-text-center">');
        if(file_type('image')){
            echo('<img src="'.$url.'" />');
        }elseif(file_type('audio')){
            echo('<audio src="'.$url.'" controls="controls" autoplay="true"></audio>');
        }elseif(file_type('video')){
            echo('<video src="'.$url.'" controls="controls" autoplay="true"></video>');
        }else{
            echo('<script>window.location.href="'.$url.'"</script>');
        }
        echo('<br /><i uk-icon="cloud-download"></i> <a class="uk-link-text" href="'.$url.'">'.$_GET['name'].'</a>');
        echo('</div>');
    }
    
    function http_curl($url,$data,$header){
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, true);//是否返回headers信息
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_POST, true);
        //curl_setopt($curl, CURLOPT_ENCODING,'gzip');
        curl_setopt($curl, CURLOPT_POSTFIELDS , $data);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);//忽略重定向
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($curl);
        $error = ($response === false?curl_error($curl):'');
        $header = curl_getinfo($curl);
        curl_close($curl);
        return [$response,$header,$error];
    }

    function file_size($size){
        if($size>1024*1024*1024){
            return trim(number_format(floatval($size/1024/1024/1024), 2, '.', ''),'0').'G';
        }elseif($size>1024*1024){
            return trim(number_format(floatval($size/1024/1024), 2, '.', ''),'0').'M';
        }elseif($size>1024){
            return trim(number_format(floatval($size/1024), 2, '.', ''),'0').'K';
        }else{
            return floatval($size/1).'b';
        }
    }
    function file_type($type){
        $name=$_GET['name']??'';
        if($name && strpos($name,'.')!== false){
            $ext=strtolower(substr($name, strrpos($name,'.')+1));
            if(in_array($ext,['jpg','png','gif','bmp','webp','svg','ico'])){
                return 'image'==$type;
            }elseif(in_array($ext,['mp3','ogg','wav','wma','aac','flac','ape','m4a'])){
                return 'audio'==$type;
            }elseif(in_array($ext,['mp4','ogg','webm','mkv','mov','mpeg','wmv','avi','rm','rmvb','flv'])){
                return 'video'==$type;
            }
        }
        return false;
    }
?>
</body>
</html>
