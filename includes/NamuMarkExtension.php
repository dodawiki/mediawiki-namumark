<?php

class NamuMarkExtension
{
	public static $mediawikiTable = [];

	public static function onBeforePageDisplay( OutputPage $out )
	{
		$out->addModules('ext.NamuMark');
	}

	public static function onParserBeforeInternalParse(Parser &$parser, &$text)
	{
		$title = $parser->getTitle();

		# 상기의 확인 함수의 반환값과, 현 URI가 히스토리인지 확인하는 함수의 반환값과, 현 문서가 특수:기여 또는 특수:기록인지 확인하는 함수의 반환값을 확인한다.
		if (!preg_match('/^특수:/', $title) && !preg_match("/&action=history/", $_SERVER["REQUEST_URI"]) && !preg_match('/^사용자:.*\.(css|js)$/', $title)) {
			$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5); // HTML 엔티티를 디코드한다.

			# '[[내부 링크|<span style="color:색깔값">표시내용<span>]]'와 같은 내부 링크 글씨의 색깔을 지정하는 방식이 버그를 일으키므로
			# 미디어위키에서 지원하는 글씨 색 방식으로 바꾼다.
			$text = preg_replace('/<span style="color:(.*?)">(.*?)<\/span>\]\]/i', '{{글씨 색|$1|$2}}]]', $text);
			$text = preg_replace('/<font color="(.*?)">(.*?)<\/font>\]\]/i', '{{글씨 색|$1|$2}}]]', $text);

			# 문서 구판에 접속시 최상단의 코드를 별도의 변수로 일단 보관하고 제거한다. 파서에 적용되지 않도록 하기 위함. 문서 구판에 접속시 발생하는 버그로 인한 조치.
			if (preg_match('/&oldid=/', $_SERVER["REQUEST_URI"])) {
				preg_match('/^.*$/m', $text, $fn);
				$text = str_replace("$fn[0]", '', $text);
			}

			$text = preg_replace('/<pre .*?>(.*?)<\/pre>/s', '<pre>$1</pre>', $text);

			# 보조 파서를 불러온다.
			$Extra = new NamuMarkExtra($text, $title);
			$Extra->title();
			$mediawikiTable = $Extra->cutMediawikiTable();
			$Extra->table();
			$Extra->indent();
			$Extra->getTemplateParameter();
			$text = $Extra->text;

			# 파서를 불러온다.
			$wEngine = new NamuMarkExtended($text, $title);
			$text = $wEngine->toHtml();

			# 상기에서 볃도로 보관한 변수의 값을 본문의 바로 앞에 추가한다.
			if (preg_match('/&oldid=/', $_SERVER["REQUEST_URI"])) {
				$text = $fn[0] . $text;
			}

			preg_match_all('/<html>(.*?)<\/html>/s', $text, $html);

			foreach ($html[1] as $code) {
				$lines = explode("\n", $code);
				$code_ex = '';
				foreach ($lines as $key => $line) {
					if ((!$key && !$lines[$key]) || ($key == count($lines) - 1 && !$lines[$key]))
						continue;
					if (preg_match('/^(:+)/', $line, $match)) {
						$line = substr($line, strlen($match[1]));
						$add = '';
						for ($i = 1; $i <= strlen($match[1]); $i++)
							$add .= ' ';
						$line = $add . $line;
						$code_ex .= $line . "\n";
					} else {
						if (!isset($lines[$key + 1]) || $lines[$key + 1] === '')
							$code_ex .= $line;
						else
							$code_ex .= $line . "\n";
					}
				}
				$xss = new XssHtml($code_ex);
				$text = str_replace($code, $xss->getHtml(), $text);
			}

			$Extra = new NamuMarkExtra($text, $title);
			$Extra->pasteMediawikiTable($mediawikiTable);
			$text = $Extra->text;
		}
	}

	public static function markHandleHTML(&$parser, &$text)
	{
		$title = $parser->getTitle();

		if (!preg_match('/^특수:/', $title) && !preg_match("/&action=history/", $_SERVER["REQUEST_URI"]) && !preg_match('/^사용자:.*\.(css|js)$/', $title)) {
			$Extra = new NamuMarkExtra($text, $title);
			$mediawikiTable = $Extra->cutMediawikiTable();
			preg_match('/(<div id="specialchars".*<\/div>)/s', $text, $charinsert);
			$text = preg_replace('/(<div id="specialchars".*<\/div>)/s', '', $text);

			$text = str_replace("<br /></p>\n<p>", '<br />', $text);
			$text = str_replace("<p><br />\n</p>", '', $text);

			$text = preg_replace('/<a rel="nofollow" target="_blank" class="external autonumber" href="(.*?)">\[(\[\d+\])\]<\/a>/',
				'<a rel="nofollow" target="_blank" class="external autonumber" href="$1">$2</a>',
				$text);

			$text = preg_replace('@^<ol><li><ol><li>.*?</li></ol></li></ol>$@ms', '', $text);

			$Extra->external();
			$Extra->imageurl();
			$Extra->printTemplateParameter();
			$Extra->pasteMediawikiTable($mediawikiTable);
			$text = $Extra->text;
		}
	}

	public static function onInternalParseBeforeLinks(&$parser, &$text)
	{
		$title = $parser->getTitle();
		if (!preg_match('/^특수:/', $title) && !preg_match("/&action=history/", $_SERVER["REQUEST_URI"]) && !preg_match('/^사용자:.*\.(css|js)$/', $title)) {
			$text = str_replace('&apos;', "'", $text);
			$text = str_replace('&gt;', ">", $text);

			$Extra = new NamuMarkExtra($text, $title);
			$Extra->table();
			$text = $Extra->text;

			# 파서를 불러온다.
			$wEngine = new NamuMarkExtended2($text, $title);
			$text =  $wEngine->toHtml();

			$Extra = new NamuMarkExtra($text, $title);
			$text = $Extra->text;

		}
	}

	public static function onParserAfterTidy(&$parser, &$text)
	{
		$title = $parser->getTitle();

		if (!preg_match('/^특수:/', $title) && !preg_match("/&action=history/", $_SERVER["REQUEST_URI"]) && !preg_match('/^사용자:.*\.(css|js)$/', $title)) {
			$text = str_replace("<br /></p>\n<p>", '<br />', $text);
			$text = str_replace("<p><br />\n</p>", '', $text);

			$text = preg_replace('/<a rel="nofollow" target="_blank" class="external autonumber" href="(.*?)">\[(\[\d+\])\]<\/a>/', '<a rel="nofollow" target="_blank" class="external autonumber" href="$1">$2</a>', $text);

			$text = preg_replace('@^<ol><li><ol><li>.*?</li></ol></li></ol>$@ms', '', $text);

			$wEngine = new NamuMarkExtended($text, $title);
			$text =  $wEngine->toHtml();

			$Extra = new NamuMarkExtra($text, $title);
			$Extra->enter();
			$text = $Extra->text;
		}
	}
}
