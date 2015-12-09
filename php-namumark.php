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
 
class NamuMark {

	protected function lineParser($line, $type) {
		if($line == '----')
			return '<hr>';

		$line = $this->blockParser($line);

		if($type == 'notn')
			return $line;
		else
            return $line."\n";
	}

	protected function formatParser($line) {
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

	protected function bracketParser($text, &$now, $bracket) {
		$len = strlen($text);
		$cnt = 0;
		$done = false;

		$openlen = strlen($bracket['open']);
		$closelen = strlen($bracket['close']);

		for($i=$now;$i<$len;self::nextChar($text,$i)) {
			if(self::startsWith($text, $bracket['open'], $i) && !($bracket['open']==$bracket['close'] && $cnt>0)) {
				$cnt++;
				$done = true;
				$i+=$openlen-1; // �ݺ��� �� ������ ���̹Ƿ�
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
	
	protected static function getChar($string, $pointer){
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

	protected static function nextChar($string, &$pointer){
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

	protected static function startsWith($haystack, $needle, $offset = 0) {
		$len = strlen($needle);
		if(($offset+$len)>strlen($haystack))
			return false;
		return $needle == substr($haystack, $offset, $len);
	}

	protected static function startsWithi($haystack, $needle, $offset = 0) {
		$len = strlen($needle);
		if(($offset+$len)>strlen($haystack))
			return false;
		return strtolower($needle) == strtolower(substr($haystack, $offset, $len));
	}

	
	protected static function seekEndOfLine($text, $offset=0) {
		return ($r=strpos($text, "\n", $offset))===false?strlen($text):$r;
	}
	
	protected function tableParser($text, &$offset) {
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
				$trAttr = $trStyleList = array();
				
				if($simpleColspan != 0) {
					$tdAttr['colspan'] = $simpleColspan+1;
					$simpleColspan = 0;
				}
				
				$innerstr = preg_replace('/\s+?</', '<', $innerstr);
				
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
											$tableStyleList['border'] = '2px solid ';
											$tableStyleList['border'] .= $tbprop[2];
											break;
										case 'width':
											$tableStyleList['width'] = $tbprop[2];
											break;
									}
								}
							}
							elseif(preg_match('/^(\||\-|v|\^)\|?([0-9]+)$/', $prop, $span)) {
								if($span[1] == '-') {
									$tdAttr['colspan'] = $span[2];
									break;
								}
								elseif($span[1] == '|') {
									$tdAttr['rowspan'] = $span[2];
									break;
								}
								elseif($span[1] == '^') {
									$tdAttr['rowspan'] = $span[2];
									$tdStyleList['vertical-align'] = 'top';
									break;
								}
								elseif($span[1] == 'v') {
									$tdAttr['rowspan'] = $span[2];
									$tdStyleList['vertical-align'] = 'bottom';
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
									case 'rowbgcolor':
										$trStyleList['background-color'] = $match[2];
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
//					elseif(self::startsWith($innerstr, ' ')) {
//						$tdStyleList['text-align'] = 'right';
//					}
					else {
						$tdStyleList['text-align'] = null;
					}
				}
				$innerstr = trim($innerstr);
				
				$tdAttr['style'] = '';
				foreach($tdStyleList as $styleName =>$tdstyleValue) {
					if(empty($tdstyleValue))
						continue;
					$tdAttr['style'] .= $styleName.': '.$tdstyleValue.'; ';
				}
				
				$trAttr['style'] = '';
				foreach($trStyleList as $styleName =>$trstyleValue) {
					if(empty($trstyleValue))
						continue;
					$trAttr['style'] .= $styleName.': '.$trstyleValue.'; ';
				}

				$tdAttrStr = '';
				foreach($tdAttr as $propName => $propValue) {
					if(empty($propValue))
						continue;
					$tdAttrStr .= ' '.$propName.'="'.str_replace('"', '\\"', $propValue).'"';
				}
				
				if (!isset($trAttrStri)) {
					$trAttrStri = true;
					$trAttrStr = '';
					foreach($trAttr as $propName => $propValue) {
						if(empty($propValue))
							continue;
						$trAttrStr .= ' '.$propName.'="'.str_replace('"', '\\"', $propValue).'"';
					}
				}
				
				$trInnerStr .= '<td'.$tdAttrStr.'>'.$this->blockParser($innerstr).'</td>';
			}
			$tableInnerStr .= !empty($trInnerStr)?'<tr'.$trAttrStr.'>'.$trInnerStr.'</tr>':'';
			unset($trAttrStri);
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
		$result = '<table class="wikitable"'.$tableAttrStr.'>'.$tableInnerStr."</table>\n";
		$offset = $i-1;
		return $result;
	}
	
}

?>