<html>
<head>
<title>Checker.php</title>
<meta name="robots" content="noindex,nofollow" />
</head>
<body>
<?php
echo '<h3> Site <a href="http://'.$_SERVER["HTTP_HOST"].'" target="_blank">'.$_SERVER["HTTP_HOST"].'</a> test.</h3>';

setstart(); //отображение ошибок, задаем кодировку страницы
filesBK(); //делаем бекапы
php_get_version(); //версия PHP
checkerstart(); //классы проверок чекера 

function setstart() {
	error_reporting( E_ERROR ); //отображаем только значительные ошибки
	ini_set('display_errors', 0); //не показываем ошибки
	header('Content-Type: text/html; charset=utf-8'); //задаем кодировку страницы
}

function filesBK() {

	$htaccessfile = '.htaccess';
	$htcontent = file_get_contents($htaccessfile);
	$htbackfile = ".htaccess_checker_autobackup";	

	if (!file_exists($htaccessfile)) {
		$htaccesswrite = fopen($htaccessfile, "w");
		fwrite($htaccesswrite, "#.htaccess file");
		fclose($htaccesswrite);
	}
	
	if (file_exists('.htaccess')) {
		$htaccessfile = '.htaccess';
		$htcontent = file_get_contents($htaccessfile);
		$htbackfile = ".htaccess_checker_autobackup";
		if (file_put_contents($htbackfile, $htcontent)) {
			return 'OK';
			} else return 'not OK';
		}
	
	if (file_exists('index.php')) {
		$indexPHPfile = 'index.php';
		$indexPHPcont = file_get_contents($indexPHPfile);
		$indexphpbackfile = "index.php_checker_autobackup";
		if (file_put_contents($indexphpbackfile, $indexPHPcont)) {
			return 'OK';
			} else return 'not OK';
		}
	
	if (file_exists('index.html')) {
		$indexHTMLfile = 'index.html';
		$indexHTMLcont = file_get_contents($indexHTMLfile);
		$indexhtmlbackfile = "index.html_checker_autobackup";
		if (file_put_contents ($indexhtmlbackfile, $indexHTMLcont)) {
			return 'OK';
			} else return 'not OK';
		}
		
	if (file_exists('index.htm')) {
	$indexHTMfile = 'index.htm';
	$indexHTMcont = file_get_contents($indexHTMfile);
	$indexhtmbackfile = "index.htm_checker_autobackup";
		if (file_put_contents ($indexhtmbackfile, $indexHTMcont)) {
			return 'OK';
			} else return 'not OK';
		}
}

function php_get_version(){
	$phpversion = phpversion(); //версия PHP
	if (preg_match('|^4.*|',$phpversion)){
		echo "<b>PHP Version: <span style='color:red'>".phpversion()."<br>В настоящий момент сервис работает с сайтами на PHP выше версии 5.0</span></b><br><br>";
	} else echo "<br><b>PHP Version:  <span style='color:green'>".phpversion()." ОК</span></b><br>";
}

class Checker {

    var $_IO;
    var $_Http;

    function Exists($name){
        return function_exists($name);
    }

    function Start(){

        if($this->Exists('fopen') && $this->Exists('fwrite')){
            $this->Log('Сокеты доступны', '<span style="color:green">ok</span>');
            $this->_IO = new OldFileIO();
        } elseif($this->Exists('file_get_contents') && $this->Exists('file_put_contents')){
            $this->Log('file_get_contents-file_put_contents', '<span style="color:green">ok</span>');
            $this->_IO = new NewFileIO();
        }

        if($this->_IO === null){
            $this->Log('IO stack', 'FAIL');
            return;
        }


        if($this->Exists('fsockopen') && $this->Exists('fwrite')){
            $this->Log('cURL доступен: ', '<span style="color:green">ok</span>');
            $this->_Http = new Socket();
        } elseif($this->Exists('curl_init') && $this->Exists('curl_exec')){
            $this->Log('curl_init-curl_exec', 'ok');
            $this->_Http = new Curl();
        } elseif($this->Exists('fopen') && $this->Exists('stream_context_create')){
            $this->Log('fopen-stream_context_create', 'ok');
            $this->_Http = new NetContext();
        }

        if($this->_Http === null){
            $this->Log('HTTP stack', 'FAIL');
            return;
        }

        $this->TestHtaccess();

        if($this->Exists('apache_get_version')){
            $this->Log('ServerSoftware', apache_get_version());
        }
    }
	
    function stripos($k, $s){
        $k = strtolower($k);
        $s = strtolower($s);
        return strpos($k, $s);
    }

    function TestHtaccess(){
        $firstFile = $_SERVER['DOCUMENT_ROOT']."/testFirst.html";
        $secondFile = $_SERVER['DOCUMENT_ROOT']."/testSecond.html";
        $htaccess = $_SERVER['DOCUMENT_ROOT']."/.htaccess";

        $fistContent = "FirstPage";
        $secondContent = "RedirectPage";

        $htaccessRedirect = "RewriteEngine On" . "\n" . "RewriteRule testFirst.html /testSecond.html" . " [L,R=301]" . "\r";


        $this->_IO->CreateFile($firstFile, $fistContent);
        $this->_IO->CreateFile($secondFile, $secondContent);

        $this->_IO->FileStartAppend($htaccess, $htaccessRedirect);

        $headersArray = $this->_Http->GetHeaders($_SERVER['SERVER_NAME'], "/$firstFile");

        if(!isset($headersArray)){
            $this->Log('headers is', 'null');
            return;
        }
        foreach($headersArray as $h){
            if($this->stripos($h, 'Location') !== false){
                $this->Log(".htaccess: ", "<span style='color:green'>работает</span>");
                return;
            }
        }
        $this->Log(".htaccess", "<span style='color:red'>не работает</span>");
    }

    function Log($name, $rez){
        echo "<br>" . $name . ":\t" . "<b>" . $rez ."</b>";
    }


}

class Socket{
    function GetHeaders($host, $url){

        $stream = fsockopen($host, 80, $errno, $errstr, 30);
        if (!$stream) {
            echo "<br><span style='color:red'>Ошибка сокета: $errstr ($errno)</span><br>\n";
        }

        $out  = "GET ". $url ." HTTP/1.0\r\n";
        $out .= "Host: " . $host;
        $out .= "\r\nConnection: close";
        $out .= "\r\n\r\n";

        fwrite($stream, $out);
        $fresponse = '';
        while (!feof($stream)) {
            $fresponse .= fgets($stream);
        }
        fclose($stream);

        $fcontent = explode("\r\n\r\n", $fresponse);
        $fhead = explode("\r\n", $fcontent[0]);
        array_shift($fcontent);
        $fcontent = join("\r\n\r\n", $fcontent);

        return $fhead;
    }
}

class Curl{
    function GetHeaders($host, $url){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://' . $host . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $headers = substr($result,0,$info['header_size']-4);
        return preg_split('#\r\n#',$headers);
    }
}

class NetContext{
    function GetHeaders($host, $url){
        $headers = array(
            'http'=>array(
                'method'=>"GET",
                'max_redirects' => '1',
                'ignore_errors' => '1',
            )
        );

        $context = stream_context_create($headers);
        $stream = @fopen('http://' . $host . $url, 'r', false, $context);


        $responseMeta = stream_get_meta_data($stream);
        fclose($stream);

        $responseHeaders;
        if(isset($responseMeta['wrapper_data']['headers'])){
            $responseHeaders = $responseMeta['wrapper_data']['headers'];
        } else {
            $responseHeaders = $responseMeta['wrapper_data'];
        }

        return $responseHeaders;
    }
}


class BaseIO{
	function IsFileExists($filename){
		return file_exists($filename);
	}
}

class NewFileIO extends BaseIO{
    function FileStartAppend($fileName, $fileContent){
        $oldContent = file_get_contents($fileName);
        $newContent = $fileContent . "\n" . $oldContent;
        if(file_put_contents($fileName, $newContent) === false){
            return false;
        }
        return true;
    }

    function CreateFile($fileName, $fileContent){
        if(file_put_contents($fileName, $fileContent) === false){
            return false;
        }
        return true;
    }
}

class OldFileIO extends BaseIO{
    function FileStartAppend($fileName, $fileContent){

        $handle = @fopen($fileName, "a+");
        if($handle == false){
            return false;
        }

        $oldContent = '';
        while (!feof($handle)) {
            $oldContent .= fgets($handle);
        }
        fclose($handle);

        $newContent = $fileContent . "\n" . $oldContent;

        $handle = fopen($fileName, "w+");

        if(fwrite($handle,$newContent) === false){
            return false;
        }

        return true;
    }

    function CreateFile($fileName, $fileContent){
        $handle = @fopen($fileName, "w+");
        if(fwrite($handle,$fileContent) === false){
            return false;
        }
        return true;
    }
}
function checkerstart() {
	$checker = new Checker(); //остальные тесты чекера
	$checker->Start();
	echo "<br>";
}
//получить содержимое страницы
function getpage($nadres){
	$user_agent = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.124 Safari/537.36";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$nadres);
	curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_VERBOSE, false);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSLVERSION, 3);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

	$page = curl_exec($ch);
	curl_close($ch);
	return $page;
}

?>
</body>
</html>