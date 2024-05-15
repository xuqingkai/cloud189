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
    $username="";
    $password="";
    $accessToken='';
    $access_token_file_path='./access_token.txt';
    $folder_dict_path='./folder_dict.json';


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
    }elseif($folderId){
        $folders='<a href="?" class="uk-text-bottom">首页</a>';
        if($folderId){
            $folderIds='';
            foreach (explode('/', $folderId) as $key){
                $folderIds.='/'.$key;
                $folders.=' / <a href="?folderId='.substr($folderIds,1).'" class="uk-text-bottom">'.$folder_dict[$key].'</a>';
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
        $query['AccessToken']=$accessToken;
        $query['Timestamp']=$timestamp;
        ksort($query);
        $signature=md5(http_build_query($query));
        $header.="\r\nAccessToken:".$accessToken;
        $header.="\r\nTimestamp:".$timestamp;
        $header.="\r\nSignature:".$signature;

        $json=@file_get_contents($url, false, stream_context_create(array('http'=>array('method'=>'GET','header'=>$header,'content'=>''),'ssl'=>array('verify_peer'=>false, 'verify_peer_name'=>false))));
        //exit($json);
        //{"res_code": 0,"res_message": "成功","fileListAO": {
        $json=access_token_check($json);
        echo('<div class="uk-container">');
        echo('<div class="uk-text-lead uk-text-large"><i uk-icon="icon:home;ratio:1.4"></i> '.$folders.'</div>');
        echo('<table class="uk-table uk-table-small uk-table-divider uk-table-hover">');
        foreach ($json['fileListAO']['folderList'] as $item){
            $folder_dict[$item['id']]=$item['name'];
            echo('<tr><td><i uk-icon="folder"></i> <a class="uk-link-text uk-text-bold uk-text-primary" href="?folderId='.$folderId.'/'.$item['id'].'">'.$item['name'].'</a>('.$item['fileCount'].')<span class="uk-align-right">'.$item['lastOpTime'].'</span></td></tr>');
        }
        file_put_contents($folder_dict_path, json_encode($folder_dict, JSON_UNESCAPED_UNICODE));
        foreach ($json['fileListAO']['fileList'] as $item){
            echo('<tr><td><i uk-icon="file"></i> <a class="uk-link-text" href="?fileId='.$item['id'].'">'.$item['name'].'</a>('.file_size($item['size']).')<span class="uk-align-right">'.$item['lastOpTime'].'</span></td></tr>');
        }
        echo('</table></div>');
    }elseif($fileId){
        $query=[];
        $query['fileId']=$fileId;
        $url='https://api.cloud.189.cn/open/file/getFileDownloadUrl.action?'.http_build_query($query);
        $query['AccessToken']=$accessToken;
        $query['Timestamp']=$timestamp;
        ksort($query);
        $signature=md5(http_build_query($query));
        $header.="\r\nAccessToken:".$accessToken;
        $header.="\r\nTimestamp:".$timestamp;
        $header.="\r\nSignature:".$signature;

        $json=@file_get_contents($url, false, stream_context_create(array('http'=>array('method'=>'GET','header'=>$header,'content'=>''),'ssl'=>array('verify_peer'=>false, 'verify_peer_name'=>false))));
        $json=access_token_check($json);
        //{"res_code": 0,"res_message": "成功","fileDownloadUrl": "https://download.cloud.189.cn/file/downloadFile.action?dt=51&expired=1715225292173&sk=237d0b41-6277-46b8-a0e9-0d350c95750c_app&ufi=124331132637818636&zyc=5&token=cloud15&sig=4zt1BWunePoR1sGG5vqMcLohwn4%3D"}
        echo('<div class="uk-container">');
        echo('<a class="uk-link-text" href="'.$json['fileDownloadUrl'].'"><br />'.$json['fileDownloadUrl'].'</a>');
        echo('</table></div>');
        exit('<script>window.location.href="'.$json['fileDownloadUrl'].'"</script>');
    }else{
        echo('<a href="?folderId=-11"><strong>打开</strong></a><br/>');
        echo('获取AccessToken方法：登录https://h5.cloud.189.cn/，下载文件前开启抓包看getFileDownloadUrl.action请求的header信息就有');
    }
    
    function access_token_check($data){
        global $header;
        global $username;
        global $password;
        global $access_token_file_path;
        $data=json_decode($data, true);
        if(!$data){
            list($location,$cookies)=get_location_cookie('https://cloud.189.cn/api/portal/loginUrl.action?redirectURL=https%3A%2F%2Fcloud.189.cn%2Fmain.action');
            $queryString=substr($location,strpos($location,'?')+1);
            parse_str($queryString, $query);
            //exit(json_encode($query));
            //{"appId":"cloud","lt":"0D47E2B194F7217059A077742BAD077C7C29312B46BA04E8C64A0F14FC63F3E2E1D55CE08760ADED12FD871161CFE03CE252C8CC44D159B5021B798C98CC857DE651EA200402E09E5CC85E74B5A8C7C5C982973A","reqId":"01bd0019eff54b27816979158cc5af00"}
            $header="Origin:https://open.e.189.cn";
            $header.="\r\nReferer:".$location;
            $header.="\r\nlt:".$query['lt'];
            $header.="\r\nreqId:".$query['reqId'];
            
            //file_put_contents('log.txt', "\r\nheader-----------------\r\n".$header, FILE_APPEND);
            //file_put_contents('log.txt', "\r\ncookies-----------------\r\n".json_encode($cookies), FILE_APPEND);
    
            //$header.="\r\nCookie:".implode('; ',$cookies);
            //$header.="\r\nUser-Agent:".$userAgent;
            $header.="\r\nContent-type:application/x-www-form-urlencoded;charset=UTF-8";
            $header.="\r\nAccept:application/json;charset=UTF-8";
            //exit($header);
    
            $appConf=@file_get_contents('https://open.e.189.cn/api/logbox/oauth2/appConf.do', false, stream_context_create(array('http'=>array('method'=>'POST','header'=>$header,'content'=>'appKey='.$query['appId'].'&version=2.0'),'ssl'=>array('verify_peer'=>false, 'verify_peer_name'=>false))));
            //file_put_contents('log.txt', "\r\nappConf-----------------\r\n".$appConf, FILE_APPEND);
    
            $appConf=json_decode($appConf, true);
            $encryptConf=@file_get_contents('https://open.e.189.cn/api/logbox/config/encryptConf.do', false, stream_context_create(array('http'=>array('method'=>'POST','header'=>$header,'content'=>'appId='.$query['appId']),'ssl'=>array('verify_peer'=>false, 'verify_peer_name'=>false))));
            //{"result":0,"data":{"upSmsOn":"0","pre":"{NRP}","preDomain":"card.e.189.cn","pubKey":"MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCZLyV4gHNDUGJMZoOcYauxmNEsKrc0TlLeBEVVIIQNzG4WqjimceOj5R9ETwDeeSN3yejAKLGHgx83lyy2wBjvnbfm/nLObyWwQD/09CmpZdxoFYCH6rdDjRpwZOZ2nXSZpgkZXoOBkfNXNxnN74aXtho2dqBynTw3NFTWyQl8BQIDAQAB"}}
            //file_put_contents('log.txt', "\r\nencryptConf-----------------\r\n".$encryptConf, FILE_APPEND);
            $encryptConf=json_decode($encryptConf, true);
            
            $content=[];
            $content['version']='2.0';
            $content['apToken']='';
            $content['appKey']=$query['appId'];
            $content['version']='2.0';
            $content['accountType']=$appConf['data']['accountType'];
            $content['userName']=$encryptConf['data']['pre'].rsa_public_encrypt($username, $encryptConf['data']['pubKey']);
            $content['epd']=$encryptConf['data']['pre'].rsa_public_encrypt($password, $encryptConf['data']['pubKey']);
            $content['captchaType']='';
            $content['validateCode']='';
            $content['smsValidateCode']='';
            $content['captchaToken']='';
            $content['returnUrl']=$appConf['data']['returnUrl'];
            $content['mailSuffix']=$appConf['data']['mailSuffix'];
            $content['dynamicCheck']='false';
            $content['clientType']=$appConf['data']['ClientType'].'';
            $content['cb_SaveName']='3';
            $content['isOauth2']=$appConf['data']['isOauth2']?'true':'false';
            $content['state']='';
            $content['paramId']=$appConf['data']['paramId'];
            $content=http_build_query($content);
            
            $loginSubmit=@file_get_contents('https://open.e.189.cn/api/logbox/oauth2/loginSubmit.do', false, stream_context_create(array('http'=>array('method'=>'POST','header'=>$header,'content'=>$content),'ssl'=>array('verify_peer'=>false, 'verify_peer_name'=>false))));
            //{"result": 0,"msg": "登录成功","toUrl": "https://cloud.189.cn/api/portal/callbackUnify.action?redirectURL=https://cloud.189.cn/main.action&appId=cloud¶s=11198F4EAFE579AED3ED2B5C5D5DD85DF4B93152C255B9E44635F9AE490FAB44FB91BE2E8AA6C7F1DA01E6369037C87D622AA7949434F1A5F9624BD30EE955506B7C3AFDBFF6582111CFFA0BE52C943E5FB7DC7BDC5D5BDBF207D69EBBA3BA8F7C23F746F0793F2BB77A53980F5C43F0824AAE01B652DC750B01DA35A1FB8F52DB149D96E51A6AA1FF8461BBEE7476D7D9C4E314969E12666CD21316E8D2D54B9329DD6EEA729186D9B2BE6908128C7CA81B983DED609B1C65D9E7F1F1E610AC4ECAF4813697FBDCFCC9721F2F31C31B1B5CE3221D6DFB86134961DEC49E5670A7AE79F7AAE4CD9C408DB31229E11DE1A642B8886E52CAB47F75AAC19A5609AA9575F1A3808E69A41D4710D0A7021AC1A729F569A7CD5B5AC79466F8FC26404ABE83F14F43D23821F095A730B38B9C992EC76AD8C65EAFD87430881A94E5738E9E02522862E6B39A461CFEF07969A906B25F327094A87F206D928E9DD85D5A780A29E7706811BCFC8D9BE54CB11E4DA35DFC229CCF451F87EB9E2F6ED3884CCA7746BF8EB6162749E6C5A5BE0AB22CB1A7A8B29836E7AC00C1F5E3557B3B7728F380E6B82AFBD46A4473449217D58B7FE83810B6C5031199321D665AF6C14AD85A17B5B4943040AF220D5F00&sign=5275B4C97DB7B5533453749654E09E2A4E720A26"}
            //echo($loginSubmit);
            //file_put_contents('log.txt', "\r\nloginSubmit-----------------\r\n".$loginSubmit, FILE_APPEND);
            $loginSubmit=json_decode($loginSubmit, true);
            
            list($location,$cookies)=get_location_cookie($loginSubmit['toUrl']);
            //file_put_contents('log.txt', "\r\nlocation-----------------\r\n".json_encode($location), FILE_APPEND);
            //file_put_contents('log.txt', "\r\ncookies-----------------\r\n".json_encode($cookies), FILE_APPEND);
    
            $header=[];
            $header[]='application/x-www-form-urlencoded;charset=UTF-8';
            $header[]="Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7";
            $header[]="Cookie:".$cookies[1];
            $header[]="User-Agent:Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Mobile Safari/537.36 Edg/124.0.0.0";
            //exit(implode("\r\n", $header));
            //file_put_contents('log.txt', "\r\nheader-----------------\r\n".implode("\r\n", $header), FILE_APPEND);
    
            @file_get_contents('https://cloud.189.cn/v2/getUserBriefInfo.action?noCache='.md5(time().''.rand(10000,99999)));
            $html=@file_get_contents('https://api.cloud.189.cn/open/oauth2/ssoH5.action', false, stream_context_create(array('http'=>array('method'=>'GET','header'=>'','content'=>''))));
            $response_header=$http_response_header;
            //list($html,$head,$error)=http_curl('https://api.cloud.189.cn/open/oauth2/ssoH5.action','',$header);
            //file_put_contents('log.txt', "\r\nresponse_header-----------------\r\n".json_encode($response_header), FILE_APPEND);
            exit('<textarea>'.json_encode($response_header).'</textarea><hr/><textarea>'.htmlspecialchars($html).'</textarea><hr/><textarea>'.implode("\r\n", $header).'</textarea>');
            
            $access_token='';
            file_put_contents($access_token_file_path, $access_token);
            exit('<script>window.alert("token失效！刷新即可");window.onbeforeunload=null;window.location.reload(true);</script>');
        }
        return $data;
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
    function get_location_cookie($url){
        $header=get_headers($url, 1);
        //exit(json_encode($header));
        if (strpos($header[0], '301') !== false || strpos($header[0], '302') !== false) {
            if (is_array($header['Location'])) {
                $url=$header['Location'][count($header['Location']) - 1];
            }else{
                $url=$header['Location'];
            }
        }
        $cookies=[];
        foreach ($header['Set-Cookie'] as $cookie){
            $cookies[]=substr($cookie,0,strpos($cookie,';'));
        }
        return [$url,$cookies];
    }
    function rsa_public_encrypt($data, $rsa_public_key, $hex=true){
        $rsa_public_key="-----BEGIN PUBLIC KEY-----\n" . wordwrap($rsa_public_key, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        openssl_public_encrypt($data, $result, $rsa_public_key);
        $result=base64_encode($result);
        if($hex){ $result=base64tohex($result); }
        return $result;
    }
    function base64tohex($a) {
        $bi_rm='0123456789abcdefghijklmnopqrstuvwxyz';
        $d = '';
        $e = 0;
        $c = 0;
    
        for ($i = 0; $i < strlen($a); $i++) {
            $m = $a[$i];
            if ($m !== '=') {
                $v = strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/', $m);
    
                if ($e === 0) {
                    $e = 1;
                    $d .= $bi_rm[$v >> 2];
                    $c = 3 & $v;
                } elseif ($e === 1) {
                    $e = 2;
                    $d .= $bi_rm[$c << 2 | $v >> 4];
                    $c = 15 & $v;
                } elseif ($e === 2) {
                    $e = 3;
                    $d .= $bi_rm[$c];
                    $d .= $bi_rm[$v >> 2];
                    $c = 3 & $v;
                } else {
                    $e = 0;
                    $d .= $bi_rm[$c << 2 | $v >> 4];
                    $d .= $bi_rm[15 & $v];
                }
            }
        }
    
        if ($e === 1) {
            $d .= $bi_rm[$c << 2];
        }
    
        return $d;
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
    
?>
</body>
</html>
