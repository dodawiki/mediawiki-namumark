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

class NamuMark2 extends NamuMark {

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
			array(
				'open'	=> '{{{',
				'close' => '}}}',
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
		$this->whtml = $this->WikiPage;
		$this->whtml = $this->htmlScan($this->whtml);
		return $this->whtml;
	}

	private function htmlScan($text) {
		$result = '';
		$len = strlen($text);
		$now = '';
		$line = '';


		for($i=0;$i<$len;$this->nextChar($text,$i)) {
			$now = $this->getChar($text,$i);


			if($this->startsWith($text, '|', $i) && $table = $this->tableParser($text, $i)) {
				$result .= ''
					.$table
					.'';
				$line = '';
				$now = '';
				continue;
			}

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
	
	protected function blockParser($block) {
		$result = '';

		if(preg_match('/^(.*?)(?<!<nowiki>)attachment:"?(.*?)(\.jpeg|\.jpg|\.png|\.gif)"?([?&][^\| <]+)?(?!<\/nowiki>)(.*)$/i', $block, $match)) {

			$match[2] = preg_replace('/^\//', '', $match[2]);

			if(strlen($match[4]) == 0) {
				$result .= ''
					.$match[1].'[[파일:'.$match[2].$match[3].']]'
					.'';

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

		if(preg_match('/^(.*?)(?<!<nowiki>)(https?.*?)(\.jpeg|\.jpg|\.png|\.gif)([?&][^< ]+)(?!<\/nowiki>)(.*)$/i', $block, $match)) {
			$vowels = array('?', '&');
			$match[4] = str_replace($vowels, ' ', $match[4]);

			$match[4] = str_ireplace('align=left', '', $match[4]);
			$match[4] = str_ireplace('align=center', 'style="display:block; margin-left:auto; margin-right:auto;"', $match[4]);

			$result .= ''
				. $match[1] . '<img src="' . $match[2] . $match[3] . '"' . $match[4] . '>'
				. '';

			$block = $this->blockParser($match[5]);
		}

		$result .= $this->formatParser($block);
		return $result;
	}

	protected function linkProcessor($text, $type) {
		
		if($this->startsWithi($text, 'wiki')) {
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
		} else {
			return '[['.$this->formatParser($text).']]';
		}
		
	}
	
	protected function macroProcessor($text, $type) {
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
				} elseif(!$this->startsWith($text, '[') && !preg_match('/^https?/m', $text)) {
					return '[['.$text.']]';
				}
		}
	
		return '['.$text.']';
	}

	protected function textProcessor($text, $type) {
		switch($type) {
			case '__':
				if(preg_match('/TOC/', $text)) {
					return '__'.$text.'__';
				} else {
					return '<u>'.$text.'</u>';
				}
			case '--':
			case '~~':
				if (!self::startsWith($text, 'QINU') && !preg_match('/^(nowiki|ref)-.*-QINU/', $text) && !self::startsWith($text, 'h-') && !self::startsWith($text, 'item-'))
					return '<s>'.$text.'</s>';
				else
					return $type.$text.$type;
			case '{{{':
				if(preg_match('/^#(?:([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})|([A-Za-z]+)) (.*)$/', $text, $color)) {
					if(empty($color[1]) && empty($color[2]))
						return $text;
					return '<span style="color: '.(empty($color[1])?$color[2]:'#'.$color[1]).'">'.$this->formatParser($color[3]).'</span>';
				}
				if(preg_match('/^\+([1-5]) (.*)$/', $text, $size)) {
					for ($i=1; $i<=$size[1]; $i++){
						if(isset($big_before) && isset($big_after)) {
							$big_before .= '<big>';
							$big_after .= '</big>';
						} else {
							$big_before = '<big>';
							$big_after = '</big>';
						}
					}

					return $big_before.$this->formatParser($size[2]).$big_after;
				}
				if(preg_match('/^\-([1-5]) (.*)$/', $text, $size)) {
					for ($i=1; $i<=$size[1]; $i++){
						if(isset($small_before) && isset($small_after)) {
							$small_before .= '<small>';
							$small_after .= '</small>';
						} else {
							$small_before = '<small>';
							$small_after = '</small>';
						}
					}

					return $small_before.$this->formatParser($size[2]).$small_after;
				}
		}
		
	}

    protected function renderProcessor($text, $type) {
        if(self::startsWithi($text, '#!html')) {
            $text = substr($text, 7);
            $text = htmlspecialchars_decode($text);
			require_once("XSSfilter.php");
			$xss = new XssHtml($text);
            return $xss->getHtml();
        }
    }

}