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

		$this->single_bracket = array(
			array(
				'open'	=> '[[',
				'close' => ']]',
				'multiline' => false,
				'processor' => array($this,'linkProcessor')),
			array(
				'open'	=> '[',
				'close' => ']',
				'multiline' => false,
				'processor' => array($this,'macroProcessor')),
			array(
				'open'	=> '__',
				'close' => '__',
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


if(self::startsWith($text, '|', $i) && $table = $this->tableParser($text, $i)) {
				$result .= ''
					.$table
					.'';
				$line = '';
				$now = '';
				continue;
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

private function tableParser($text, &$offset) {
		$tableTable = array();
		$len = strlen($text);
		$lineStart = $offset;
		
		$tableInnerStr = '';
		$tableStyleList = array();
		for($i=$offset;$i<$len;$i=self::seekEndOfLine($text, $i)+1) {
			$now = self::getChar($text,$i);
			$eol = self::seekEndOfLine($text, $i);
			if(!self::startsWith($text, '||', $i)) {
				// table end
				break;
			}
			$line = substr($text, $i, $eol-$i);
			$td = explode('||', $line);
			$td_cnt = count($td);

			$trInnerStr = '';
			$simpleColspan = 0;
			for($j=1;$j<$td_cnt-1;$j++) {
				$innerstr = htmlspecialchars_decode($td[$j]);

				if($innerstr=='') {
					$simpleColspan += 1;
					continue;
				}

				$tdAttr = $tdStyleList = array();

				if($simpleColspan != 0) {
					$tdAttr['colspan'] = $simpleColspan+1;
					$simpleColspan = 0;
				}
				while(self::startsWith($innerstr, '<')) {
					$dummy=0;
					$prop = $this->bracketParser($innerstr, $dummy, array('open'	=> '<', 'close' => '>','multiline' => false,'processor' => function($str) { return $str; }));
					$innerstr = substr($innerstr, $dummy+1);
					switch($prop) {
						case '(':
							break;
						case ':':
							$tdStyleList['text-align'] = 'center';
							break;
						case ')':
							$tdStyleList['text-align'] = 'right';
							break;
						default:
							if(self::startsWith($prop, 'table ')) {
								$tbprops = explode(' ', $prop);
								foreach($tbprops as $tbprop) {
									if(!preg_match('/^([^=]+)=(?|"(.*)"|\'(.*)\'|(.*))$/', $tbprop, $tbprop))
										continue;
									switch($tbprop[1]) {
										case 'align':
											switch($tbprop[2]) {
												case 'left':
													$tableStyleList['margin-left'] = null;
													$tableStyleList['margin-right'] = 'auto';
													break;
												case 'center':
													$tableStyleList['margin-left'] = 'auto';
													$tableStyleList['margin-right'] = 'auto';
													break;
												case 'right':
													$tableStyleList['margin-left'] = 'auto';
													$tableStyleList['margin-right'] = null;
													break;
											}
											break;
										case 'bgcolor':
											$tableStyleList['background-color'] = $tbprop[2];
											break;
										case 'bordercolor':
											$tableStyleList['border-color'] = $tbprop[2];
											break;
										case 'width':
											$tableStyleList['width'] = $tbprop[2];
											break;
									}
								}
							}
							elseif(preg_match('/^(\||\-)([0-9]+)$/', $prop, $span)) {
								if($span[1] == '-') {
									$tdAttr['colspan'] = $span[2];
									break;
								}
								elseif($span[1] == '|') {
									$tdAttr['rowspan'] = $span[2];
									break;
								}
							}
							elseif(preg_match('/^#(?:([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})|([A-Za-z]+))$/', $prop, $span)) {
								$tdStyleList['background-color'] = $span[1]?'#'.$span[1]:$span[2];
								break;
							}
							elseif(preg_match('/^([^=]+)=(?|"(.*)"|\'(.*)\'|(.*))$/', $prop, $match)) {
								switch($match[1]) {
									case 'bgcolor':
										$tdStyleList['background-color'] = $match[2];
										break;
									case 'width':
										$tdStyleList['width'] = $match[2];
										break;
									case 'height':
										$tdStyleList['height'] = $match[2];
										break;
								}
							}
					}
				}

				if(empty($tdStyleList['text-align'])) {
					if(preg_match('/^ .* $/', $innerstr)) {
						$tdStyleList['text-align'] = 'center';
					}
					elseif(self::seekEndOfLine($innerstr)>0 && $innerstr[self::seekEndOfLine($innerstr)-1] == ' ') {
						$tdStyleList['text-align'] = null;
					}
					elseif(self::startsWith($innerstr, ' ')) {
						$tdStyleList['text-align'] = 'right';
					}
					else {
						$tdStyleList['text-align'] = null;
					}
				}
				$innerstr = trim($innerstr);
				
				$tdAttr['style'] = '';
				foreach($tdStyleList as $styleName =>$styleValue) {
					if(empty($styleValue))
						continue;
					$tdAttr['style'] .= $styleName.': '.$styleValue.'; ';
				}

				$tdAttrStr = '';
				foreach($tdAttr as $propName => $propValue) {
					if(empty($propValue))
						continue;
					$tdAttrStr .= ' '.$propName.'="'.str_replace('"', '\\"', $propValue).'"';
				}
				$trInnerStr .= '<td'.$tdAttrStr.'>'.$this->blockParser($innerstr).'</td>';
			}
			$tableInnerStr .= !empty($trInnerStr)?'<tr>'.$trInnerStr.'</tr>':'';
		}

		if(empty($tableInnerStr))
			return false;

		$tableStyleStr = '';
		foreach($tableStyleList as $styleName =>$styleValue) {
			if(empty($styleValue))
				continue;
			$tableStyleStr .= $styleName.': '.$styleValue.'; ';
		}

		$tableAttrStr = ($tableStyleStr?' style="'.$tableStyleStr.'"':'');
		$result = '<table class="wikitable"'.$tableAttrStr.'>'.$tableInnerStr.'</table>';
		$offset = $i-1;
		return $result;
	}
	private function renderProcessor($text, $type) {
		
		if(!preg_match('/\|/', $text)) {
			$text = str_replace("\n", '<br>', $text);
			$text = preg_replace('/<br>:+/', '<br> ', $text);
			if(preg_match('/^&lt;(#.*?)&gt;/m', $text, $match)) {
				$text = str_replace($match[0], '', $text);
				return '<div style="border: 2px solid #d6d2c5; background-color: '.$match[1].'; padding: 1em;"><p>'.$text.'</p></div>';
			} else {
				return '<div style="border: 2px solid #d6d2c5; background-color: #f9f4e6; padding: 1em;"><p>'.$text.'</p></div>';
			}
		}
	}
	
	private function blockParser($block) {
		$result = '';
		$block_len = strlen($block);
		
		if(preg_match('/^(.*?)(?<!<nowiki>)(https?.*?)(\.jpeg|\.jpg|\.png|\.gif)([?&][^< ]+)(?!<\/nowiki>)(.*)$/', $block, $match)) {
			$vowels = array('?', '&');
			$match[4] = str_replace($vowels, ' ', $match[4]);
			$result .= ''
				.$match[1].'<img src="'.$match[2].$match[3].'"'.$match[4].'>'
				.'';
			$block = $this->blockParser($match[5]);
		}
		
		if(preg_match('/^(.*?)(?<!<nowiki>)attachment:"?(.*?)(\.jpeg|\.jpg|\.png|\.gif)"?([?&][^\| <]+)?(?!<\/nowiki>)(.*)$/i', $block, $match)) {
			
			$match[2] = preg_replace('/^\//', '', $match[2]);
			
			if(strlen($match[4]) == 0) {
				$result .= ''
					.$match[1].'[[파일:'.$match[2].$match[3].']]'
					.'';
					
			} elseif(preg_match('/%/', $match[4])) {
				$vowels = array('?', '&');
				$match[4] = str_replace($vowels, '|', $match[4]);
				$match[4] = str_replace('%', '', $match[4]);
				$match[4] = str_replace('width', 'newwidth', $match[4]);
				$match[4] = str_replace('align', 'caption', $match[4]);
				return '{{ScaleImage|imagename='.$match[2].$match[3].$match[4].'}}';
			} else {
				$vowels = array('?', '&');
				$match[4] = str_replace($vowels, '|', $match[4]);
				$match[4] = preg_replace('/width=(\d*)/i', '$1px', $match[4]);
				$match[4] = str_replace('align=', '', $match[4]);
				
				$result .= ''
					.$match[1].'[[파일:'.$match[2].$match[3].$match[4].']]'
					.'';
					
			}
			
			$block = $this->blockParser($match[5]);
		}
		


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

	private function linkProcessor($text, $type) {
		
		if(self::startsWithi($text, 'wiki')) {
			if(preg_match('/wiki: ?"(.*?)" ?(.*)/', $text, $wikilinks)) {
				if(preg_match('/https?.*?(\.jpeg|\.jpg|\.png|\.gif)/' ,$wikilinks[2])) {
					$wikilinks[2] = '{{{#!html <img src="'.$wikilinks[2].'">}}}';
				}

				return '[['.$wikilinks[1].'|'.$wikilinks[2].']]';
			}	
		} elseif(preg_match('/^"(.*?)" ?(.*)/m', $text, $wikilinks)) {
			return '[['.$wikilinks[1].'|'.$wikilinks[2].']]';
		} elseif(preg_match('/^br$/im', $text)) {
			return '<br>';
		}
		
	}
	
	private function macroProcessor($text, $type) {
		switch(strtolower($text)) {
			case 'br':
				return '<br>';
			default:
				if(preg_match('/wiki: ?"(.*?)" ?(.*)/', $text, $wikilinks) || preg_match('/^"(.*?)" ?(.*)/m', $text, $wikilinks) || preg_match('/wiki:(\w*?) (.*)/u', $text, $wikilinks)) {
					if($wikilinks[2] !== '') {
					return '[['.$wikilinks[1].'|'.$wikilinks[2].']]';
					} else {
					return '[['.$wikilinks[1].']]';
					}
				} elseif(!self::startsWith($text, '[') && !preg_match('/^https?/m', $text)) {
					return '[['.$text.']]';
				}
		}
	
		return '['.$text.']';
	}

	private function lineParser($line, $type) {
		$result = '';
		$line_len = strlen($line);


		$line = $this->blockParser($line);

		if($type == 'notn') {
			return $line;
		} else {
		return $line."\n";
		}
	}
	
	private function textProcessor($text, $type) {
		if(preg_match('/TOC/', $text)) {
			return '__'.$text.'__';
		} else {
			return '<u>'.$text.'</u>';
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