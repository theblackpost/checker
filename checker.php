<html>
<head>
<title>Checker.php</title>
<meta name="robots" content="noindex,nofollow" />
</head>
<body>
<?php
echo '<h1> Site <a href="http://'.$_SERVER["HTTP_HOST"].'" target="_blank">'.$_SERVER["HTTP_HOST"].'</a> checker. Ver. 2.0150106</h1>';
// php_value memory_limit 192M можно добавить в htaccess если мало при проверке showmemory


#################################################################################################
/*                  																		    #
следующие несколько строк - это функции, отвечающие за проверку той или иной части сайта        #
если на каком-то этапе проверка перестает работать или ведет себя не так, как ожидалось,        #
соответствующий пункт необходимо закомментировать (добавить в начале строки символ # или //     #
*/																								#
#################################################################################################




setstart(); //отображение ошибок, задаем кодировку страницы
filesBK(); //!!!  ВАЖНО  !!! тут закоментировать, если не работает при ошибке создания backup htaccess + ОБЯЗАТЕЛЬНО в самом низу erase_all();
cmscheck(); //проверка CMS
toolzacheck(); //проверка стоит ли тулза
ReplaceSystemVars(); //заменяем системные переменные
diffinfo(); //инфо ns-записи, path to file, phpversion
FileCreateRead(); //создание папки
modrewritecheck(); //проверяем включен ли mod_rewrite
memorylimit(); // выводим memory_limit (если меньше 64 и есть проблемы с работой тулзы - ставим  php_value memory_limit 192M или кратно выше в .htaccess в начало
// showmemory();	// проверка memory после index.php
checkerstart(); //все оставшиеся проверки чекера (fopen, cURL version, fsockopen, redirect, Software, modules, phpinfo)
erase_all(); //стираем за собой все временные файлы, папки и т.п.












############     сами функции       ###############




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
		if(mb_detect_encoding($site) != "ASCII"){ //если сайт в кириллице переводим в punycode
			include("http://xtoolza.ru/q/cms/idna_convert.class.php");
			$IDN = new idna_convert(array('idn_version' => '2008'));
			$site=$IDN->encode($site);
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
                "ABO CMS" => array("Design and programming (for ABO.CMS)","ABO.CMS"),	
                "AdVantShop.NET" => array('Работает на <a data-cke-saved-href="http://www.advantshop.net" href="http://www.advantshop.net"'),	
                "Amiro CMS" => array("/amiro_sys_css.php?","/amiro_sys_js.php?","-= Amiro.CMS (c) =-","Работает на Amiro.CMS"),	
                "Bigcommerce" => array('shortcut icon" href="http://cdn2.bigcommerce.com/',"'platform':'bigcommerce'",'link rel="shortcut icon" href="http://cdn3.bigcommerce.com','link rel="shortcut icon" href="http://cdn4.bigcommerce.com','link rel="shortcut icon" href="http://cdn1.bigcommerce.com/'),	
                "Bitrix" => array("/bitrix/templates/", "/bitrix/js/", "/bitrix/admin/"),	
                "BLOX CMS" => array('div id="blox-html-container"','Powered by <a href="http://bloxcms.com" title="BLOX Content Management System"'),	
                "BM Shop 5" => array('Разработка сайта — <a href="http://renua.ru" target="_blank">Renua</a><br/> проект <a href="http://bmshop5.ru" target="_blank">bmshop5','Создание интернет-магазинов <a href="http://bmshop.ru" target="_blank">BmShop'),	
				"CMS BS" => array('meta name="GENERATOR" content="CMS-BS"'),	
				"Cotonti" => array('meta name="generator" content="Cotonti http://www.cotonti.com'),	
				"CMS Made Simple" => array("Released under the GPL - http://cmsmadesimple.org",),	
                "Concrete CMS" => array("/concrete/js/", "concrete5 - 5.","/concrete/css/"),	
                "Contao" => array("This website is powered by Contao Open Source CMS", "tl_files"),	
                "CS Cart" => array("/skins/basic/customer/addons/","/skins/basic/customer/images/icons/favicon.ico","/auth-loginform?return_url=index.php","/index.php?dispatch=auth.recover_password","cm-popup-box hidden","cm-popup-switch hand cart-list-icon","cart-list hidden cm-popup-box cm-smart-position","index.php?dispatch=checkout.cart","cm-notification-container","/index.php?dispatch=pages.view&page_id="),	
                "Danneo CMS" => array("Danneo Русская CMS", 'content="CMS Danneo'),	
                "Demandware" => array("Demandware Analytics code", 'shortcut icon" type="image/png" href="http://demandware.edgesuite.net/','link rel="stylesheet" href="http://demandware.edgesuite.net/','img src="http://demandware.edgesuite.net/'),	
                "DataLife Engine" => array("DataLife Engine", "/engine/", "DataLife Engine (http://dle-news.ru)", "index.php?do=lostpassword", "/engine/ajax/dle_ajax.js","/engine/opensearch.php","/index.php?do=feedback","/index.php?do=rules","/?do=lastcomments"),	
                "Discuz!</title>" => array('- Powered by Discuz!</title>','meta name="generator" content="Discuz!','meta name="author" content="Discuz! Team and Comsenz UI Team"','<p>Powered by <b>Discuz!</b>','div id="discuz_bad_','Powered by <strong><a href="http://www.discuz.net"',"discuz_uid = '0'"),	
                "Django CMS" => array('meta name="generator" content="Django-CMS'),	
                "Drupal" => array("Drupal.settings","Drupal 7 (http://drupal.org)","misc\/drupal.js","drupal_alter_by_ref","/sites/default/files/css/css_","/sites/all/files/css/css_",'text/javascript" src="/misc/drupal.js'),	
                "DokuWiki" => array("DokuWiki Release"),	
                "e107" => array("This site is powered by e107"),	
                "Edicy SaaS" => array("http://stats.edicy.com:8000/tracker.js","http://static.edicy.com/assets/site_search/"),	
                "Ektron" => array("EktronClientManager","Ektron.PBSettings","ektron.modal.css","Ektron/Ektron.WebForms.js","EktronSiteDataJS","/Workarea/java/ektron.js","Amend the paths to reflect the ektron system"),	
                "Ekwid SaaS" => array("ecwid_product_browser_scroller","push-menu-trigger ecwid-icons-menu","ecwid-starter-site-links","ecwid-loading loading-start"),	
                "eSyndiCat" => array('meta name="generator" content="eSyndiCat','Powered by <a href="http://www.esyndicat.com/">eSyndiCat Directory Software'),	
                "Explay CMS" => array('meta name="generator" content="Explay CMS','Engine &copy; <a href="http://www.explay.su/">Explay CMS','meta name="generator" content="EXPLAY Engine CMS"','alt="Explay Engine CMS"'),	
                "eZ Publish" => array('img src="/var/ezflow_site','img src="/design/ezflow'),	
                "Flexcore CMS" => array("<!-- Oliwa-pro service -->"),	
                "Fo.ru SaaS" => array("MLP_NAVIGATION_MENU_ITEM_START","MLP_WINDOW_HEAD","/MLP_WINDOW_END","MLP_NAVIGATION_MENU_ITEM_END"),	
                "Gamburger CMS" => array('<span class="web"><a href="http://gamburger.ru/" target="_blank">','/templates/default/images/gamburger.png'),	
                "GD SiteManager" => array("name='generator' content='GD SiteManager'"),	
                "GitHub Pages" => array('Powered by <a href="http://pages.github.com">GitHub Pages</a>','a href="https://github.com/bip32/bip32.github.io">GitHub Repository</a>'),	
                "Homestead" => array('meta name="generator" content="Homestead SiteBuilder','link rel="stylesheet" href="http://www.homestead.com'),	
                "HostCMS" => array("/hostcmsfiles/",'<!-- HostCMS Counter -->','type="application/rss+xml" title="HostCMS RSS Feed"'),	
                "Hotlist.biz SaaS" => array("hotengine-hotlist_logo","Аренда и Создание интернет магазина Hotlist.biz","hotengine-hotcopyright","hotlist.biz/ru/?action=logout","hotengine-dialog-email","hotengine-shop-cart-message-empty-cart","hotengine-footer-copyright","hotengine-counters"),	
                "Howbay SaaS" => array("http://rtty.howbay.ru/","howbay-snapprodnamehldr","Аренда онлайн магазина howbay.ru"),	
                "inDynamic" => array("Система управления сайтом и контекстом (cms) - inDynamic",'Управление сайтом — <a href="http://www.indynamic.ru/">inDynamic'),	
                "InSales SaaS" => array("InSales.formatMoney", ".html(InSales.formatMoney","http://assets3.insales.ru/assets/","http://assets2.insales.ru/assets/","http://static12.insales.ru","Insales.money_format"),	
                "InstantCMS" => array("InstantCMS - www.instantcms.ru","/templates/instant/css/popalas.css","/templates/instant/css/siplim.css",'link href="/templates/_default_/css/styles.css"'),	
                "Image CMS" => array('meta name="generator" content="ImageCMS"','name="cms_token" />'),	
                "IP.Board" => array("!--ipb.javascript.start--","IBResouce\invisionboard.com",'/forum/index.php?act=boardrules','Powered By IP.Board'),	
                "Joomla!" => array("/css/template_css.css", "Joomla! 1.5 - Open Source Content Management",'src="/templates/marshgreen/js/', "/templates/system/css/system.css", "Joomla! - the dynamic portal engine and content management system","/templates/system/css/system.css","/media/system/js/caption.js","/templates/system/css/general.css","/index.php?option=com_content&task=view",'name="generator" content="Joomla! - Open Source Content Management"','href="/components/com_rsform/assets/css/front.css','"stylesheet" href="/media/jui/css/bootstrap.min.css','script src="/modules/mod_slideshowck/assets/camera.min.js','src="/modules/mod_slideshowck/assets/jquery.mobile.customized.min.js'),	
                "Kentico" => array("CMSListMenuLI","CMSListMenuUL","Lvl2CMSListMenuLI","/CMSPages/GetResource.ashx"),	
                "Kwimba SaaS" => array("Kwimba.ru - он-лайн сервис для создания Интернет-магазина"),	
                "Lark.ru SaaS" => array("/user_login.lm?back=%2F","http://lark.ru/gb.lm?u=", "http://lark.ru/news.lm?u="),	
                "Liferay CMS" => array("var Liferay={Browser:",'Liferay.currentURL="','var themeDisplay=Liferay.','Liferay.Portlet.onLoad','comboBase:Liferay','Liferay.AUI.getFilter','Liferay.Portlet.runtimePortletIds','Liferay.Util.evalScripts','Liferay.Publisher.register','Liferay.Publisher.deliver','Liferay.Popup.center'),	
                "LiveStreet" => array("LIVESTREET_SECURITY_KEY","Free social engine"),	
                "Magento" => array("cms-index-index","___store=eng&___from_store=rus"),	
                "MaxSite CMS" => array("/application/maxsite/shared/","/application/maxsite/templates/","/application/maxsite/common/","/application/maxsite/plugins/"),	
                "MediaWiki" => array("/common/wikibits.js","/common/images/poweredby_mediawiki_"),	
                "Megagroup CMS/Hosting SaaS" => array("https://cabinet.megagroup.ru/client.", "https://counter.megagroup.ru/loader.js","создание сайтов в студии Мегагруп","создание сайтов</a> в студии Мегагруп.",">Мегагрупп.ру</a>","изготовление интернет магазина</a> - сделано в megagroup.ru","сайт визитка</a> от компании Мегагруп","Разработка сайтов</a>: megagroup.ru","веб студия exclusive.megagroup.ru"),	
                "Melbis Shop" => array('meta name="generator" content="Melbis Shop'),	
                "Miva Merchant" => array("merchant.mvc", "admin.mvc"),	
                "MODx" => array('var MODX_MEDIA_PATH = "media";', 'modxmenu.css', 'modx.css','assets/templates/modxhost/','/assets/js/jquery.colorbox-min.js','/assets/js/jquery-1.3.2.min.js','/assets/components/ajaxform/css/default.css','/assets/components/ajaxform/js/config.js','/assets/components/ajaxform/js/default.js','/assets/components/ajaxform/js/lib/jquery.min.js','/assets/components/minifyx/cache/','img src="assets/images/catalog/','src="/manager/includes/','/manager/includes/veriword.php','link href="/assets/templates/css/style.css','My MODx Site" />','img src="/image.php?src=/assets/images/catalog/','javascript" src="/assets/components/minishop/js/web/minishop.js"','src="/manager/templates/','- My MODx Site" />','link href="/assets/templates/css/style.css"','img src="assets/images/','text/javascript" src="assets/js/jquery-1.4.1.min.js','rel="stylesheet" href="assets/templates/','/image.php?src=assets/images/','meta name="modxru" content=','src="/assets/components/','type="text/css" rel="stylesheet" href="assets/templates/','"shortcut icon" href="/template/images/favicon.ico','link href="assets/templates/site/menu.css','link href="assets/templates/site/style.css','/assets/templates/mosint/js/jquery.tinycarousel.min.js','script type="text/javascript" src="assets/fancybox/jquery.mousewheel-3.0.4.pack.js','javascript" src="assets/fancybox/jquery.fancybox-1.3.4.pack.js','/assets/plugins/qm/js/jquery.colorbox-min.js"></script>'),	
                "MoinMoin" => array('link rel="stylesheet" type="text/css" href="/moin_static','This website is based on <a href="/wiki/MoinMoin">MoinMoin','This site uses the MoinMoin Wiki software.">MoinMoin Powered','rel="Start" href="/cgi-bin/moin.cgi/MainPage">','a href="/cgi-bin/moin.cgi/MainPage"','a href="http://moinmo.in/">MoinMoin Powered</a>'),	
                "Monolit.CMS" => array('Создание сайта – IT Группа "<a target="_blank" href="http://peredovik.ru/">Передовик точка ру','templates/_shablon/CFW/CFW_styles.css'),	
                "Movable Type" => array('meta name="generator" content="Movable Type','Powered by<br /><a href="http://www.sixapart.jp/movabletype/">Movable Type'),	
                "Mozello SaaS" => array("//cache.mozello.com/designs/","//cache.mozello.com/libs/js/jquery/jquery.js","Mozello</a> - самым удобным онлайн конструктором сайтов","mz_component mz_wysiwyg mz_editable","moze-wysiwyg-editor","//cache.mozello.com/mozello.ico"),	
				"myBB SaaS" => array("http://bs.mybb.ru/adverification?","Mybb_Brown_Assembly","mybb-counter","mybb.ru/userlist.php","mybb.ru/search.php?action=show_recent","unescape(mybb_ad4)"),	
                "NetDo SaaS" => array("Мой сайт на конструкторе сайтов netdo.ru","http://netdo.ru/min/g/web.js", "http://netdo.ru/engine/css/layout/", "http://netdo.ru/engine/template/style/"),	
                "NetCat" => array("/netcat_template/","/netcat_files/"),	
                "Nethouse" => array('data-ng-app="Nethouse"','data-host="nethouse.ru"','Конструктор сайтов<br/><a href="http://www.nethouse.ru/?footer"'),	
                "Ning" => array('import url(http://api.ning.com:80','src="http://api.ning.com:80/files/','href="http://static.ning.com/socialnetworkmain/'),	
                "Nubex CMS" => array('name="copyright" content="Powered by Nubex"','Конструктор&nbsp;сайтов&nbsp;<a href="http://nubex.ru"','href="/_nx/plain/css/'),	
                "Nucleus CMS" => array('content="Nucleus CMS v3.24"'),	
                "Open CMS" => array('/system/modules/com.gridnine.opencms.modules'),	
                "Open Text" => array('published by Open Text Web Solutions'),	
                "OpenCart (ocStore)" => array('<div class="cart-add-wrap"><input type="button" class="cart-add"','type="button" class="cart-add" value="Купить" onclick="addToCart',"catalog/view/theme/default/stylesheet/","catalog/view/javascript/jquery/colorbox/jquery","catalog/view/theme/default/stylesheet/stylesheet.css", "index.php?route=account/account", "index.php?route=account/login","index.php?route=account/simpleregister"),	
                "Shopify" => array('var Shopify = Shopify','Shopify.theme = {"name":"','//cdn.shopify.com/s/files/'),	
                "Parallels Presence Builder" => array('meta name="generator" content="Parallels Presence Builder'),	
                "phpBB" => array("phpBB style name: prosilver", "The phpBB Group : 2006", "linked to www.phpbb.com. If you refuse","_phpbbprivmsg","Русская поддержка phpBB","below including the link to www.phpbb.com",'Движется на пхпББ'),	
                "PHP-Fusion" => array("Powered by <noindex><a href='http://www.php-fusion.co.uk'>PHP-Fusion</a>","Powered by <a href='http://www.php-fusion.co.uk'>PHP-Fusion</a>","script src='infusions/","language='javascript' src='infusions/","background-image: url('infusions/"),	
                "PHP Link Directory" => array('<a href="http://www.phplinkdirectory.com" title="PHP Link Directory">PHP Link Directory</a>','Powered By <a href="http://www.phplinkdirectory.com/">PHPLD</a>','meta name="generator" content="Internet Directory One Running on PHP Link Directory','href="/profile.php?mode=register" title="Register">Register to PHPLD</a>','<a href="http://www.phplinkdirectory.com" title="PHP Link Directory">PHP LD</a>'),	
                "PHP-Nuke" => array('META NAME="GENERATOR" CONTENT="PHP-Nuke - Copyright by http://phpnuke.org"','META NAME="GENERATOR" CONTENT="PHP-Nuke Copyright','Powered by PHP-Nuke Platinum','META NAME="GENERATOR" CONTENT="PHP-Nuke'),	
                "PhpShop" => array("/phpshop/templates/",'Скрипт интернет-магазина PHPShop','PHPShop Software 2005-','META name="engine-copyright" content="PHPSHOP.RU','href="http://phpshopcms.ru/">PHPShopCMS</a>'),	
                "Pligg" => array('Pligg is an open source content management system that lets you easily','Pligg <a href="http://www.pligg.com/" target="_blank">Content Management System','name="description" content="Pligg is an open source content management system that lets you easily','var my_pligg_base='),	
                "Plone" => array('generator" content="Plone - http://plone.org"','template-homepage_f8_view portaltype-homepagef8 site-en'),	
                "PrestaShop" => array("/themes/prestashop/cache/","/themes/prestashop/","Prestashop 1.5"." || Presta-Module.com",'meta name="generator" content="PrestaShop"'),	
                "cubiQue" => array("http://www.laconix.net/cubiQue"),	
                "SETUP.ru SaaS" => array("Сделано на SETUP.ru"),	
                "SharePoint" => array('meta name="GENERATOR" content="Microsoft SharePoint','"ProgId" content="SharePoint.WebPartPage.Document','=== STARTER: Core SharePoint CSS ==','STARTER: SharePoint Reqs this for adding colu','xmlns:SharePoint="Microsoft.SharePoint.WebControls'),	
                "Shopware" => array('stylesheet" href="/engine/Shopware/Plugins','div class="shopware_footer"'),	
                "Squarespace" => array('itemscope itemtype="http://schema.org/Thing" class="squarespace-cameron"','http://static.squarespace.com/static/','Squarespace.afterBodyLoad(Y);','Squarespace.Constants.CORE_APPLICATION_DOMAIN = "squarespace.com"','div id="squarespace-powered"','alt="Powered by Squarespace"'),	
                "SilverStripe" => array('meta name="generator" content="SilverStripe - http://silverstripe.org"'),	
                "Simpla CMS" => array("design/default/css/main.css","design/default/images/favicon.ico","tooltip='section' section_id="),	
                "Simple Machines Forum" => array('<a href="http://www.simplemachines.org/" title="Simple Machines Forum" target="_blank" class="new_win">Powered by SMF</a>','alt="Simple Machines Forum" title="Simple Machines Forum"','a href="http://www.simplemachines.org" title="Simple Machines"','title="Simple Machines" target="_blank" class="new_win">Simple Machines</a>'),	
                "SiteDNK" => array('http://company.nn.ru/sitednk/" target="_blank"><img src="/img/sdnk.gif"'),	
				"Shop2You" => array('href="http://www.shop2you.ru/" target=_blank>Создание интернет-магазина</A>','href="http://www.shop2you.ru/" target=_blank>Создание интернет-магазина</a>','Создание сайта: Александр Фролов, <a href="http://www.shop2you.ru/"','A href="http://www.shop2you.ru/" target=_blank>Услуги по созданию интернет-магазинов</A>: Александр Фролов','href="http://www.shop2you.ru/" target=_blank>Создание интернет-магазина</A>: Александр Фролов'),	
				"ShopOS" => array('meta name="generator" content="(c) by ShopOS , http://www.shopos.ru"','Telerik.Sitefinity.Services.Search.Web.UI.Public.SearchBox'),	
                "SMF" => array("var smf_images_url","PHP, MySQL, bulletin, board, free, open, source, smf, simple, machines, forum","Simple Machines Forum","Powered by SMF"),	
                "SPIP CMS" => array('meta name="generator" content="SPIP','href="prive/spip_style.css"','id="searchform" name="search" action="spip.php"','<!-- SPIP-CRON -->',"img class='spip_logos'"),	
                "Storeland SaaS" => array("storeland.net/favicon.ico","http://storeland.ru/?utm_source=powered_by_link&amp;utm_medium=","StoreLand.Ru: Сервис создания интернет-магазинов",'src="http://statistics3.storeland.ru/stat.js?site_id=','src="http://statistics2.storeland.ru/stat.js?site_id=','src="http://statistics1.storeland.ru/stat.js?site_id='),	
                "Telerik Sitefinity" => array('<meta name="Generator" content="Sitefinity','class="RadMenu RadMenu_Sitefinity"','src="/Sitefinity/WebsiteTemplates/','Telerik.Sitefinity.Resources'),	
                "TextPattern" => array("/textpattern/index.php"),	
				"Timelabs CMS" => array("X-Powered-By: TimeLabs CMS"),	
				"Tiu.ru" => array('href="http://tiu.ru/" class="b-head-control-panel__logo','data-propopup-url="http://tiu.ru/util/ajax_get_pro_popup_new','Сайт создан на платформе Tiu.ru</a>','href="http://tiu.ru/how_to_order?source_id='),	
				"Trac" => array('rel="help" href="/wiki/TracGuide"','/wiki/WikiStart?format=txt" type="text/x-trac-wiki','аботает на <a href="/about"><strong>Trac','owered by <a href="/about"><strong>Trac'),	
                "Tumblr" => array('arning: Never enter your Tumblr password unless \u201chttps://www.tumblr.com/login','background-image: url(http://static.tumblr.com','href="android-app://com.tumblr/tumblr/','BEGIN TUMBLR FACEBOOK OPENGRAPH TAGS'),	
                "TypePad" => array('meta name="generator" content="http://www.typepad.com/"','application/rsd+xml" title="RSD" href="http://www.typepad.com'),	
                "TYPO 3" => array("This website is powered by TYPO3","typo3temp/"),	
                "Twilight CMS" => array('<A HREF="http://www.twl.ru" target="_blank" >Система управления сайтом TWL CMS</A>','<link rel="stylesheet" href="Sites/','<link rel="stylesheet" href="/Sites/'),	
                "uCoz" => array("cms-index-index","U1BFOOTER1Z","U1DRIGHTER1Z","U1CLEFTER1","U1AHEADER1Z","U1TRAKA1Z","U1YANDEX1Z"),	
                "Umbraco" => array('xmlns:umbraco.library="urn:umbraco.library','/umbraco/imageGen.ashx','uComponents: Multipicker','umbraco:Item field=','umbraco:macro alias='),	
                "UMI CMS" => array('xmlns:umi="http://www.umi-cms.ru/',"umi:element-id=", "umi:field-name=","umi:method=", "umi:module=",'<!-- Подключаем title, description и keywords -->'),	
                "Ural CMS" => array('<meta name="author" content="Ural-Soft"','uss-copyright_logo" href="http://www.ural-soft.ru/','http://www.ural-soft.ru/" target="_blank" title="создание сайтов Екатеринбург'),	
                "VamShop" => array("templates/vamshop/css/","templates/vamshop/img","templates/vamshop/buttons"),	
                "vBulletin" => array("vbulletin_css", "vbulletin_important.css","clientscript/vbulletin_read_marker.js", "Powered by vBulletin", "Main vBulletin Javascript Initialization","var vb_disable_ajax = parseInt","vbmenu_control"),	
                "WebAsyst" => array("/published/SC/","/published/publicdata/","aux_page=","auxpages_navigation","auxpage_","?show_aux_page=",'/wa-data/public/shop/themes/'),	
                "Webs" => array('thumbServer: "http://thumbs.webs.com',"if(typeof(webs)==='undefined')",'<link rel="stylesheet" type="text/css" href="http://static.websimages.com/','text/javascript" src="http://static.websimages.com/JS/','webs.theme.style = {','webs-allow-nav-wrap'),	
                "Web Canape CMS" => array('Web-canape - <a href="http://www.web-canape.','a href="http://www.web-canape.ru/seo/?utm_source=copyright">продвижение</a>','/themes/canape1/css/ie/main.ie.css'),	
                "Weebly SaaS" => array("Weebly.Commerce = Weebly.Commerce","Weebly.setup_rpc","editmysite.com/js/site/main.js","editmysite.com/css/sites.css","editmysite.com/editor/libraries","weebly-footer-signup-container","link weebly-icon"),	
                "Wix SaaS" => array("static.wix.com/client/","X-Wix-Published-Version", "X-Wix-Renderer-Server","X-Wix-Meta-Site-Id"),	
                "WordPress" => array("/wp-includes/", "wp-content/", "/wp-admin/", "/wp-login/"),
                "XenForo" => array('html id="XenForo" lang="','link rel="stylesheet" href="css.php?css=xenforo','script src="js/xenforo/xenforo.js','src="styles/default/xenforo/','Forum software by XenForo&trade; <span>','action="login/login" method="post" class="xenForm"'),
                "XOOPS" => array('meta name="generator" content="XOOPS"','meta name="author" content="XOOPS"','/include/xoops.js'),
				"XpressEngine" => array('meta name="Generator" content="XpressEngine"'),	
                "xt:Commerce" => array('meta name="generator" content="xt:Commerce','alt="xt:Commerce Payments','div class="copyright">xt:Commerce','This OnlineStore is brought to you by XT-Commerce'),
                "Yahoo! Small Business" => array('(new Image).src="http://store.yahoo.net/cgi-bin/refsd?e='),
                "Zen Cart" => array('meta name="generator" content="shopping cart program by Zen Cart','meta name="author" content="The Zen Cart&trade; Team and others"','greybox 1: greybox for zencart',"n&amp;zenid="),
                "ZMS" => array('generator" content="ZMS http://www.zms-publishing.com"'),
                "Просто Сайт CMS" => array('<a title="создание сайтов" href="http://www.yalstudio.ru/services/corporativ/">создание сайтов</a> — Студия ЯЛ','http://www.yalstudio.ru/services/complex/" title="продвижение сайтов','title="продвижение сайтов" href="http://www.yalstudio.ru/services/complex/">Продвижение сайтов','<a href="http://www.yalstudio.ru/services/complex/">продвижение сайтов</a>')
        );
        foreach ($cms as $name => $rules) {
            $c = count($rules);
            for ($i = 0; $i < $c; $i++) {
                if (stripos($html, $rules[$i]) !== FALSE) {
                    return '<b>CMS</b>: '.$name . '<br>';
					}
                }
        }
        return "<b>CMS</b>: Не определено<br>";
}
function cmscheck() {
	echo check(grab($_SERVER["HTTP_HOST"])); //выводим CMS
}


function setstart() {
	error_reporting( E_WARNING ); //отображаем только значительные предупреждения
	ini_set('display_errors', 0); //не показываем ошибки
	header('Content-Type: text/html; charset=utf-8'); //задаем кодировку страницы
}


function modrewritecheck() {
	ob_end_flush(); 
	ob_start();   
	phpinfo(8);  
	$inf = ob_get_contents();  
	ob_end_clean(); 
	if (preg_match('/Loaded Modules.*mod_rewrite/i', $inf)) echo '<br>mod_rewrite <span style="color:#004010"><b>found</b></span>';  
	else echo '<br>mod_rewrite <span style="color:red"><b>not found</b></span>';  
}


function filesBK() {
	echo '<b>Checker BackUps:</b><br>';
	echo '<ul>';
	$htaccessfile = ".htaccess";
	if (!file_exists($htaccessfile)) {
		$htaccesswrite = fopen($htaccessfile, "w");
		fwrite($htaccesswrite, "#.htaccess file");
		fclose($htaccesswrite);
		echo "<li>.htaccess created</li>";
	} else {
		echo "<li>.htaccess already exist</li>";
	}
	
	if (file_exists('.htaccess')) {
		if (copy (".htaccess", ".htaccess_checker_autobackup")) {
			echo '<li>.htaccess backup created</li>';}
			else exit ('<li style="color:red">cant create .htaccess backup</li>');
		}
		else echo ('<li style="color:#993300">file .htaccess is not exist</li>');
	
	if (file_exists('index.php')) {
		if (copy ("index.php", "index.php_checker_autobackup")) {
			echo '<li>index.php backup created</li>';}
			else exit ('<li style="color:red">cant create index.php backup</li>');
		}
		else echo ('<li style="color:#993300">file index.php is not exist</li>');
	
	if (file_exists('index.html')) {
		if (copy ("index.html", "index.html_checker_autobackup")) {
			echo '<li>index.html backup created</li>';}
			else exit ('<li style="color:red">cant create index.html backup</li>');
		}
		else echo ('<li style="color:#993300">file index.html is not exist</li>');
		
	if (file_exists('index.htm')) {
		if (copy ("index.htm", "index.htm_checker_autobackup")) {
			echo '<li>index.htm backup created</li>';}
			else exit ('<li style="color:red">cant create index.htm backup</li>');
		}
		else echo ('<li style="color:#993300">file index.htm is not exist</li>');
		
	echo '</ul>';
}



function toolzacheck() {
	$intfile = fopen("magictoolza.html","w+");
		$textinfile = "<pre>
            <(__)> | | |
            | \/ | \_|_/
            \^  ^/   |
            /\--/\  /|
           /  \/  \/ |
    </pre>";
	fwrite($intfile,$textinfile);
	fclose($intfile);
	$toolzaurl = $_SERVER["HTTP_HOST"].'/?magiya=poyav1s';
	$magicpage = getpage($toolzaurl);
	$ourcompare = 'http://'.$_SERVER["HTTP_HOST"].'/magictoolza.html';
	$compareinfo = getpage($ourcompare);
	if ($magicpage === $compareinfo) {
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
	
$_mainFileName = "index.php";
$_fileNameChecker = "checker.php";

function ReplaceSystemVars(){
	foreach($_SERVER as $k=>$v){
		$_SERVER[$k] = str_replace($_fileNameChecker, $_mainFileName, $_SERVER[$k]);
	}
}
function diffinfo(){

	//выводим NS-записи хоста
	$sitens = $_SERVER["HTTP_HOST"];
	$dns_arr = dns_get_record($sitens,DNS_NS);
	echo '<br><table><tr><td>NS-record 1: </td><td>'. ($dns_arr[0]['target']).'</td></tr>';
	echo '<tr><td>NS-record 2: </td><td>'. ($dns_arr[1]['target']).'</td></tr>';
	$dns_arr2 = dns_get_record($sitens,DNS_MX);
	echo '<tr><td>MX-record: </td><td>'. ($dns_arr[0]['target']).'</td></tr></table>';

	echo '<p> Path to file: '.$_SERVER["SCRIPT_FILENAME"].'</p>';
	echo "<b>PHP Version: </b>".phpversion()."<br>";
}

function memorylimit(){
	echo "<br><b>memory limit: </b>" . ini_get("memory_limit"); //memory limit in php.ini
}

function showmemory(){
	$_mainFileName = "index.php";
		// echo "<br />Memory before Index.php (byte): " . memory_get_usage(true) . " = " . round(memory_get_usage(true)/1048576,2) . " Mb";
	ob_end_flush(); 
	ob_start();
	require_once $_mainFileName; //$FileName ;
	$file = ob_get_contents();
	$memory = memory_get_usage(true);
	ob_end_clean();
	echo "<br />Memory after Index.php (byte): " . $memory . " = " . round($memory/1048576,2) . " Mb" . "<br> (Need more than <b>20 Mb</b> for toolza correct work: Memory Limit - Memory after Index.php)<br>";
	}



// Создаем папку
function FileCreateRead() {
		$structure = './test-123-folderUniquename74/';
		if (!mkdir($structure, 0777, true)) 
		echo "Cant create directory...";
		else
		chmod("./test-123-folderUniquename74", 0777); 
		//создаем файл info.php, наполняем его
		$intfile = fopen("./test-123-folderUniquename74/info.php","w+");
		$textinfile = "<?php echo \"<b>ok</b>\"; ?>";
		if (fwrite($intfile,$textinfile))
		echo "file created: ";
		else
		echo "file created: false";
		fclose($intfile);
		//читаем файл
		include './test-123-folderUniquename74/info.php';
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
			$curlv = curl_version();
            $this->Log('cURL version', $curlv[version]); //curl version
            $this->Log('fsockopen-fwrite', 'ok');
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
            echo "<br><pre>";
            print_r(apache_get_modules());
            echo "<br></pre>";
        }
		
		$this->ShowPhpinfo();
    }
	
	function ShowPhpinfo(){
		phpinfo();
	}
	
    function stripos($k, $s){
        $k = strtolower($k);
        $s = strtolower($s);
        return strpos($k, $s);
    }

    function TestHtaccess(){
        $firstFile = "testFirst.html";
        $secondFile = "testSecond.html";
        $htaccess = ".htaccess";

        $fistContent = "FirstPage";
        $secondContent = "RedirectPage";

        $htaccessRedirect = "RewriteEngine On" . "\n" . "RewriteRule $firstFile /$secondFile" . " [L,R=301]" . "\r";


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
        $this->Log("redirect", "not found");
    }

    function Log($name, $rez){
        echo "<br>" . $name . ":\t" . "<b>" . $rez ."</b>";
    }


}

class Socket{
    function GetHeaders($host, $url){

        $stream = fsockopen($host, 80, $errno, $errstr, 30);
        if (!$stream) {
            echo "Ошибка сокета: $errstr ($errno)<br>\n";
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

        $handle = @fopen($fileName, "w+");

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
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$nadres);
	curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_VERBOSE, false);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSLVERSION, 3);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

	$pagemeta = curl_exec($ch);
	curl_close($ch);
	return $pagemeta;
}

function erase_all() { //чистим за собой
		$path = './test-123-folderUniquename74';
		unlink('./test-123-folderUniquename74/info.php');
		echo "File info deleted <br />";
		unlink('magictoolza.html');
		echo "File magictoolza deleted <br />";
		rmdir('./test-123-folderUniquename74');
		echo "Folder test-123-folderUniquename74 deleted<br />";
		unlink('testFirst.html');
		echo "File testFirst deleted <br />";
		unlink('testSecond.html');
		echo "File testSecond deleted <br />";

		$row_number = 0; //Удалим 1 строку из .htaccess (rewriteengine on)
		$file = file(".htaccess"); // Считываем весь файл в массив 
		for($i = 0; $i < sizeof($file); $i++)
		if($i == $row_number) unset($file[$i]);
		$fp = fopen(".htaccess", "w");
		fputs($fp, implode("", $file));
		fclose($fp);
		echo ".htaccess line \"RewriteEngine On\" deleted <br/>";

		$row_number = 0; //Удалим 2 строку из .htaccess ещё раз - (rewriterule testFirst to testSecond)
		$file = file(".htaccess"); // Считываем весь файл в массив
		for($i = 0; $i < sizeof($file); $i++)
		if($i == $row_number) unset($file[$i]);
		$fp = fopen(".htaccess", "w");
		fputs($fp, implode("", $file));
		fclose($fp);
		echo ".htaccess line \"RewriteRule testFirst.html /testSecond.html [L,R=301]\" deleted <br>"; 
		unlink('checker.php');
		echo "File checker.php deleted <br />";
}

?>
</body>
</html>