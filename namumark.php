<?php
 
// Take credit for your work.
$wgExtensionCredits['parserhook'][] = array(
 
   // The full path and filename of the file. This allows MediaWiki
   // to display the Subversion revision number on Special:Version.
   'path' => __FILE__,
 
   // The name of the extension, which will appear on Special:Version.
   'name' => '나무마크 미디어위키판',
 
   // A description of the extension, which will appear on Special:Version.
   'description' => 'PHP 나무마크를 미디어위키에 적용합니다.',
 
   // Alternatively, you can specify a message key for the description.
  // 'descriptionmsg' => 'exampleextension-desc',
 
   // The version of the extension, which will appear on Special:Version.
   // This can be a number or a string.
   'version' => '0.4', 
 
   // Your name, which will appear on Special:Version.
   'author' => 'koreapyj 원본, 김동동 수정',
 
   // The URL to a wiki page/web page with information about the extension,
   // which will appear on Special:Version.
   'url' => 'https://github.com/koreapyj/php-namumark',
   
'license-name' => "AGPL-3.0",   // Short name of the license, links LICENSE or COPYING file if existing - string, added in 1.23.0
  
);
$wgHooks['ParserBeforeStrip'][] = 'NamuMark';
$wgHooks['InternalParseBeforeLinks'][] = 'NamuMarkHTML';
$wgHooks['ParserAfterTidy'][] = 'NamuMarkHTML2';


function NamuMark(&$parser, &$text, &$strip_state) { 
	$text = html_entity_decode($text);
	$title = $parser->getTitle();

	$special = '특수:올리기';
	$str1 = strcmp($title, $special);
	unset($special);
	$special = '특수:최근바뀜';
	$str2 = strcmp($title, $special);
	unset($special);
	$special = '특수:옮기기';
	$str3 = strcmp($title, $special);
	if ($str1 && $str2 && $str3 && !preg_match("/&action=history/", $_SERVER["REQUEST_URI"]) && !preg_match('/특수:기여/', $title) && !preg_match('/특수:기록/', $title)) {
		if (preg_match('/&oldid=/', $_SERVER["REQUEST_URI"])) {
			preg_match('/^.*$/m', $text, $fn);
			$text = str_replace("$fn[0]", '', $text);
		}
			
		global $namu_articepath;
		require_once("php-namumark.php");
		$wPage = new PlainWikiPage("$text");
		$wEngine = new NamuMark($wPage);
		$wEngine->prefix = "$namu_articepath";
		$text =  $wEngine->toHtml();
		preg_match_all('/<math>.*?<\/math>/', $text, $math);
    
		foreach ($math as $tex) {
			foreach ($tex as $rtex) {
				if (preg_match('/<math>.*?\[.*?\].*?<\/math>/', $rtex)) {
						if (!isset($i)) {
							$i=0;
						} 
						$i++;
						$math_value[$i] = $rtex;
				}
			}
		}
    

		if(isset($math_value)) {
			foreach ($math_value as $link) {
					$rlink = str_replace('[[', '[', $link);
					$rlink = str_replace(']]', ']', $rlink);
			$text = str_replace("$link", "$rlink", $text);
			}
		}
		
		if (preg_match('/&oldid=/', $_SERVER["REQUEST_URI"])) {
		$text = $fn[0].$text;
		}
		
	}
	preg_match_all("/<nowiki>'''(.*?)'''<\/nowiki>/", $text, $GLOBALS['strong_nowiki'], PREG_SET_ORDER);
	
}


function NamuMarkHTML( Parser &$parser, &$text ) {
	global $namu_articepath;
	require_once("php-namumark.class2.php");
	$wPage = new PlainWikiPage2("$text");
	$wEngine = new NamuMark2($wPage);
	$wEngine->prefix = "$namu_articepath";
	$text =  $wEngine->toHtml();	
}

function NamuMarkHTML2( &$parser, &$text ) {
	global $namu_articepath;
	require_once("php-namumark.class2.php");
	$wPage = new PlainWikiPage2("$text");
	$wEngine = new NamuMark2($wPage);
	$wEngine->prefix = "$namu_articepath";
	$text =  $wEngine->toHtml();
	global $strong_nowiki;

	preg_match_all("/'''(.*?)'''/", $text, $strong, PREG_SET_ORDER);
		
		
	$text = preg_replace("/'''(.*?)'''/", '<strong>$1</strong>', $text);
		
	foreach($strong_nowiki as $each_strong_nowiki) {
		$text = str_replace("<strong>".$each_strong_nowiki[1]."</strong>", "'''".$each_strong_nowiki[1]."'''", $text);
			
	}
	preg_match_all("/<span class=\"mwe-math-fallback-source-inline tex\" dir=\"ltr\">(.*?)<\/span>/", $text, $math);
	foreach($math[1] as $math_value){
		$vowels = array(
			"&lt;sup&gt;",
			"&lt;/sup&gt;"
		);
		$math_value_rpe = str_replace($vowels, '^', $math_value);
		$text = str_replace($math_value, $math_value_rpe, $text);
	}
}	
?>