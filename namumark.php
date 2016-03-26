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

	// The version of the extension, which will appear on Special:Version.
	// This can be a number or a string.
	'version' => '1.0.19',
 
	// Your name, which will appear on Special:Version.
	'author' => 'koreapyj 원본, 김동동 수정',
 
	// The URL to a wiki page/web page with information about the extension,
	// which will appear on Special:Version.
	'url' => 'https://github.com/Oriwiki/php-namumark-mediawiki',
   
	'license-name' => "AGPL-3.0",   // Short name of the license, links LICENSE or COPYING file if existing - string, added in 1.23.0
  
);

$wgHooks['ParserBeforeStrip'][] = 'NamuMark';
$wgHooks['InternalParseBeforeLinks'][] = 'NamuMarkHTML';
$wgHooks['ParserBeforeTidy'][] = 'NamuMarkHTML2';
$wgHooks['ParserAfterTidy'][] = 'NamuMarkExtraHTML';

require_once('php-namumark.php');
require_once("NamuMarkExtra.php");

function NamuMark(&$parser, &$text, &$strip_state) {
	$title = $parser->getTitle(); // 문서의 제목을 title로 변수화한다.

	
	# 상기의 확인 함수의 반환값과, 현 URI가 히스토리인지 확인하는 함수의 반환값과, 현 문서가 특수:기여 또는 특수:기록인지 확인하는 함수의 반환값을 확인한다.
	if (!preg_match('/^특수:/', $title) && !preg_match("/&action=history/", $_SERVER["REQUEST_URI"]) && !preg_match('/^사용자:.*\.(css|js)$/', $title)) {
		$text = html_entity_decode($text,  ENT_QUOTES | ENT_HTML5);   // HTML 엔티티를 디코드한다.

		# '[[내부 링크|<span style="color:색깔값">표시내용<span>]]'와 같은 내부 링크 글씨의 색깔을 지정하는 방식이 버그를 일으키므로
		# 미디어위키에서 지원하는 글씨 색 방식으로 바꾼다.
		$text = preg_replace('/<span style="color:(.*?)">(.*?)<\/span>\]\]/i', '{{글씨 색|$1|$2}}]]', $text);
		$text = preg_replace('/<font color="(.*?)">(.*?)<\/font>\]\]/i', '{{글씨 색|$1|$2}}]]', $text);

		# 문서 구판에 접속시 최상단의 코드를 별도의 변수로 일단 보관하고 제거한다. 파서에 적용되지 않도록 하기 위함. 문서 구판에 접속시 발생하는 버그로 인한 조치.
		if (preg_match('/&oldid=/', $_SERVER["REQUEST_URI"])) {
			preg_match('/^.*$/m', $text, $fn);
			$text = str_replace("$fn[0]", '', $text);
		}

		$text = preg_replace('/<pre .*?>(.*?)<\/pre>/s', '<pre>$1</pre>', $text); // pre 태그 뒤에 붙는 모든 속성을 제거한다.

		# 보조 파서를 불러온다.
		$Extra = new NamuMarkExtra;
		$text = $Extra->output($text, false);

		# 파서를 불러온다.
		require_once("php-namumark.class1.php");
		$wEngine = new NamuMark1($text);
		$text =  $wEngine->toHtml();
				
		
		preg_match_all('/<math>.*?<\/math>/', $text, $math); // [내부항목] 태그로 인해 수식의 [내용]이 [[내용]]으로 대괄호 하나가 덧붙는 버그를 제거하기 위하여 모든 수식을 가져오고,
    
		# 가져온 수식 중 '[['과 ']]'를 모두 각각 '[', ']'로 바꾼다.
		foreach ($math as $tex) {
			foreach ($tex as $rtex)
				$text = str_replace($rtex, str_replace(array("]]", "[["), array("]", "["), $rtex), $text);
		}
				
		# 상기에서 볃도로 보관한 변수의 값을 본문의 바로 앞에 추가한다.
		if (preg_match('/&oldid=/', $_SERVER["REQUEST_URI"]))
			$text = $fn[0].$text;

		preg_match_all('/<html>(.*?)<\/html>/s', $text, $html);
		require_once 'XSSfilter.php';
		foreach ($html[1] as $code) {
			$lines = explode("\n", $code);
			$code_ex = '';
			foreach($lines as $key => $line) {
				if( (!$key && !$lines[$key]) || ($key == count($lines) - 1 && !$lines[$key]) )
					continue;
				if (preg_match('/^(:+)/', $line, $match)) {
					$line = substr($line, strlen($match[1]));
					$add = '';
					for ($i = 1; $i <= strlen($match[1]); $i++)
						$add .= ' ';
					$line = $add . $line;
                    $code_ex .= $line . "\n";
				} else {
                    $code_ex .= $line . "\n";
				}
			}
			$xss = new XssHtml($code_ex);
			$text = str_replace($code, $xss->getHtml(), $text);
		}

	}

}


function NamuMarkHTML( Parser &$parser, &$text ) {
	$title = $parser->getTitle();
	if (!preg_match('/^특수:/', $title) && !preg_match("/&action=history/", $_SERVER["REQUEST_URI"]) && !preg_match('/^사용자:.*\.(css|js)$/', $title)) {
		$text = str_replace('&apos;', "'", $text);

		$Extra = new NamuMarkExtra;
		$text = $Extra->table($text);

        $text = preg_replace('/\[attachment:(.*?)\]/', 'attachment:$1', $text);

		# 파서를 불러온다.
		require_once("php-namumark.class2.php");
		$wEngine = new NamuMark2($text, $title);
		$text =  $wEngine->toHtml();

	}

}

function NamuMarkHTML2( &$parser, &$text ) {
	$title = $parser->getTitle();
	if (!preg_match('/^특수:/', $title) && !preg_match("/&action=history/", $_SERVER["REQUEST_URI"]) && !preg_match('/^사용자:.*\.(css|js)$/', $title)) {
		# 파서를 불러온다.
		require_once("php-namumark.class3.php");
		$wEngine = new NamuMark3($text);
		$text =  $wEngine->toHtml();
	
	
		$text = str_replace("<br /></p>\n<p>", '<br />', $text);
	
		$text = preg_replace('/<a rel="nofollow" target="_blank" class="external autonumber" href="(.*?)">\[(\[\d+\])\]<\/a>/',
		'<a rel="nofollow" target="_blank" class="external autonumber" href="$1">$2</a>',
		$text);

        $text = preg_replace('@^<ol><li><ol><li>.*?</li></ol></li></ol>$@ms', '', $text);

        // 엔터 한 번 개행
        $br_regex = '@^(.*?)(?<!<br/>|<br>|<br />)\n(?!<p>|<h|</p|<e|<u|<l|편집한 내용은 아직|이것을 입력하지|<a onclick|<br|</ol|</li|<if|<div|</div|<dl|<dd|</u|<m|</m|<t|</t|<o|</o|<blockquote)([^\n])@m';
        do {
		$text = preg_replace($br_regex, '$1<br>$2', $text);
        } while(preg_match($br_regex, $text));

	}
}

function NamuMarkExtraHTML ( &$parser, &$text ) {
	$title = $parser->getTitle(); // 문서의 제목을 title로 변수화한다.

	if (!preg_match('/^특수:/', $title) && !preg_match("/&action=history/", $_SERVER["REQUEST_URI"]) && !preg_match('/^사용자:.*\.(css|js)$/', $title)) {
		$Extra = new NamuMarkExtra;
		$text = $Extra->output($text, true);
	}
}

?>