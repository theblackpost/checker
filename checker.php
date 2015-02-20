<!DOCTYPE html>
<html>
<head>


<title>Checker.php</title>
<meta name="robots" content="noindex,nofollow" />
</head>
<body>
<?php
echo '<h1> Site <a href="http://'.$_SERVER["HTTP_HOST"].'" target="_blank">'.$_SERVER["HTTP_HOST"].'</a> checker. Ver. 2.0150127</h1>';
// php_value memory_limit 192M можно добавить в .htaccess если мало при проверке showmemory


#################################################################################################
/*                  																		   ##
следующие несколько строк - это функции, отвечающие за проверку той или иной части сайта       ##
если на каком-то этапе проверка перестает работать или ведет себя не так, как ожидалось,       ##
соответствующий пункт необходимо закомментировать (добавить в начале строки символ # или //    ##
*/																							   ##
#################################################################################################




setstart(); //отображение ошибок, задаем кодировку страницы
filesBK(); //!!!  ВАЖНО  !!! тут закоментировать, если не работает при ошибке создания backup htaccess + ОБЯЗАТЕЛЬНО в самом низу erase_all();
php_get_version(); //Проверяем версию PHP
cms_curl_check(); //проверка CMS
toolza_curl_check(); //проверка стоит ли тулза
ReplaceSystemVars(); //заменяем системные переменные
diffinfo(); //инфо ns-записи, path to file
FileCreateRead(); //создание папки
modrewritecheck(); //проверяем включен ли mod_rewrite
memorylimit(); // выводим memory_limit (если меньше 64 и есть проблемы с работой тулзы - можно поставить  php_value memory_limit 192M или кратно выше в .htaccess в начало
shutdown(); //если showmemory показывает Error - продолжаем с checkerstart(); и завершаем erase_all();
showmemory();	// проверка memory после index.php
checkerstart(); //все оставшиеся проверки чекера (fopen, cURL version, fsockopen, redirect, Software, modules, phpinfo)
erase_all(); //стираем за собой все временные файлы, папки и т.п.












#############     сами функции       ###############


function shutdown() {
register_shutdown_function('checkerstart'); 
register_shutdown_function('erase_all'); 
}

function setstart() {
    error_reporting( E_ERROR ); //отображаем только значительные ошибки
    ini_set('display_errors', 1); //не показываем ошибки
    header('Content-Type: text/html; charset=utf-8'); //задаем кодировку страницы
    header("Expires: Tue, 1 Jul 2003 05:00:00 GMT"); //ниже нафиг кэш
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Pragma: no-cache");
    set_time_limit(6000);
}

function filesBK() {
	echo '<b>Checker BackUps:</b><br>';
	echo '<ul>';

	$htaccessfile = '.htaccess';
	$htcontent = file_get_contents($htaccessfile);
	$htbackfile = ".htaccess_checker_autobackup";	

	if (!file_exists($htaccessfile)) {
		$htaccesswrite = fopen($htaccessfile, "w");
		fwrite($htaccesswrite, "#.htaccess file");
		fclose($htaccesswrite);
		echo "<li>.htaccess created</li>";
	} else {
		echo "<li>.htaccess already exist</li>";
	}
	
	if (file_exists('.htaccess')) {
		$htaccessfile = '.htaccess';
		$htcontent = file_get_contents($htaccessfile);
		$htbackfile = ".htaccess_checker_autobackup";
		if (file_put_contents($htbackfile, $htcontent)) {
			echo '<li>.htaccess backup created</li>';
			} else exit ('<li style="color:red">cant create .htaccess backup</li>');
		}
		else echo ('<li style="color:#993300">file .htaccess is not exist</li>');
	
	if (file_exists('index.php')) {
		$indexPHPfile = 'index.php';
		$indexPHPcont = file_get_contents($indexPHPfile);
		$indexphpbackfile = "index.php_checker_autobackup";
		if (file_put_contents($indexphpbackfile, $indexPHPcont)) {
			echo '<li>index.php backup created</li>';}
			else exit ('<li style="color:red">cant create index.php backup</li>');
		}
		else echo ('<li style="color:#993300">file index.php is not exist</li>');
	
	if (file_exists('index.html')) {
		$indexHTMLfile = 'index.html';
		$indexHTMLcont = file_get_contents($indexHTMLfile);
		$indexhtmlbackfile = "index.html_checker_autobackup";
		if (file_put_contents ($indexhtmlbackfile, $indexHTMLcont)) {
			echo '<li>index.html backup created</li>';}
			else exit ('<li style="color:red">cant create index.html backup</li>');
		}
		else echo ('<li style="color:#993300">file index.html is not exist</li>');
		
	if (file_exists('index.htm')) {
	$indexHTMfile = 'index.htm';
	$indexHTMcont = file_get_contents($indexHTMfile);
	$indexhtmbackfile = "index.htm_checker_autobackup";
		if (file_put_contents ($indexhtmbackfile, $indexHTMcont)) {
			echo '<li>index.htm backup created</li>';}
			else exit ('<li style="color:red">cant create index.htm backup</li>');
		}
		else echo ('<li style="color:#993300">file index.htm is not exist</li>');
		
	echo '</ul>';
}

function php_get_version(){
	$phpversion = phpversion(); //версия PHP
	if (preg_match('|^4.*|',$phpversion)){
		echo "<b>PHP Version: <span style='color:red'>".phpversion()."<br>it's n­ecessary to have 5+ PHP version for correct toolza working</span></b><br><br>";
	} else echo "<b>PHP Version:  <span style='color:green'>".phpversion()."</span></b><br>";
}

//если curl есть, тогда проверяем ЦМС и наличие тулзы
function cms_curl_check(){
	if (function_exists('curl_init')) {
		cmscheck();
	}
}
function toolza_curl_check(){
	if (function_exists('curl_init')) {
		toolzacheck(); 
	}
}

//CMS
function curl_redir_exec($ch) {
        static $curl_loops = 0;
        static $curl_max_loops = 3; # Максимальное количество перебросов.
        if ($curl_loops >= $curl_max_loops) {
                $curl_loops = 0;
                return FALSE;
			}
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        list($header, $data) = explode("\n\n", $data, 2);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code == 301 || $http_code == 302) {
                $matches = array();
                preg_match('/Location:(.*?)\n/', $header, $matches);
                $url = @parse_url(trim(array_pop($matches)));
                if (!$url) {
                    $curl_loops = 0;
                    return $data;
					}
                $last_url = parse_url(curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
                if (!$url['scheme'])
                        $url['scheme'] = $last_url['scheme'];
                if (!$url['host'])
                        $url['host'] = $last_url['host'];
                if (!$url['path'])
                        $url['path'] = $last_url['path'];
                $new_url = $url['scheme'] . '://' . $url['host'] . $url['path'] . ($url['query']?'?'.$url['query']:'');
                curl_setopt($ch, CURLOPT_URL, $new_url);
                return curl_redir_exec($ch);
        } else {
                $curl_loops=0;
                return $data;
        }
}

function grab($site) {
		if (function_exists('mb_detect_encoding')){
			if(mb_detect_encoding($site) != "ASCII"){ //если сайт в кириллице переводим в punycode
				include("http://xtoolza.ru/q/cms/idna_convert.class.php");
				$IDN = new idna_convert(array('idn_version' => '2008'));
				$site=$IDN->encode($site);
			}
		}
        $ch = curl_init();
		$user_agent = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.124 Safari/537.36";
        curl_setopt($ch, CURLOPT_URL, $site);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $data = curl_exec($ch); /* curl_exec($ch); */
        curl_close($ch);
        if ($data)
            return $data;
        else
            return FALSE;
}

function check($html) {
        $cms = array(
				"3dcart" => array('Software by <a href="http://www.3dcart.com">3dcart</a>','!--START: 3dcart stats--','!--END: 3dcart stats--'),
				"a5 SaaS" => array('!-- siteByName_','img src="/img/zones/a5.ru/site_copyright.png','Создано на конструкторе сайтов - A5.ru'),	
                "ABO CMS" => array("Design and programming (for ABO.CMS)","ABO.CMS"),	
                "Adobe CQ5" => array('$CQ.getScript(','$CQ(function()','"jquery": "../CQ/main"','/js/CQ/main.js"','/cq5/css/main.css','/cq5/css/print.css'),	
                "AdVantShop.NET" => array('Работает на <a data-cke-saved-href="http://www.advantshop.net" href="http://www.advantshop.net"'),	
                "AsciiDoc" => array('meta name="generator" content="AsciiDoc'),	
                "Altedit SaaS" => array('name="altedit_widget_header_menu','class="altedit_widget"','name="altedit_region','class="altedit-row"','Разработано на платформе &quot;<a href="http://altedit.ru"','.alteditBlockWrapper'),	
                "Ametys" => array('meta content="Ametys','div id="ametys-cms-zone'),	
                "Amiro CMS" => array("/amiro_sys_css.php?","/amiro_sys_js.php?","-= Amiro.CMS (c) =-","Работает на Amiro.CMS",'Сайт работает на Amiro.CMS'),	
                "Apache Lenya" => array('<alt="Built with Apache Lenya"','content="Apache Lenya'),	
                "AxCMS" => array('Build and published by AxCMS.net','content="Axinom'),	
				"Bigcommerce" => array('shortcut icon" href="http://cdn2.bigcommerce.com/',"'platform':'bigcommerce'",'link rel="shortcut icon" href="http://cdn3.bigcommerce.com','link rel="shortcut icon" href="http://cdn4.bigcommerce.com','link rel="shortcut icon" href="http://cdn1.bigcommerce.com/','link rel="shortcut icon" href="http://cdn2.bigcommerce.com/','link href="http://cdn1.bigcommerce.com/','link href="http://cdn3.bigcommerce.com/'),	
                "Bigace" => array('Работает на BIGACE','Site is running BIGACE','meta name="generator" content="BIGACE'),	
                "Bitrix" => array("/bitrix/templates/", "/bitrix/js/", "/bitrix/admin/"),	
                "BLOX CMS" => array('div id="blox-html-container"','Powered by <a href="http://bloxcms.com" title="BLOX Content Management System"'),	
                "BM Shop 5" => array('Разработка сайта — <a href="http://renua.ru" target="_blank">Renua</a><br/> проект <a href="http://bmshop5.ru" target="_blank">bmshop5','Создание интернет-магазинов <a href="http://bmshop.ru" target="_blank">BmShop'),	
				"Cargo" => array('type="text/javascript" src="a/js/cargo.js','meta name="cargo_title" content=','Cargo.IncludeSocialMedia({','type="application/rss+xml" title="Cargo feed"','Running on <a href="http://cargocollective.com">Cargo</a>'),
				"CMS BS" => array('meta name="GENERATOR" content="CMS-BS"'),
				"Bricolage" => array('meta name="generator" content="Bricolage','is powered by Bricolage'),
				"BrowserCMS" => array('meta name="generator" content="BrowserCMS'),
				"Business Catalyst" => array('rel="stylesheet" href="/CatalystStyles/','src="/CatalystScripts','businesscatalyst.com/favicon.ico"'),
				"Chameleon" => array('meta name="generator" content="Chameleon Content Management System - chameleon-cms.com'),
				"CMSimple" => array('meta name="generator" content="CMSimple','Сайт работает на CMSimple','Powered by CMSimple','www.cmsimplewebsites.com">Designed By CmSimpleWebsites.com'),	
				"CMS Made Simple" => array("Released under the GPL - http://cmsmadesimple.org",),	
				"CommonSpot" => array('var emptyimg = "/commonspot/','Powered by CommonSpot','commonspot.csPage'),					
				"Cotonti" => array('meta name="generator" content="Cotonti'),	
                "Concrete5" => array("/concrete/js/", "concrete5 - 5.","/concrete/css/",'IMAGE_PATH="/concrete/','meta name="generator" content="concrete5'),	
                "Contao" => array("This website is powered by Contao Open Source CMS", 'link rel="stylesheet" href="system/contao.css','src="tl_files/','a href="tl_files/'),	
                "Contenido CMS" => array('meta name="generator" content="CMS CONTENIDO','meta name="generator" content="CMS Contenido'),	
                "Contensis CMS" => array('meta name="GENERATOR" content="Contensis CMS'),	
                "Convio" => array('CONVIO.pageUserName','CONVIO.pageSessionID'),	
                "CoreMedia" => array('content="CoreMedi'),	
                "CPG Dragonfly" => array('meta name="generator" content="CPG Dragonfly CMS'),	
                "CS Cart" => array("/skins/basic/customer/addons/","/skins/basic/customer/images/icons/favicon.ico","/auth-loginform?return_url=index.php","/index.php?dispatch=auth.recover_password","cm-popup-box hidden","cm-popup-switch hand cart-list-icon","cart-list hidden cm-popup-box cm-smart-position","index.php?dispatch=checkout.cart","cm-notification-container","/index.php?dispatch=pages.view&page_id="),	
                "Danneo CMS" => array("Danneo Русская CMS", 'content="CMS Danneo','META NAME="GENERATOR" CONTENT="Danneo CMS','meta name="generator" content="CMS Danneo'),	
                "Demandware" => array("Demandware Analytics code", 'shortcut icon" type="image/png" href="http://demandware.edgesuite.net/','link rel="stylesheet" href="http://demandware.edgesuite.net/','img src="http://demandware.edgesuite.net/'),	
                 "DataLife Engine" => array("DataLife Engine Copyright", "index.php?do=lostpassword", "/engine/ajax/dle_ajax.js","/engine/opensearch.php","/index.php?do=feedback","/index.php?do=rules","/?do=lastcomments",'meta name="generator" content="DataLife Engine'),		
                 "diafan.CMS" => array('http://www.diafan.ru/'),	
                "Discuz!" => array('- Powered by Discuz!</title>','meta name="generator" content="Discuz!','meta name="author" content="Discuz! Team and Comsenz UI Team"','<p>Powered by <b>Discuz!</b>','div id="discuz_bad_','Powered by <strong><a href="http://www.discuz.net"',"discuz_uid = '0'"),	
                "Divolta CMS" => array('Разработка сайта <a href="http://divolta.com.ua'),	
                "Django CMS" => array('meta name="generator" content="Django-CMS'),	
                "Drupal" => array("Drupal.settings","Drupal 7 (http://drupal.org)","misc\/drupal.js","drupal_alter_by_ref","/sites/default/files/css/css_","/sites/all/files/css/css_",'text/javascript" src="/misc/drupal.js'),	
                "DokuWiki" => array("DokuWiki Release"),	
                "DotNetNuke" => array('meta id="MetaGenerator" name="GENERATOR" content="DotNetNuke','by DotNetNuke Corporation','meta id="MetaDescription" name="DESCRIPTION" content=','name="COPYRIGHT" content="Copyright 2015 by DotNetNuke Corporation'),	
                "Dynamicweb" => array('meta name="Generator" content="Dynamicweb','meta name="generator" content="Dynamicweb'),	
                "e107" => array("This site is powered by e107","text/javascript' src='/content_files/e107.js","stylesheet' href='/content_files/e107.css",'Powered by e107 website system','/e107_files/e107.css','/e107_files/e107.js','img src="/e107_themes'),	
                "Edicy SaaS" => array("http://stats.edicy.com:8000/tracker.js","http://static.edicy.com/assets/site_search/"),	
                "Ektron" => array("EktronClientManager","Ektron.PBSettings","ektron.modal.css","Ektron/Ektron.WebForms.js","EktronSiteDataJS","/Workarea/java/ektron.js","Amend the paths to reflect the ektron system"),	
                "Ekwid SaaS" => array("ecwid_product_browser_scroller","push-menu-trigger ecwid-icons-menu","ecwid-starter-site-links","ecwid-loading loading-start",'var ecwid_ProductBrowserURL','script type="text/javascript" src="http://app.ecwid.com/script.js'),	
                "EPiServer CMS" => array('meta name="generator" content="EPiServer','meta name="EPi.ID','!-- EPi metatags --','meta name="generator" content="http://www.episerver.com','meta name="Author-Template" content="EPiServer CSS design','meta name="EPi.Description','meta name="EPi.Keywords'),	
                "eSyndiCat" => array('meta name="generator" content="eSyndiCat','Powered by <a href="http://www.esyndicat.com/">eSyndiCat Directory Software'),	
                "Explay CMS" => array('meta name="generator" content="Explay CMS','Engine &copy; <a href="http://www.explay.su/">Explay CMS','meta name="generator" content="EXPLAY Engine CMS"','alt="Explay Engine CMS"'),	
                "Express Site" => array('"http://www.expresssite.ru">изготовление сайтов - www.expresssite.ru</a>'),
                "ExpressionEngine" => array('"http://www.expresssite.ru">изготовление сайтов - www.expresssite.ru</a>','alt="Expression Engine"border="0"/></a>'),
                "eZ Publish" => array('img src="/var/ezflow_site','img src="/design/ezflow','meta name="generator" content="eZ Publish','import url(/extension/ezwebin/design/','link rel="stylesheet" type="text/css" href="/var/ezflow_site'),	
                "FlexCMP" => array("meta name='generator' content='FlexCMP",'FlexCMP - CMS per Siti Accessibili'),	
                "Flexcore CMS" => array("<!-- Oliwa-pro service -->"),	
                "Fo.ru SaaS" => array("MLP_NAVIGATION_MENU_ITEM_START","MLP_WINDOW_HEAD","/MLP_WINDOW_END","MLP_NAVIGATION_MENU_ITEM_END","window.location.replace('http://fo.ru/signup"),
                "Gamburger CMS" => array('<span class="web"><a href="http://gamburger.ru/" target="_blank">','/templates/default/images/gamburger.png'),	
                "GD SiteManager" => array("name='generator' content='GD SiteManager'"),	
                "Geeklog" => array('var geeklog = {'),	
                "GetSimple" => array('meta name="generator" content="GetSimple','Powered by  GetSimple'),	
                "GitHub Pages" => array('Powered by <a href="http://pages.github.com">GitHub Pages</a>','a href="https://github.com/bip32/bip32.github.io">GitHub Repository</a>'),	
                "Google Sites" => array('class="powered-by"><a href="http://sites.google.com','\u003dhttps://sites.google.com/','meta itemprop="image" content="https://sites.google.com/','meta property="og:image" content="https://sites.google.com/'),	
                "Gollos SaaS" => array('<meta name="generator" content="Gollos.com, <script src="http://s4.golloscdn.com/'),	
                "Government Site Builder" => array('content="Government Site Builder'),	
                "Graffiti CMS" => array('meta name="generator" content="Graffiti','a title="Powered by Graffiti CMS" href="http://graffiticms.com'),
                "GX WebManager" => array('meta name="generator" content="GX WebManager','meta name="Generator" content="GX WebManager'),
                "Homestead" => array('meta name="generator" content="Homestead SiteBuilder','link rel="stylesheet" href="http://www.homestead.com'),	
                "HostCMS" => array("/hostcmsfiles/",'<!-- HostCMS Counter -->','type="application/rss+xml" title="HostCMS RSS Feed"'),	
				"Hotaru CMS" => array('meta name="generator" content="Hotaru'),	
                "Hotlist.biz SaaS" => array("hotengine-hotlist_logo","Аренда и Создание интернет магазина Hotlist.biz","hotengine-hotcopyright","hotlist.biz/ru/?action=logout","hotengine-dialog-email","hotengine-shop-cart-message-empty-cart","hotengine-footer-copyright","hotengine-counters",'class="hotengine-seo-likeit"','class="hotengine-footer-copyright"','Powered by <img class="hotengine-hotcopyright'),	
                "Howbay SaaS" => array("http://rtty.howbay.ru/","howbay-snapprodnamehldr","Аренда онлайн магазина howbay.ru"),	
                "IBM WebSphere Portal" => array('section class="ibmPortalControl',':ibmCfg.portalConfig.',"var pageMenuURL = '/wps/portal/",'href="/wps/portal/'),	
				"Indexhibit" => array("Built with <a href='http://www.indexhibit.org/'>Indexhibit",'you must provide a link to Indexhibit on your site someplace','Visit Indexhibit.org for more information!'),
                "inDynamic" => array("Система управления сайтом и контекстом (cms) - inDynamic",'Управление сайтом — <a href="http://www.indynamic.ru/">inDynamic'),	
                "Infopark" => array("meta content='https://www.infopark.com/"),	
                "InSales SaaS" => array("InSales.formatMoney", ".html(InSales.formatMoney","http://assets3.insales.ru/assets/","http://assets2.insales.ru/assets/","http://static12.insales.ru","Insales.money_format",'--InsalesCounter --'),	
                "InstantCMS" => array("InstantCMS - www.instantcms.ru","/templates/instant/css/popalas.css","/templates/instant/css/siplim.css",'link href="/templates/_default_/css/styles.css"','href="http://www.instantcms.ru/" title="Работает на InstantCMS"','meta name="generator" content="InstantCMS'),	
                "Introweb" => array('href="http://introweb.ru/">Создание сайтов</a>','<a href="http://www.introweb.ru">Создание сайта - introweb.ru</a>'),	
                "Imperia CMS" => array('meta name="generator" content="IMPERIA'),	
                "ImpressCMS" => array('meta name="generator" content="ImpressCMS"'),	
                "Image CMS" => array('meta name="generator" content="ImageCMS"','name="cms_token" />'),	
                "ImpressPages" => array('/ip_cms/','ip_themes','ip_libs','ip_plugins','class="ipWidget ipWidget-Html','class="ipWidget ipWidget-Image','content="ImpressPages'),	
                "IP.Board" => array("!--ipb.javascript.start--","IBResouce\invisionboard.com",'/forum/index.php?act=boardrules','Powered By IP.Board'),	
                "Jadu" => array('powered by Jadu CMS','content="http://www.jadu.net','content="Jadu.net'),	
                "Jalios CMS" => array('meta name="Generator" content="Jalios JCMS'),	
                "Jimdo SaaS" => array('var jimdoData = ','link href="http://u.jimdo.com','link rel="shortcut icon" href="http://u.jimdo.com'),	
                "Joomla!" => array("/css/template_css.css", "Joomla! 1.5 - Open Source Content Management",'src="/templates/marshgreen/js/', "/templates/system/css/system.css", "Joomla! - the dynamic portal engine and content management system","/templates/system/css/system.css","/media/system/js/caption.js","/templates/system/css/general.css","/index.php?option=com_content&task=view",'name="generator" content="Joomla! - Open Source Content Management"','href="/components/com_rsform/assets/css/front.css','"stylesheet" href="/media/jui/css/bootstrap.min.css','script src="/modules/mod_slideshowck/assets/camera.min.js','src="/modules/mod_slideshowck/assets/jquery.mobile.customized.min.js','/templates/yoo_digit/css/bootstrap','link rel="stylesheet" href="/templates/yoo_glass/css/','link rel="stylesheet" href="/media/zoo/elements/','script src="/media/system/js/modal.js"','script src="/templates/yoo_nano3/warp/','meta name="generator" content="Joomla!','/css/joomla.css'),	
                "Joostina" => array('Joostina CMS','Работает на Joostina'),	
                "Kentico" => array("CMSListMenuLI","CMSListMenuUL","Lvl2CMSListMenuLI","/CMSPages/GetResource.ashx"),	
				"Kernel Video Sharing: KVS" => array("/js/KernelTeamVideoSharingSystem"),	
				"Komodo" => array('Developed by: Komodo CMS','content="Komodo CMS','a href="/komodo-cms'),	
				"Koobi CMS" => array('meta name="generator" content="(c) Koobi',"expires: 30,path: '/koobi7",'meta name="generator" content="KOOBI'),	
                "Kwimba SaaS" => array("Kwimba.ru - он-лайн сервис для создания Интернет-магазина",'a title="Kwimba.ru - он-лайн сервис для создания Интернет-магазина" href="http://kwimba.ru'),	
                "LEPTON CMS" => array('/templates/lepton/css/template.css" media="screen,projection"','/templates/lepton/css/print.css" media="print"'),	
                "Lark.ru SaaS" => array("/user_login.lm?back=%2F","http://lark.ru/gb.lm?u=", "http://lark.ru/news.lm?u="),	
                "Limb CMS" => array("!-- POWERED BY limb",'!-- POWERED BY limb | HTTP://WWW.LIMB-PROJECT.COM/ --'),	
                "LightMon Engine" => array('meta name="copyright" content="Powered by LightMon','!-- Lightmon Engine Copyright'),	
                "Liferay CMS" => array("var Liferay={Browser:",'Liferay.currentURL="','var themeDisplay=Liferay.','Liferay.Portlet.onLoad','comboBase:Liferay','Liferay.AUI.getFilter','Liferay.Portlet.runtimePortletIds','Liferay.Util.evalScripts','Liferay.Publisher.register','Liferay.Publisher.deliver','Liferay.Popup.center'),	
                "LiveStreet" => array("LIVESTREET_SECURITY_KEY","Free social engine"),	
                "Limbo (Lite mambo)" => array('meta name="GENERATOR" content="Limbo - Lite Mambo'),	
                "Magento" => array("cms-index-index","___store=eng&___from_store=rus"),	
                "Magnolia" => array('http://www.magnolia-cms.com/'),	
                "Mambo" => array('meta name="Generator" content="Mambo'),	
                "MaxSite CMS" => array("/application/maxsite/shared/","/application/maxsite/templates/","/application/maxsite/common/","/application/maxsite/plugins/",'meta name="generator" content="MaxSite CMS'),	
                "MediaWiki" => array("/common/wikibits.js","/common/images/poweredby_mediawiki_",'Powered by MediaWiki','mediawiki.page.startup'),	
                "Megagroup SaaS" => array("https://cabinet.megagroup.ru/client.", "https://counter.megagroup.ru/loader.js","создание сайтов в студии Мегагруп","создание сайтов</a> в студии Мегагруп.",">Мегагрупп.ру</a>","изготовление интернет магазина</a> - сделано в megagroup.ru","сайт визитка</a> от компании Мегагруп","Разработка сайтов</a>: megagroup.ru","веб студия exclusive.megagroup.ru"),	
                "Melbis Shop" => array('meta name="generator" content="Melbis Shop'),
                "Merchium SaaS" => array('a class="bottom-copyright" href="http://www.merchium.ru'),
                "Methode CMS" => array('<!-- Methode uuid:'),
                "Microsoft SharePoint" => array('meta name="GENERATOR" content="Microsoft SharePoint"','meta name="progid" content="SharePoint.','id="MSOWebPartPage_Shared"'),
                "Miva Merchant" => array("merchant.mvc", "admin.mvc"),	
                "MODx" => array('var MODX_MEDIA_PATH = "media";', 'modxmenu.css', 'modx.css','assets/templates/modxhost/','/assets/js/jquery.colorbox-min.js','/assets/js/jquery-1.3.2.min.js','/assets/components/ajaxform/css/default.css','/assets/components/ajaxform/js/config.js','/assets/components/ajaxform/js/default.js','/assets/components/ajaxform/js/lib/jquery.min.js','/assets/components/minifyx/cache/','img src="assets/images/catalog/','src="/manager/includes/','/manager/includes/veriword.php','link href="/assets/templates/css/style.css','My MODx Site" />','img src="/image.php?src=/assets/images/catalog/','javascript" src="/assets/components/minishop/js/web/minishop.js"','src="/manager/templates/','- My MODx Site" />','link href="/assets/templates/css/style.css"','img src="assets/images/','text/javascript" src="assets/js/jquery-1.4.1.min.js','rel="stylesheet" href="assets/templates/','/image.php?src=assets/images/','meta name="modxru" content=','src="/assets/components/','type="text/css" rel="stylesheet" href="assets/templates/','"shortcut icon" href="/template/images/favicon.ico','link href="assets/templates/site/menu.css','link href="assets/templates/site/style.css','/assets/templates/mosint/js/jquery.tinycarousel.min.js','script type="text/javascript" src="assets/fancybox/jquery.mousewheel-3.0.4.pack.js','javascript" src="assets/fancybox/jquery.fancybox-1.3.4.pack.js','/assets/plugins/qm/js/jquery.colorbox-min.js"></script>','link href="assets/template/js/fancy_box/source/jquery.fancybox.css'),	
                "Moguta CMS" => array('/mg-templates/"','/mg-core/','/mg-plugins/'),	
				"MoinMoin" => array('link rel="stylesheet" type="text/css" href="/moin_static','This website is based on <a href="/wiki/MoinMoin">MoinMoin','This site uses the MoinMoin Wiki software.">MoinMoin Powered','rel="Start" href="/cgi-bin/moin.cgi/MainPage">','a href="/cgi-bin/moin.cgi/MainPage"','a href="http://moinmo.in/">MoinMoin Powered</a>'),	
                "mojoPortal" => array('content="http://www.mojoportal.com','var mojoPageTracker'),
                "Monolit.CMS" => array('Создание сайта – IT Группа "<a target="_blank" href="http://peredovik.ru/">Передовик точка ру','templates/_shablon/CFW/CFW_styles.css'),
                "Movable Type" => array('meta name="generator" content="Movable Type','Powered by<br /><a href="http://www.sixapart.jp/movabletype/">Movable Type'),	
                "Mozello SaaS" => array("//cache.mozello.com/designs/","//cache.mozello.com/libs/js/jquery/jquery.js","Mozello</a> - самым удобным онлайн конструктором сайтов","mz_component mz_wysiwyg mz_editable","moze-wysiwyg-editor","//cache.mozello.com/mozello.ico"),	
				"Mura CMS" => array('meta name="generator" content="Mura'),	
				"myBB SaaS" => array("http://bs.mybb.ru/adverification?","Mybb_Brown_Assembly","mybb-counter","mybb.ru/userlist.php","mybb.ru/search.php?action=show_recent","unescape(mybb_ad4)"),	
                "NetDo SaaS" => array("Мой сайт на конструкторе сайтов netdo.ru","http://netdo.ru/min/g/web.js", "http://netdo.ru/engine/css/layout/", "http://netdo.ru/engine/template/style/"),	
                "NetCat" => array("/netcat_template/","/netcat_files/"),	
                "Nethouse" => array('data-ng-app="Nethouse"','data-host="nethouse.ru"','Конструктор сайтов<br/><a href="http://www.nethouse.ru/?footer"'),	
                "Ning" => array('import url(http://api.ning.com:80','src="http://api.ning.com:80/files/','href="http://static.ning.com/socialnetworkmain/'),	
                "NQcontent" => array('content="nqcontent'),	
                "Nubex CMS" => array('name="copyright" content="Powered by Nubex"','Конструктор&nbsp;сайтов&nbsp;<a href="http://nubex.ru"','href="/_nx/plain/css/'),	
                "Nucleus CMS" => array('content="Nucleus CMS v3.24"'),	
                "ocPortal" => array('Powered by ocPortal'),	
                "Open CMS" => array('/system/modules/com.gridnine.opencms.modules'),	
                "OpenText Web Solutions" => array('published by Open Text Web Solutions'),	
                "OpenCart (ocStore)" => array('<div class="cart-add-wrap"><input type="button" class="cart-add"','type="button" class="cart-add" value="Купить" onclick="addToCart',"catalog/view/theme/default/stylesheet/","catalog/view/javascript/jquery/colorbox/jquery","catalog/view/theme/default/stylesheet/stylesheet.css", "index.php?route=account/account", "index.php?route=account/login","index.php?route=account/simpleregister",'class="jcarousel-skin-opencart"'),	
				"osCommerce" => array('osCommerce Template &copy;','Powered by <a href="http://www.oscommerce.ru" target="_blank">osCommerce','/index.php?osCsid=','shopping_cart.php?osCsid=','/shipping.php?osCsid=','/account.php?osCsid=','/products_new.php?osCsid=','&amp;osCsid='),	
                "Shopify" => array('var Shopify = Shopify','Shopify.theme = {"name":"','//cdn.shopify.com/s/files/'),
                "Shopium SaaS" => array('link rel="stylesheet" href="//cdn2.shopium.ua','meta property= content="http://cdn2.shopium.ua/','img src="//cdn2.shopium.ua','script type="text/javascript" src="//cdn1.shopium.ua'),
                "Parallels Presence Builder" => array('meta name="generator" content="Parallels Presence Builder'),	
                "Percussion CMS" => array('meta content="Percussion CM System" name="generator','meta name="generator" content="Percussion',"var evergageAccount = 'percussion"),	
                "phpBB" => array("phpBB style name: prosilver", "The phpBB Group : 2006", "linked to www.phpbb.com. If you refuse","_phpbbprivmsg","Русская поддержка phpBB","below including the link to www.phpbb.com",'Движется на пхпББ'),	
                "PHP-Fusion" => array("Powered by <noindex><a href='http://www.php-fusion.co.uk'>PHP-Fusion</a>","Powered by <a href='http://www.php-fusion.co.uk'>PHP-Fusion</a>","script src='infusions/","language='javascript' src='infusions/","background-image: url('infusions/","alt='PHP-Fusion' title='PHP-Fusion'","Powered by <a href='http://www.php-fusion.co.uk'"),	
                "PHP Link Directory" => array('<a href="http://www.phplinkdirectory.com" title="PHP Link Directory">PHP Link Directory</a>','Powered By <a href="http://www.phplinkdirectory.com/">PHPLD</a>','meta name="generator" content="Internet Directory One Running on PHP Link Directory','href="/profile.php?mode=register" title="Register">Register to PHPLD</a>','<a href="http://www.phplinkdirectory.com" title="PHP Link Directory">PHP LD</a>'),	
                "PHP-Nuke" => array('META NAME="GENERATOR" CONTENT="PHP-Nuke - Copyright by http://phpnuke.org"','META NAME="GENERATOR" CONTENT="PHP-Nuke Copyright','Powered by PHP-Nuke Platinum','META NAME="GENERATOR" CONTENT="PHP-Nuke'),	
                "PhpShop" => array("/phpshop/templates/",'Скрипт интернет-магазина PHPShop','PHPShop Software 2005-','META name="engine-copyright" content="PHPSHOP.RU','href="http://phpshopcms.ru/">PHPShopCMS</a>'),		
                "phpSQLiteCMS" => array('meta name="generator" content="phpSQLiteCMS'),	
                "Pligg" => array('Pligg is an open source content management system that lets you easily','Pligg <a href="http://www.pligg.com/" target="_blank">Content Management System','name="description" content="Pligg is an open source content management system that lets you easily','var my_pligg_base=','meta name="generator" content="Pligg'),	
                "Plone" => array('generator" content="Plone','template-homepage_f8_view portaltype-homepagef8 site-en'),	
                "Posterous" => array('class="posterous_autopost','class="posterous_bookmarklet_entry','class="posterous_short_quote'),	
                "PrestaShop" => array("/themes/prestashop/cache/","/themes/prestashop/","Prestashop 1.5"." || Presta-Module.com",'meta name="generator" content="PrestaShop"'),	
                "cubiQue" => array("http://www.laconix.net/cubiQue"),	
                "Rainbow" => array('meta name="generator" content="rainbow-cms'),
                "RiteCMS" => array('meta name="generator" content="RiteCMS'),
                "RCMS" => array('meta name="generator" content="RCMS','link href="//rcms-r-production'),
                "RBS Change" => array('<body xmlns:change="http://www.rbs.fr','meta name="generator" content="RBS Change'),	
                "Sequnda" => array('alt="Работает на CMS Sequnda"','img src="/i/2sun.gif','2Sun. Web-дизайн и реклама в интернете','href="/images/2sun/'),	
                "Sense/Net" => array('content="Sense/Net','Powered by Sense/Net'),	
                "Serendipity" => array('meta name="Powered-By" content="Serendipity','div id="serendipity_banner"','meta name="generator" content="Serendipity'),	
                "SETUP.ru SaaS" => array("Сделано на SETUP.ru"),	
                "S.Builder" => array('<a href="/techine/Sbuilder_sites.php">'),	
                "SharePoint" => array('meta name="GENERATOR" content="Microsoft SharePoint','"ProgId" content="SharePoint.WebPartPage.Document','=== STARTER: Core SharePoint CSS ==','STARTER: SharePoint Reqs this for adding colu','xmlns:SharePoint="Microsoft.SharePoint.WebControls'),	
                "Shopware" => array('stylesheet" href="/engine/Shopware/Plugins','div class="shopware_footer"'),	
                "Squiz Matrix" => array('Running Squiz Matrix','Developed by Squiz'),	
                "Squarespace" => array('itemscope itemtype="http://schema.org/Thing" class="squarespace-cameron"','http://static.squarespace.com/static/','Squarespace.afterBodyLoad(Y);','Squarespace.Constants.CORE_APPLICATION_DOMAIN = "squarespace.com"','div id="squarespace-powered"','alt="Powered by Squarespace"'),	
                "SilverStripe" => array('meta name="generator" content="SilverStripe'),	
                "Simpla CMS" => array("design/default/css/main.css","design/default/images/favicon.ico","tooltip='section' section_id=",'Slider_Simpla_Module'),	
                "Simple Machines Forum" => array('<a href="http://www.simplemachines.org/" title="Simple Machines Forum" target="_blank" class="new_win">Powered by SMF</a>','alt="Simple Machines Forum" title="Simple Machines Forum"','a href="http://www.simplemachines.org" title="Simple Machines"','title="Simple Machines" target="_blank" class="new_win">Simple Machines</a>','gaq.push(["_setDomainName", "simplemachines.org"'),	
                "SiteDNK" => array('http://company.nn.ru/sitednk/" target="_blank"><img src="/img/sdnk.gif"'),	
                "SiteEdit" => array('meta name="generator" content="CMS EDGESTILE SiteEdit"','Сайт разработан и работает на CMS SiteEdit'),	
				"Shop2You" => array('href="http://www.shop2you.ru/" target=_blank>Создание интернет-магазина</A>','href="http://www.shop2you.ru/" target=_blank>Создание интернет-магазина</a>','Создание сайта: Александр Фролов, <a href="http://www.shop2you.ru/"','A href="http://www.shop2you.ru/" target=_blank>Услуги по созданию интернет-магазинов</A>: Александр Фролов','href="http://www.shop2you.ru/" target=_blank>Создание интернет-магазина</A>: Александр Фролов'),	
				"ShopOS" => array('meta name="generator" content="(c) by ShopOS , http://www.shopos.ru"','Telerik.Sitefinity.Services.Search.Web.UI.Public.SearchBox'),	
                "Skynell SaaS" => array('<meta property="og:image" content="http://skynell.com"/>','href="http://skynell.com/promo/shop.php" class','href="http://skynell.com/promo/crm.php','href="http://skynell.com/company/','skynell.biz" class="theme_show_logo'),
                "SMF" => array("var smf_images_url","PHP, MySQL, bulletin, board, free, open, source, smf, simple, machines, forum","Simple Machines Forum","Powered by SMF"),
                "sNews" => array('meta name="Generator" content="sNews','meta name="generator" content="sNews'),
                "Squarespace SaaS" => array('Squarespace.Constants','CORE_APPLICATION_DOMAIN = "squarespace.com"','onclick="Squarespace.Interaction.shareLink','Squarespace.Constants.WEBSITE_TITLE','Squarespace.Constants.SS_AUTHKEY','Squarespace.Constants.ADMINISTRATION_UI','Squarespace.Constants.WEBSITE_ID'),
                "SPIP CMS" => array('meta name="generator" content="SPIP','href="prive/spip_style.css"','id="searchform" name="search" action="spip.php"','<!-- SPIP-CRON -->',"img class='spip_logos'"),	
                "Strikingly SaaS" => array('"host_suffix": "strikingly.com"','"pages_show_static_path": "//assets.strikingly.com/assets/','"show_strikingly_logo"','<meta content="//assets.strikingly.com"','<div id="strikingly-navigation-menu">','<div class="strikingly-footer-spacer"','Rendered by Strikingly','Powered by Strikingly'),	
                "Storeland SaaS" => array("storeland.net/favicon.ico","http://storeland.ru/?utm_source=powered_by_link&amp;utm_medium=","StoreLand.Ru: Сервис создания интернет-магазинов",'src="http://statistics3.storeland.ru/stat.js?site_id=','src="http://statistics2.storeland.ru/stat.js?site_id=','src="http://statistics1.storeland.ru/stat.js?site_id='),	
                "Subrion CMS" => array('meta name="generator" content="Subrion'),	
                "swift.engine" => array('uralweb_d=document','uralweb_s.colorDepth:uralweb_s'),	
                "Telerik Sitefinity" => array('<meta name="Generator" content="Sitefinity','class="RadMenu RadMenu_Sitefinity"','src="/Sitefinity/WebsiteTemplates/','Telerik.Sitefinity.Resources'),	
                "TextPattern" => array('meta name="generator" content="Textpattern','CMS Textpattern'),	
                "Tiki Wiki CMS Groupware" => array('meta name="generator" content="Tiki Wiki CMS Groupware','#tiki-center','body class="tiki tiki_wiki_page','action="tiki-login.php"','a href="tiki-remind_password.php"'),	
				"Timelabs CMS" => array("X-Powered-By: TimeLabs CMS"),	
				"Tiu.ru" => array('href="http://tiu.ru/" class="b-head-control-panel__logo','data-propopup-url="http://tiu.ru/util/ajax_get_pro_popup_new','Сайт создан на платформе Tiu.ru</a>','href="http://tiu.ru/how_to_order?source_id='),	
				"Trac" => array('rel="help" href="/wiki/TracGuide"','/wiki/WikiStart?format=txt" type="text/x-trac-wiki','аботает на <a href="/about"><strong>Trac','owered by <a href="/about"><strong>Trac'),	
                "Tumblr" => array('arning: Never enter your Tumblr password unless \u201chttps://www.tumblr.com/login','background-image: url(http://static.tumblr.com','href="android-app://com.tumblr/tumblr/','BEGIN TUMBLR FACEBOOK OPENGRAPH TAGS'),	
                "TypePad" => array('meta name="generator" content="http://www.typepad.com/"','application/rsd+xml" title="RSD" href="http://www.typepad.com'),	
                "TYPO 3" => array("This website is powered by TYPO3","typo3temp/",'meta name="generator" content="TYPO3','src="/typo3conf/','--TYPO3SEARCH_end'),	
                "Twilight CMS" => array('<A HREF="http://www.twl.ru" target="_blank" >Система управления сайтом TWL CMS</A>','<link rel="stylesheet" href="Sites/','<link rel="stylesheet" href="/Sites/','<link rel="stylesheet" href="/Sites/','<img src="/Sites/'),	
                "uCoz" => array("cms-index-index","U1BFOOTER1Z","U1DRIGHTER1Z","U1CLEFTER1","U1AHEADER1Z","U1TRAKA1Z","U1YANDEX1Z"),	
                "UkroCMS" => array('target="_blank" href="http://ukro.in.ua">UkroCMS</a>'),	
                "Umbraco" => array('xmlns:umbraco.library="urn:umbraco.library','/umbraco/imageGen.ashx','uComponents: Multipicker','umbraco:Item field=','umbraco:macro alias=','html xmlns:umbraco="http://umbraco.org'),	
                "UMI CMS" => array('xmlns:umi="http://www.umi-cms.ru/',"umi:element-id=", "umi:field-name=","umi:method=", "umi:module=",'<!-- Подключаем title, description и keywords -->'),	
                "Ural CMS" => array('<meta name="author" content="Ural-Soft"','uss-copyright_logo" href="http://www.ural-soft.ru/','http://www.ural-soft.ru/" target="_blank" title="создание сайтов Екатеринбург'),	
                "VamShop" => array("templates/vamshop/css/","templates/vamshop/img","templates/vamshop/buttons"),	
                "uWeb SaaS" => array('Хостинг от <a href="http://www.uweb.ru/" title="Создать сайт">uWeb'),	
                "vBulletin" => array("vbulletin_css", "vbulletin_important.css","clientscript/vbulletin_read_marker.js", "Powered by vBulletin", "Main vBulletin Javascript Initialization","var vb_disable_ajax = parseInt","vbmenu_control"),	
                "Vignette" => array('begCacheTok=com.vignette','link href="/vgn-ext-templating'),	
                "Vivvo CMS" => array('meta name="generator" content="Vivvo','new vivvoTicker','VIVVO CMS'),	
                "WebAsyst" => array("/published/SC/","/published/publicdata/","aux_page=","auxpages_navigation","auxpage_","?show_aux_page=",'/wa-data/public/shop/themes/'),	
                "webEdition" => array('meta name="generator" content="webEdition"'),
                "WebGUI" => array('meta name="generator" content="WebGUI','function getWebguiProperty','content="WebGUI'),
				"Website Baker" => array('meta name="generator" content="CMS: Website Baker'),
                "Webs" => array('thumbServer: "http://thumbs.webs.com',"if(typeof(webs)==='undefined')",'<link rel="stylesheet" type="text/css" href="http://static.websimages.com/','text/javascript" src="http://static.websimages.com/JS/','webs.theme.style = {','webs-allow-nav-wrap'),
				"WebSite X5" => array('generator" content="Incomedia WebSite X5'),
				"WebsPlanet" => array('meta name="generator" content="WebsPlanet Core'),
                "Web Canape CMS" => array('Web-canape - <a href="http://www.web-canape.','a href="http://www.web-canape.ru/seo/?utm_source=copyright">продвижение</a>','/themes/canape1/css/ie/main.ie.css'),	
                "Weebly SaaS" => array("Weebly.Commerce = Weebly.Commerce","Weebly.setup_rpc","editmysite.com/js/site/main.js","editmysite.com/css/sites.css","editmysite.com/editor/libraries","weebly-footer-signup-container","link weebly-icon"),	
                "Wix SaaS" => array("static.wix.com/client/","X-Wix-Published-Version", "X-Wix-Renderer-Server","X-Wix-Meta-Site-Id",'http-equiv="X-Wix-Application-Instance-Id"'),	
                "Wolf CMS" => array('href="http://www.wolfcms.org/">Wolf CMS</a> Inside','title="Wolf CMS" target=_blank>Wolf CMS</a> Inside','href="http://www.wolfcms.org">Wolf CMS Inside</a>'),
                "WordPress" => array("/wp-includes/", "wp-content/", "/wp-admin/", "/wp-login/",'meta name="generator" content="WordPress'),
                "WYSIWYG Web Builder" => array('name="generator" content="WYSIWYG Web Builder'),
                "XenForo" => array('html id="XenForo" lang="','link rel="stylesheet" href="css.php?css=xenforo','script src="js/xenforo/xenforo.js','src="styles/default/xenforo/','Forum software by XenForo&trade; <span>','action="login/login" method="post" class="xenForm"'),
                "XOOPS" => array('meta name="generator" content="XOOPS','meta name="author" content="XOOPS"','/include/xoops.js'),
				"XpressEngine" => array('meta name="Generator" content="XpressEngine"'),	
                "xt:Commerce" => array('meta name="generator" content="xt:Commerce','alt="xt:Commerce Payments','div class="copyright">xt:Commerce','This OnlineStore is brought to you by XT-Commerce'),
                "Yahoo! Small Business" => array('(new Image).src="http://store.yahoo.net/cgi-bin/refsd?e='),
                "Yu CMS" => array('(new Image).src="http://store.yahoo.net/cgi-bin/refsd?e='),
                "Zen Cart" => array('meta name="generator" content="shopping cart program by Zen Cart','meta name="author" content="The Zen Cart&trade; Team and others"','greybox 1: greybox for zencart',"n&amp;zenid="),
                "ZMS" => array('generator" content="ZMS http://www.zms-publishing.com"'),
                "Просто Сайт CMS" => array('<a title="создание сайтов" href="http://www.yalstudio.ru/services/corporativ/">создание сайтов</a> — Студия ЯЛ','http://www.yalstudio.ru/services/complex/" title="продвижение сайтов','title="продвижение сайтов" href="http://www.yalstudio.ru/services/complex/">Продвижение сайтов','<a href="http://www.yalstudio.ru/services/complex/">продвижение сайтов</a>')
        );
        foreach ($cms as $name => $rules) {
            $c = count($rules);
            for ($i = 0; $i < $c; $i++) {
				if (function_exists('stripos')) {
					if (stripos($html, $rules[$i]) !== FALSE) {
						return '<b>CMS</b>: '.$name . '<br>';
						}
					}	
                }
        }
        return "<b>CMS</b>: not defined<br>";
}
function cmscheck() {
	echo check(grab($_SERVER["HTTP_HOST"])); //выводим CMS 
}

function toolzacheck() {
	$toolzaurl = $_SERVER["HTTP_HOST"].'/?magiya=poyav1s';
	$magicpage = getpage($toolzaurl);
	if (preg_match ('|.*<\(__\)>.*|ism',$magicpage,$res)) {
		echo "<p>Toolza <span style='color:#004010'>installed</span>";
		$htaccesslook = file_get_contents('.htaccess');
		preg_match ('|.*/([\w\d-]+)/.*toolza.php\$|ism', $htaccesslook, $contentsht);
		echo ' in '.$contentsht[1].'';
		if (is_dir(($contentsht[1]))){
			echo ' (directory found)</p>';
		}
		else echo ' (directory not found)</p>';
	} 
	else echo "<p>Toolza <span style='color:#660000'>not installed</span></p>";
}		

function modrewritecheck() {
	ob_end_flush(); 
	ob_start();   
	phpinfo(8);  
	$inf = ob_get_contents();  
	ob_end_clean(); 
	if (preg_match('/Loaded Modules.*mod_rewrite/i', $inf)) echo '<br>mod_rewrite <span style="color:#004010">found</span>';  
	else echo '<br>mod_rewrite <span style="color:orange">not found</span>';  
}

$_mainFileName = "index.php";
$_fileNameChecker = "checker.php";

function ReplaceSystemVars(){
	foreach($_SERVER as $k=>$v){
        if (!empty($_fileNameChecker)||!empty($_mainFileName)) {
            $_SERVER[$k] = str_replace($_fileNameChecker, $_mainFileName, $_SERVER[$k]);
        }
	}
}
function diffinfo(){

	//выводим NS-записи хоста
	$sitens = $_SERVER["HTTP_HOST"];
	if (function_exists('dns_get_record')) {
		$dns_arr = dns_get_record($sitens,DNS_NS);
		echo '<table><tr><td>NS-record 1: </td><td>'. ($dns_arr[0]['target']).'</td></tr>';
		echo '<tr><td>NS-record 2: </td><td>'. ($dns_arr[1]['target']).'</td></tr>';
		$dns_arr2 = dns_get_record($sitens,DNS_MX);
		echo '<tr><td>MX-record: </td><td>'. ($dns_arr[0]['target']).'</td></tr></table>';
	}
	echo '<p> Path to file: '.$_SERVER["SCRIPT_FILENAME"].'</p>';
    echo 'OS: ' . PHP_OS . '<br />';
    echo 'default file rights:'.substr(sprintf('%o',fileperms($_SERVER['DOCUMENT_ROOT'].'/checker.php')),-4).'<br />';
}


function memorylimit(){
	echo "<br><b>memory limit: </b>" . ini_get("memory_limit") . '<br>'; //memory limit in php.ini
}

function showmemory(){
   	$_mainFileName = "index.php";
	$_htmlFileName = "index.html";
	$_htmFileName = "index.htm";
		// echo "<br />Memory before Index.php (byte): " . memory_get_usage(true) . " = " . round(memory_get_usage(true)/1048576,2) . " Mb";
	if (file_exists($_mainFileName))	{
		ob_end_flush(); 
		ob_start();
		@include_once $_mainFileName;
		$file = ob_get_contents();
		$memory = memory_get_usage(true);
		ob_end_clean();
		echo "<br />Memory after Index.php (byte): " . $memory . " = " . round($memory/1048576,2) . " Mb" . "<br> (Need more than <b>20 Mb</b> for toolza correct work: Memory Limit - Memory after Index.php)<br>";
	}
	elseif (file_exists($_htmlFileName))	{
		ob_end_flush(); 
		ob_start();
		@include_once $_htmlFileName; 
		error_reporting( E_ERROR ); 
		$file = ob_get_contents();
		$memory = memory_get_usage(true);
		ob_end_clean();
		echo "<br />Memory after Index.html (byte): " . $memory . " = " . round($memory/1048576,2) . " Mb" . "<br> (Need more than <b>20 Mb</b> for toolza correct work: Memory Limit - Memory after Index.html)<br>";
	}
	elseif (file_exists($_htmFileName))	{
		ob_end_flush(); 
		ob_start();
		@include_once $_htmFileName; 
		$file = ob_get_contents();
		$memory = memory_get_usage(true);
		ob_end_clean();
		echo "<br />Memory after Index.htm (byte): " . $memory . " = " . round($memory/1048576,2) . " Mb" . "<br> (Need more than <b>20 Mb</b> for toolza correct work: Memory Limit - Memory after Index.htm)<br>";
	}
	
	else echo '<br>index.php, index.html or index.htm not found. Cant check memory usage';
 }



// Создаем папку
function FileCreateRead() {
		$structure = './test-123-folderUniquename74/';
		if (!mkdir($structure, 0777, true)) 
		echo "Cant create directory...<br>";
		else
		chmod("./test-123-folderUniquename74", 0777);
		//создаем файл info.php, наполняем его
		$intfile = fopen("./test-123-folderUniquename74/info.php","w+");
		$textinfile = "<?php echo \"<b>ok</b>\"; ?>";
		if (fwrite($intfile,$textinfile)) {
		    echo "file created: ";
        }
		else {
		    echo "file created: <span style='color:red'><b>false</b></span>";
        }
		fclose($intfile);
		include './test-123-folderUniquename74/info.php'; 	//читаем файл
        echo '<br />script created file rights:'.substr(sprintf('%o',fileperms($_SERVER['DOCUMENT_ROOT'].'/test-123-folderUniquename74/info.php')),-4).'<br />';
        //globcheck(); //проверяем включена ли ф-ция glob'ального обхода каталогов.
}

function globcheck(){
    foreach (glob($_SERVER['DOCUMENT_ROOT'].'/test-123-folderUniquename74/*.php') as $file) {
        //echo $filename . '<br>';
        if (empty($file)) {
            echo "<br />glob is false<br />";
        } else echo "<br />glob is ok: $file<br />";
    }
}

// checker other functions
class Checker {

    var $_IO;
    var $_Http;

    function Exists($name){
        return function_exists($name);
    }

    function Start(){

        if($this->Exists('fopen') && $this->Exists('fwrite')){
            $this->Log('fopen-fwrite', 'ok');
            $this->_IO = new OldFileIO();
        } elseif($this->Exists('file_get_contents') && $this->Exists('file_put_contents')){
            $this->Log('file_get_contents-file_put_contents', 'ok');
            $this->_IO = new NewFileIO();
        }

        if($this->_IO === null){
            $this->Log('IO stack', 'FAIL');
            return;
        }


        if($this->Exists('fsockopen') && $this->Exists('fwrite')){
            $this->Log('fsockopen-fwrite', 'ok');
			if ($this->Exists('curl_version')){ //curl version
			    $curlv = curl_version();
			    $curlver = $curlv['version'];
			    $this->Log("cURL version", "$curlver");
			} else $this->Log('cURL version','not found');
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

        $this->Log('ServerSoftwareExt', $_SERVER['SERVER_SOFTWARE']);
        if($this->Exists('apache_get_modules')){
        echo '<br/><details><summary><span style="cursor: pointer;"><b>Apache Modules:</b></summary><pre>';
           print_r(apache_get_modules());
           echo '<br/></pre></details> ';
        }
		
		$this->ShowPhpinfo();
    }
	
	function ShowPhpinfo(){
        echo '<br/><details><summary><span style="cursor: pointer;"><b>PHPInfo():</b></summary>';
		phpinfo();
        echo '<br/></details>';
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
                $this->Log("redirect found to ", "$h");
                return;
            }
        }
        $this->Log("redirect", "<span style='color:red'>not found</span>");
    }

    function Log($name, $rez){
        echo "<br>" . $name . ":\t" . "<b>" . $rez ."</b>";
    }


}

class Socket{
    function GetHeaders($host, $url){

        $stream = fsockopen($host, 80, $errno, $errstr, 30);
        if (!$stream) {
            echo "<span style='color:red'>Ошибка сокета: $errstr ($errno)</span><br>\n";
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

        if (!empty($responseHeaders)) {
            $responseHeaders;
        }
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

function erase_all() { //чистим за собой

$file = file_get_contents($_SERVER['DOCUMENT_ROOT']."/.htaccess"); 

		if (preg_match('|.*testFirst.html|ism',$file)){ //только, если в .htaccess найдено testFirst.html
			$row_number = 0; //Удалим 1 строку из .htaccess (rewriteengine on)
			$file = file($_SERVER['DOCUMENT_ROOT']."/.htaccess"); // Считываем весь файл в массив 
			for($i = 0; $i < sizeof($file); $i++)
			if($i == $row_number) unset($file[$i]);
			$fp = fopen($_SERVER['DOCUMENT_ROOT']."/.htaccess", "w");
			fputs($fp, implode("", $file));
			fclose($fp);
			echo ".htaccess line \"RewriteEngine On\" deleted <br/>";

			$row_number = 0; //Удалим 2 строку из .htaccess ещё раз - (rewriterule testFirst to testSecond) 
			$file = file($_SERVER['DOCUMENT_ROOT']."/.htaccess"); // Считываем весь файл в массив
			for($i = 0; $i < sizeof($file); $i++)
			if($i == $row_number) unset($file[$i]);
			$fp = fopen($_SERVER['DOCUMENT_ROOT']."/.htaccess", "w");
			fputs($fp, implode("", $file));
			fclose($fp);
			echo ".htaccess line \"RewriteRule testFirst.html /testSecond.html [L,R=301]\" deleted <br>"; 
		} else echo 'Строки редиректов не найдены в .htaccess';
		
		
		$path = $_SERVER['DOCUMENT_ROOT'].'/test-123-folderUniquename74';
		unlink($path.'/info.php');
		rmdir($path);
		echo "Folder test-123-folderUniquename74 deleted<br />";
		
		$files_root = array(
			$_SERVER['DOCUMENT_ROOT'].'/testFirst.html',
			$_SERVER['DOCUMENT_ROOT'].'/testSecond.html',
			$_SERVER['DOCUMENT_ROOT'].'/checker.php'
		);
		foreach ($files_root as $file_root){
			unlink($file_root);
		}
		echo 'files_root deleted <br>';
		
}

?>
</body>
</html>