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
   'version' => '0.4.2', 
 
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
	$text = html_entity_decode($text); // HTML 엔티티를 디코드한다.
	$title = $parser->getTitle(); // 문서의 제목을 title로 변수화한다.

	# 이하는 수식 태그로 인하여 발생하는 버그를 해결하기 위하여 특정 문서에서는 파서가 작동되지 않도록 한다.
	$special = '특수:올리기';
	$str1 = strcmp($title, $special); // 현재 문서가 특수:올리기인지 확인한다.
	unset($special);
	$special = '특수:최근바뀜'; // 현재 문서가 특수:올리기인지 확인한다.
	$str2 = strcmp($title, $special);
	unset($special);
	$special = '특수:옮기기';
	$str3 = strcmp($title, $special); // 현재 문서가 특수:올리기인지 확인한다.
	unset($special);
	$special = '특수:버전';
	$str4 = strcmp($title, $special); // 현재 문서가 특수:버전인지 확인한다.
	
	# '[[내부 링크|<span style="color:색깔값">표시내용<span>]]'와 같은 내부 링크 글씨의 색깔을 지정하는 방식이 버그를 일으키므로
	# 미디어위키에서 지원하는 글씨 색 방식으로 바꾼다.
	$text = preg_replace('/<span style="color:(.*?)">(.*?)<\/span>\]\]/i', '{{글씨 색|$1|$2}}]]', $text);
	$text = preg_replace('/<font color="(.*?)">(.*?)<\/font>\]\]/i', '{{글씨 색|$1|$2}}]]', $text);
	
	# 상기의 확인 함수의 반환값과, 현 URI가 히스토리인지 확인하는 함수의 반환값과, 현 문서가 특수:기여 또는 특수:기록인지 확인하는 함수의 반환값을 확인한다.
	if ($str1 && $str2 && $str3 && $str4 && !preg_match("/&action=history/", $_SERVER["REQUEST_URI"]) && !preg_match('/특수:기여/', $title) && !preg_match('/특수:기록/', $title)) {
		# 문서 구판에 접속시 최상단의 코드를 별도의 변수로 일단 보관하고 제거한다. 파서에 적용되지 않도록 하기 위함. 문서 구판에 접속시 발생하는 버그로 인한 조치.
		if (preg_match('/&oldid=/', $_SERVER["REQUEST_URI"])) {
			preg_match('/^.*$/m', $text, $fn);
			$text = str_replace("$fn[0]", '', $text);
		}
		$text = preg_replace('/^\|\| /m', '||', $text); // 테이블 맨 앞(||)의 바로 뒤에 공백이 있을 경우 제거하도록 한다.
		
		$text = preg_replace('/<pre .*?>(.*?)<\/pre>/s', '<pre>$1</pre>', $text); // pre 태그 뒤에 붙는 모든 속성을 제거한다.
		
		$text = preg_replace('/src="http:\/\/www\.youtube\.com/', 'src="//www.youtube.com', $text);
		
		# 파서를 불러온다.
		require_once("php-namumark.php");
		$wPage = new PlainWikiPage("$text");
		$wEngine = new NamuMark($wPage);
		$wEngine->prefix = "";
		$text =  $wEngine->toHtml();
		
		preg_match_all('/<math>.*?<\/math>/', $text, $math); // [내부항목] 태그로 인해 수식의 [내용]이 [[내용]]으로 대괄호 하나가 덧붙는 버그를 제거하기 위하여 모든 수식을 가져오고,
    
		# 가져온 수식 중 '[['과 ']]'를 모두 각각 '[', ']'로 바꾼다.
		foreach ($math as $tex) {
			foreach ($tex as $rtex) {
				$vowels = array(
				"]]",
				"[["
				);
				$rpe = array(
				"]",
				"["
				);
				$text = str_replace($rtex, str_replace($vowels, $rpe, $rtex), $text);
			}
		}
		
		# 상기에서 볃도로 보관한 변수의 값을 본문의 바로 앞에 추가한다.
		if (preg_match('/&oldid=/', $_SERVER["REQUEST_URI"])) {
		$text = $fn[0].$text;
		}
		
	}

}


function NamuMarkHTML( Parser &$parser, &$text ) {
	# 파서를 불러온다.
	require_once("php-namumark.class2.php");
	$wPage = new PlainWikiPage2("$text");
	$wEngine = new NamuMark2($wPage);
	$wEngine->prefix = "";
	$text =  $wEngine->toHtml();	
}

function NamuMarkHTML2( &$parser, &$text ) {
	# 파서를 불러온다.
	require_once("php-namumark.class2.php");
	$wPage = new PlainWikiPage2("$text");
	$wEngine = new NamuMark2($wPage);
	$wEngine->prefix = "";
	$text =  $wEngine->toHtml();
	
	
	
	# 모든 수식을 불러온다.
	#'^내용^' 태그로 인하여, 한 수식에 '^'가 두 개 이상의 짝수개로 존재할시 <sup>수식 내용</sup>으로 변환되는 버그를 고치기 위함이다.
	preg_match_all("/<span class=\"mwe-math-fallback-source-inline tex\" dir=\"ltr\">(.*?)<\/span>/", $text, $math);
	# 불러온 수식에 있는 '<sup>'과 '</sup>'를 모두 '^'으로 바꾼다.
	foreach($math[1] as $math_value){
		$vowels = array(
			"&lt;sup&gt;",
			"&lt;/sup&gt;"
		);
		$math_value_rpe = str_replace($vowels, '^', $math_value);
		$text = str_replace($math_value, $math_value_rpe, $text);
	}
	
	$text = str_replace('<ol class="references">', '<hr><ol class="references">', $text); // 각주 바로 앞에 <hr> 태그 추가
}	
?>