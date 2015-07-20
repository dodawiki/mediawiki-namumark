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
   'version' => '0.3.2', 
 
   // Your name, which will appear on Special:Version.
   'author' => 'koreapyj 원본, 김동동 수정',
 
   // The URL to a wiki page/web page with information about the extension,
   // which will appear on Special:Version.
   'url' => 'https://github.com/koreapyj/php-namumark',
 
);
$wgHooks['ParserBeforeTidy'][] = 'NamuMark';



function NamuMark( &$parser, &$text ) { 
	$url = "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]; 
	$title = $parser->getTitle();

	$upload = '특수:올리기';
	$str1 = strcmp($title, $upload);

		if ($str1) {
			global $namu_articepath;
			require_once("namumark.php");
			$wPage = new PlainWikiPage("$text");
			$wEngine = new NamuMark($wPage);
			$wEngine->prefix = "$namu_articepath";
			$text =  $wEngine->toHtml();
		}
	}

	
?>