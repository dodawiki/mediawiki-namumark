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

class NamuMark3 extends NamuMark {

	function __construct($wtext) {
		
		$this->single_bracket = array(
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

	protected function htmlScan($text) {
		$result = '';
		$len = strlen($text);
		$line = '';

		for($i=0;$i<$len;$this->nextChar($text,$i)) {
			$now = $this->getChar($text,$i);

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

	protected function renderProcessor($text, $type) {
		if(preg_match('/^&lt;(#.*?)&gt;/m', $text, $match) || preg_match('/^&lt;bgcolor=(#.*?)&gt;/m', $text, $match)) {
			$text = str_replace($match[0], '', $text);
			return '<div style="border: 2px solid #d6d2c5; background-color: '.$match[1].'; padding: 1em;"><p>'.$text.'</p></div>';
		} else {
			return '<div style="border: 2px solid #d6d2c5; background-color: #f9f4e6; padding: 1em;"><p>'.$text.'</p></div>';
		}
	}

    protected function textProcessor($text, $type) {
        if(preg_match('/^#(?:([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})|([A-Za-z]+)) (.*)$/', $text, $color)) {
            if(empty($color[1]) && empty($color[2]))
                return $text;
            return '<span style="color: '.(empty($color[1])?$color[2]:'#'.$color[1]).'">'.$this->formatParser($color[3]).'</span>';
        }
    }

    protected function blockParser($block) {
		return  $this->formatParser($block);
    }

}