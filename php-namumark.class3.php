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


		$this->multi_bracket = array(
			array(
				'open'	=> '||',
				'close' => '||',
				'multiline' => true,
				'processor' => array($this,'renderProcessor')),				
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

			foreach($this->multi_bracket as $bracket) {
				if($this->startsWith($text, $bracket['open'], $i) && $innerstr = $this->bracketParser($text, $i, $bracket)) {
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

	protected function renderProcessor($text, $type) {
		if(preg_match('/^&lt;(#.*?)&gt;/m', $text, $match) || preg_match('/^&lt;bgcolor=(#.*?)&gt;/m', $text, $match)) {
			$text = str_replace($match[0], '', $text);
			return '<div style="border: 2px solid #d6d2c5; background-color: '.$match[1].'; padding: 1em;"><p>'.$text.'</p></div>';
		} else {
			return '<div style="border: 2px solid #d6d2c5; background-color: #f9f4e6; padding: 1em;"><p>'.$text.'</p></div>';
		}
	}
		
	protected function lineParser($line, $type) {
		$result = '';

		if($type == 'notn') {
			return $line;
		} else {
		return $line."\n";
		}
	}

}