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

class NamuMark1 extends NamuMark {

	function __construct($wtext) {

		$this->list_tag = array(
			array('*', 'ul'),
			array('1.', 'ol'),
			array('A.', 'ol style="list-style-type:upper-alpha"'),
			array('a.', 'ol style="list-style-type:lower-alpha"'),
			array('I.', 'ol style="list-style-type:upper-roman"'),
			array('i.', 'ol style="list-style-type:lower-roman"')
			);

		$this->multi_bracket = array(
			array(
				'open'	=> '{{{',
				'close' => '}}}',
				'multiline' => true,
				'processor' => array($this,'renderProcessor')),
			array(
				'open'	=> '<pre>',
				'close' => '</pre>',
				'multiline' => true,
				'processor' => array($this,'renderProcessor')),
			array(
				'open'	=> '{{|',
				'close' => '|}}',
				'multiline' => true,
				'processor' => array($this,'renderProcessor')),
			array(
				'open'	=> '<nowiki>',
				'close' => '</nowiki>',
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
				'open'	=> '{{|',
				'close' => '|}}',
				'multiline' => false,
				'processor' => array($this,'textProcessor')),
            array(
                'open'	=> '{{',
                'close' => '}}',
                'multiline' => false,
                'processor' => array($this,'mediawikiProcessor')),
			array(
				'open'	=> '[',
				'close' => ']',
				'multiline' => false,
				'processor' => array($this,'macroProcessor')),
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
				'open'	=> '__',
				'close' => '__',
				'multiline' => false,
				'processor' => array($this,'textProcessor')),
			array(
				'open'	=> '^^',
				'close' => '^^',
				'multiline' => false,
				'processor' => array($this,'textProcessor')),
			array(
				'open'	=> ',,',
				'close' => ',,',
				'multiline' => false,
				'processor' => array($this,'textProcessor')),
			array(
				'open'	=> '$ ',
				'close' => ' $',
				'multiline' => false,
				'processor' => array($this,'textProcessor')),
			array(
				'open'	=> '<!--',
				'close' => '-->',
				'multiline' => false,
				'processor' => array($this,'textProcessor')),
			array(
				'open'	=> '<nowiki>',
				'close' => '</nowiki>',
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



		for($i=0;$i<$len;self::nextChar($text,$i)) {
			$now = self::getChar($text,$i);
			if($line == '' && $now == ' ' && $list = $this->listParser($text, $i)) {
				$result .= ''
					.$list
					.'';
				$line = '';
				$now = '';
				continue;
			}

			
			if(self::startsWith($text, '|', $i) && $table = $this->tableParser($text, $i)) {
				$result .= ''
					.$table
					.'';
				$line = '';
				$now = '';
				continue;
			}

			if($line == '' && self::startsWith($text, '>', $i) && $blockquote = $this->bqParser($text, $i)) {
				$result .= ''
					.$blockquote
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

	private function bqParser($text, &$offset) {
		$len = strlen($text);		
		$innerhtml = '';
		for($i=$offset;$i<$len;$i=self::seekEndOfLine($text, $i)+1) {
			$eol = self::seekEndOfLine($text, $i);
			if(!self::startsWith($text, '>', $i)) {
				// table end
				break;
			}
			$i+=1;
			$innerhtml .= $this->formatParser(substr($text, $i, $eol-$i))."<br>";
		}
		if(empty($innerhtml))
			return false;

		$offset = $i-1;
		$innerhtml = preg_replace('/^>+/m', '', $innerhtml);
		$innerhtml = preg_replace('/<br>>+/m', '<br>', $innerhtml);
		return '<blockquote>'.$innerhtml.'</blockquote>'."\n";
	}

	private function listParser($text, &$offset) {
		$listTable = array();
		$len = strlen($text);
		$lineStart = $offset;

		$quit = false;
		for($i=$offset;$i<$len;$before=self::nextChar($text,$i)) {
			$now = self::getChar($text,$i);
			if($now != ' ') {
				if($lineStart == $i) {
					// list end
					break;
				}

				$match = false;

				foreach($this->list_tag as $list_tag) {
					if(self::startsWith($text, $list_tag[0], $i)) {

						if(!empty($listTable[0]) && $listTable[0]['tag']=='indent') {
							$i = $lineStart;
							$quit = true;
							break;
						}

						$eol = self::seekEndOfLine($text, $lineStart);
						$tlen = strlen($list_tag[0]);
						$innerstr = substr($text, $i+$tlen, $eol-($i+$tlen));
						$this->listInsert($listTable, $innerstr, ($i-$lineStart), $list_tag[1]);
						$i = $eol;
						$now = "\n";
						$match = true;
						break;
					}
				}
				if($quit)
					break;

				if(!$match) {
					// indent
					if(!empty($listTable[0]) && $listTable[0]['tag']!='indent') {
						$i = $lineStart;
						break;
					}

					$eol = self::seekEndOfLine($text, $lineStart);
					$innerstr = substr($text, $i, $eol-$i);
					$this->listInsert($listTable, $innerstr, ($i-$lineStart), 'indent');
					$i = $eol;
					$now = "\n";
				}
			}
			if($now == "\n") {
				$lineStart = $i+1;
			}
		}
		if(!empty($listTable[0])) {
			$offset = $i-1;
			return $this->listDraw($listTable);
		}
		return false;
	}

	private function listInsert(&$arr, $text, $level, $tag) {
		if(preg_match('/^#([1-9][0-9]*) /', $text, $start))
			$start = $start[1];
		else
			$start = 1;
		if(empty($arr[0])) {
			$arr[0] = array('text' => $text, 'start' => $start, 'level' => $level, 'tag' => $tag, 'childNodes' => array());
			return true;
		}

		$last = count($arr)-1;
		$readableId = $last+1;
		if($arr[0]['level'] >= $level) {
			$arr[] = array('text' => $text, 'start' => $start, 'level' => $level, 'tag' => $tag, 'childNodes' => array());
			return true;
		}
		
		return $this->listInsert($arr[$last]['childNodes'], $text, $level, $tag);
	}

	private function listDraw($arr) {
		if(empty($arr[0]))
			return '';

		$tag = $arr[0]['tag'];
		$start = $arr[0]['start'];
		$result = ($tag=='indent'?'':'<'.$tag.($start!=1?' start="'.$start.'"':'').'>');
		foreach($arr as $li) {
			$text = $this->blockParser($li['text']).$this->listDraw($li['childNodes']);
			$result .= $tag=='indent'?$text:'<li>'.$text.'</li>';
		}
		$result .= ($tag=='indent'?'':'</'.$tag.'>');
		$result .= "\n";
		return $result;
	}

	protected function blockParser($block) {
		$result = '';

		if(preg_match('/^#title (.*)$/', $block, $match)) {
			$result .= ''
				.'{{DISPLAYTITLE:'.$match[1].'}}'
				.'';
			$block = '';
		}
		
		$result .= $this->formatParser($block);
		return $result;
	}

	protected function renderProcessor($text, $type) {
        if($type == '{{|') {
            if(preg_match('/\|-/', $text))
                return $type.$text.$type;
            else
                return '<poem style="border: 2px solid #d6d2c5; background-color: #f9f4e6; padding: 1em;">'.$text.'</poem>';
        } else {
            $lines = explode("\n", $text);
            $text = '';
            foreach($lines as $key => $line) {
                if( (!$key && !$lines[$key]) || ($key == count($lines) - 1 && !$lines[$key]) )
                    continue;
                if (preg_match('/^(:+)/', $line, $match)) {
                    $line = substr($line, strlen($match[1]));
                    $add = '';
                    for ($i = 1; $i <= strlen($match[1]); $i++)
                        $add .= ' ';
                    $line = $add . $line;
                    $text .= $line . "\n";
                } else {
                    $text .= $line . "\n";
                }
            }

            if(self::startsWithi($text, '#!html'))
                return '<html>'.preg_replace('/UNIQ--.*?--QINU/', '', substr($text, 7)).'</html>';
            else
                return '<pre>'.$text.'</pre>';
        }
	}

	protected function linkProcessor($text, $type) {
        if(preg_match('/^((?:http|https|ftp|ftps)\:\/\/\S+)\|\B(.*)/', $text, $ex_link))
            return '['.$ex_link[1].' '.$ex_link[2].']';
		$text = preg_replace('/(https?.*?(\.jpeg|\.jpg|\.png|\.gif))/', '<img src="$1">', $text);
        if(preg_match('/(.*)\|(attachment:.*)/i', $text, $filelink))
			return $filelink[2].'&link='.str_replace(' ', '_',$filelink[1]);
		if(preg_match('/^(파일:.*?(?!\.jpeg|\.jpg|\.png|\.gif))\|(.*)/i', $text, $namu_image)) {
            $properties = explode("&", $namu_image[2]);

            foreach($properties as $n => $each_property) {
                if(preg_match('/^width=(.*)/i', $each_property, $width)) {
                    if(self::endsWith($width[1], '%'))
                        continue;
                    $imgwidth[1] = str_ireplace('px', '', $width[1]);
                    unset($properties[$n]);
                    continue;
                }

                if(preg_match('/^height=(.*)/i', $each_property, $height)) {
                    if(self::endsWith($height[1], '%'))
                        continue;
                    $imgheight[1] = str_ireplace('px', '', $height[1]);
                    unset($properties[$n]);
                    continue;
                }

                $properties[$n] = str_ireplace('align=', '', $each_property);
            }


            $property = '|';
            foreach($properties as $n => $each_property)
                $property .= $each_property.'|';

            if(isset($imgwidth) && isset($imgheight))
                $property .= $imgwidth[1] . 'x' . $imgheight[1] . 'px|';
            elseif(isset($imgwidth))
                $property .= $imgwidth[1].'px|';
            elseif(isset($imgheight))
                $property .= 'x'.$imgheight[1].'px|';

            $property = substr($property, 0, -1);

            return '[['.$namu_image[1].$property.']]';
        }
        return '[[' . $this->formatParser($text) . ']]';
	}

	protected function macroProcessor($text, $type) {
		$text = $this->formatParser($text);
		switch(strtolower($text)) {
			case 'br':
				return '<br>';
			case 'date':
            case 'datetime':
				return date('Y-m-d H:i:s');
			case '목차':
			case 'tableofcontents':
				return '__TOC__';
			case '각주':
			case 'footnote':
				return '<references />';
			default:
				if(self::startsWithi($text, 'include') && preg_match('/^include\((.+)\)$/i', $text, $include)) {
					$include[1] = str_replace(',', '|', $include[1]);
					$include[1] = urldecode($include[1]);
					return '{{'.$include[1].'}}'."\n";
				}
				if(self::startsWith($text, '*') && preg_match('/^\*([^ ]*)([ ].+)?$/', $text, $note)) {
					if(isset($note[2]))
						return "<ref>$note[2]</ref>";
				}
				if(preg_match('/^(youtube|nicovideo)\((.*)\)$/i', $text, $video_code))
                    return $this->videoProcessor($video_code[2], strtolower($video_code[1]));

		}
		return '['.$text.']';
	}

	protected function textProcessor($otext, $type) {
		if($type != '{{{' && $type != '<nowiki>')
			$text = $this->formatParser($otext);
		else
			$text = $otext;
		switch($type) {
			case '--':
			case '~~':
				if(!self::startsWith($text, 'item-') && !self::endsWith($text, 'UNIQ'))
				    return '<s>'.$text.'</s>';
                else
                    return $type.$text.$type;
            case '__':
				if(preg_match('/TOC/', $text))
					return $type.$text.$type;
				else
					return '<u>'.$text.'</u>';
			case '^^':
				return '<sup>'.$text.'</sup>';
			case ',,':
				return '<sub>'.$text.'</sub>';
			case '<!--':
				return '<!--'.$text.'-->';
			case '{{|':
				return '<poem style="border: 2px solid #d6d2c5; background-color: #f9f4e6; padding: 1em;">'.$text.'</poem>';
			case '<nowiki>':
				return '<nowiki>'.$text.'</nowiki>';
			case '{{{':
				if(self::startsWith($text, '#!html')) {
					$html = substr($text, 6);
					$html = htmlspecialchars_decode($html);
					return '<html>'.$html.'</html>';
				}
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
				
				return '<nowiki>'.$text.'</nowiki>';
		}
		return $type.$text.$type;
	}

    private function videoProcessor($text, $service) {
		$text = str_replace('|', ',', $text);
        $options = explode(",", $text);
        $text = '';

        foreach($options as $key => $value) {
            if($key == 0) {
                $service = str_replace('nicovideo', 'nico', $service);
                $text .= '{{#evt:service='.$service.'|id='.$value;
                continue;
            }

            $option = explode("=", $value);
            if($option[0] == 'width') {
                $width = $option[1];
                continue;
            } elseif ($option[0] == 'height') {
                $height = $option[1];
                continue;
            } elseif (preg_match('/(\d+)x(\d+)/', $value, $match)) {
				$width = $match[1];
				$height = $match[2];
				continue;
			}

            $text .= '|'.$value;
        }

        if(isset($width) && isset($height))
            $text .= '|dimensions='.$width.'x'.$height;
        elseif(isset($width))
            $text .= '|dimensions='.$width;
        elseif(isset($height))
            $text .= '|dimensions=x'.$height;

        return $text.'}}';

    }

    protected function mediawikiProcessor($text, $type) {
        if($type == '{{')
            return '{{'.$text.'}}';
    }


	
}