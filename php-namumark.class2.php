<?php
/**
 * namumark.php - Namu Mark Renderer
 * Copyright (C) 2015 koreapyj koreapyj0@gmail.com
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 */

class PlainWikiPage2 {
	public $title, $text, $lastchanged;
	function __construct($text) {
		$this->title = '(inline wikitext)';
		$this->text = $text;
		$this->lastchanged = time();
	}

	function getPage($name) {
		return new PlainWikiPage2('');
	}
}


class NamuMark2 {
	public $prefix, $lastchange;

	function __construct($wtext) {


		$this->multi_bracket = array(
			array(
				'open'	=> '{{{',
				'close' => '}}}',
				'multiline' => true,
				'processor' => array($this,'renderProcessor')),
			);

		$this->single_bracket = array(
			array(
				'open'	=> '{{{',
				'close' => '}}}',
				'multiline' => false,
				'processor' => array($this,'textProcessor')),
			array(
				'open'	=> '[[',
				'close' => ']]',
				'multiline' => false,
				'processor' => array($this,'linkProcessor')),
			array(
				'open'	=> '~~',
				'close' => '~~',
				'multiline' => false,
				'processor' => array($this,'textProcessor')),
			array(
				'open'	=> '--',
				'close' => '--',
				'multiline' => false,
				'processor' => array($this,'textProcessor')),
			);
			
		
		$this->WikiPage = $wtext;

		$this->toc = array();
		$this->fn = array();
		$this->fn_cnt = 0;
		$this->prefix = '';
	}

	public function toHtml() {
		$this->whtml = $this->WikiPage->text;
		$this->whtml = $this->htmlScan($this->whtml);
		return $this->whtml;
	}

	private function htmlScan($text) {
		$result = '';
		$len = strlen($text);
		$now = '';
		$line = '';


		for($i=0;$i<$len;self::nextChar($text,$i)) {
			$now = self::getChar($text,$i);





			foreach($this->multi_bracket as $bracket) {
				if(self::startsWith($text, $bracket['open'], $i) && $innerstr = $this->bracketParser($text, $i, $bracket)) {
					$result .= ''
						.$this->lineParser($line, '')
						.$innerstr
						.'';
					$line = '';
					$now = '';
					break;
				}
			}

			if($now == "\n") { // line parse
				$result .= $this->lineParser($line, '');
				$line = '';
			}
			else
				$line.=$now;
		}
		if($line != '')
			$result .= $this->lineParser($line, 'notn');
		return $result;
	}


	private function blockParser($block) {
		$result = '';
		$block_len = strlen($block);

		$result .= $this->formatParser($block);
		return $result;
	}

	private function bracketParser($text, &$now, $bracket) {
		$len = strlen($text);
		$cnt = 0;
		$done = false;

		$openlen = strlen($bracket['open']);
		$closelen = strlen($bracket['close']);

		for($i=$now;$i<$len;self::nextChar($text,$i)) {
			if(self::startsWith($text, $bracket['open'], $i) && !($bracket['open']==$bracket['close'] && $cnt>0)) {
				$cnt++;
				$done = true;
				$i+=$openlen-1; // 반복될 때 더해질 것이므로
			}elseif(self::startsWith($text, $bracket['close'], $i)) {
				$cnt--;
				$i+=$closelen-1;
			}elseif(!$bracket['multiline'] && $text[$i] == "\n")
				return false;

			if($cnt == 0 && $done) {
				$innerstr = substr($text, $now+$openlen, $i-$now-($openlen+$closelen)+1);

				if((!strlen($innerstr)) ||($bracket['multiline'] && strpos($innerstr, "\n")===false))
					return false;
				$result = call_user_func_array($bracket['processor'],array($innerstr, $bracket['open']));
				$now = $i;
				return $result;
			}
		}
		return false;
	}

	private function formatParser($line) {
		$line_len = strlen($line);
		for($j=0;$j<$line_len;self::nextChar($line,$j)) {
			foreach($this->single_bracket as $bracket) {
				$nj=$j;
				if(self::startsWith($line, $bracket['open'], $j) && $innerstr = $this->bracketParser($line, $nj, $bracket)) {
					$line = substr($line, 0, $j).$innerstr.substr($line, $nj+1);
					$line_len = strlen($line);
					$j+=strlen($innerstr)-1;
					break;
				}
			}
		}
		return $line;
	}

	private function renderProcessor($text, $type) {
		if(self::startsWithi($text, '#!html')) {
			$html = substr($text, 7);
			$html = htmlspecialchars_decode($html);
			$html = preg_replace('/<\/?(?:object|param)[^>]*>/', '', $html);
			$html = preg_replace('/<embed([^>]+)>/', '<iframe$1 frameborder="0"></iframe>', $html);
			$html = preg_replace('/(<img[^>]*[ ]+src=[\'\"]?)(http\:[^\'\"\s]+)([\'\"]?)/', '$1//cdn.mirror.wiki/$2$3', $html);
			$html = preg_replace('/(<(?:iframe|embed)[^>]*[ ]+src=[\'\"]?)(http\:[^\'\"\s]+)([\'\"]?)/', '$1//iframe.mirror.wiki/$2$3', $html);
#			$html = preg_replace('/(<script[^>]*[ ]+src=[\'\"]?)(http\:[^\'\"\s]+)([\'\"]?)/', '$1//js.mirror.wiki/$2$3', $html);
			return $html;
		}
		
	}

	private function linkProcessor($text, $type) {
		if(self::startsWithi($text, 'html')) {
			$html = $text;
			$html = preg_replace('/html\((.*)\)/', '$1', $html);
			$html = htmlspecialchars_decode($html);
			$html = preg_replace('/<\/?(?:object|param)[^>]*>/', '', $html);
			$html = preg_replace('/<embed([^>]+)>/', '<iframe$1 frameborder="0"></iframe>', $html);
			$html = preg_replace('/(<img[^>]*[ ]+src=[\'\"]?)(http\:[^\'\"\s]+)([\'\"]?)/', '$1//cdn.mirror.wiki/$2$3', $html);
			$html = preg_replace('/(<(?:iframe|embed)[^>]*[ ]+src=[\'\"]?)(http\:[^\'\"\s]+)([\'\"]?)/', '$1//iframe.mirror.wiki/$2$3', $html);
			return $html;
		}

		
	}

	

	private function textProcessor($otext, $type) { 
		if($type != '{{{')
			$text = $this->formatParser($otext);
		else
			$text = $otext;
		switch($type) {
			case '--':
			case '~~':
				return '<s>'.$text.'</s>';
			case '{{{':
				if(self::startsWith($text, '#!html')) {
					$html = substr($text, 7);
					$html = htmlspecialchars_decode($html);
					return $html;
				}
				if(preg_match('/^#(?:([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})|([A-Za-z]+)) (.*)$/', $text, $color)) {
					if(empty($color[1]) && empty($color[2]))
						return $text;
					return '<span style="color: '.(empty($color[1])?$color[2]:'#'.$color[1]).'">'.$this->formatParser($color[3]).'</span>';
				}

				return '<code>'.$text.'</code>';
		}
		return $type.$text.$type;
	}

	
	private function lineParser($line, $type) {
		$result = '';
		$line_len = strlen($line);

		// comment
		if(self::startsWith($line, '##')) {
			$line = '';
		}

		$line = $this->blockParser($line);

		if($type == 'notn') {
			return $line;
		} else {
		return $line."\n";
		}
	}

	private static function getChar($string, $pointer){
		if(!isset($string[$pointer])) return false;
		$char = ord($string[$pointer]);
		if($char < 128){
			return $string[$pointer];
		}else{
			if($char < 224){
				$bytes = 2;
			}elseif($char < 240){
				$bytes = 3;
			}elseif($char < 248){
				$bytes = 4;
			}elseif($char == 252){
				$bytes = 5;
			}else{
				$bytes = 6;
			}
			$str = substr($string, $pointer, $bytes);
			return $str;
		}
	}

	private static function nextChar($string, &$pointer){
		if(!isset($string[$pointer])) return false;
		$char = ord($string[$pointer]);
		if($char < 128){
			return $string[$pointer++];
		}else{
			if($char < 224){
				$bytes = 2;
			}elseif($char < 240){
				$bytes = 3;
			}elseif($char < 248){
				$bytes = 4;
			}elseif($char == 252){
				$bytes = 5;
			}else{
				$bytes = 6;
			}
			$str = substr($string, $pointer, $bytes);
			$pointer += $bytes;
			return $str;
		}
	}

	private static function startsWith($haystack, $needle, $offset = 0) {
		$len = strlen($needle);
		if(($offset+$len)>strlen($haystack))
			return false;
		return $needle == substr($haystack, $offset, $len);
	}

	private static function startsWithi($haystack, $needle, $offset = 0) {
		$len = strlen($needle);
		if(($offset+$len)>strlen($haystack))
			return false;
		return strtolower($needle) == strtolower(substr($haystack, $offset, $len));
	}

	private static function seekEndOfLine($text, $offset=0) {
		return ($r=strpos($text, "\n", $offset))===false?strlen($text):$r;
	}
}