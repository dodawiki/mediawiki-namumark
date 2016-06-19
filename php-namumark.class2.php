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

	protected function blockParser($block) {
        $block = $this->formatParser($block);
		$result = '';

		if(preg_match('/^(.*?)(?<!<nowiki>)attachment:"?(.*?)(\.jpeg|\.jpg|\.png|\.gif)"?([?&][^\| <]+)?(?!<\/nowiki>)(.*)$/i', $block, $match)) {

            if(preg_match('@^/@', $match[2]))
                $match[2] = substr($match[2], 1);
            elseif(preg_match('@/@', $match[2]))
                $match[2] = str_replace('/', '__', $match[2]);
            else
                $match[2] = str_replace('/', '__', $this->title).'__'.$match[2];


			if(!strlen($match[4])) {
				$result .= ''
					.$match[1].'[[파일:'.$match[2].$match[3].']]'
					.'';

			} else {
				$match[4] = str_replace(array('?', '&'), '|', $match[4]);
				$match[4] = preg_replace('/width=(\d*)/i', '$1px', $match[4]);
				$match[4] = str_replace(['align=', 'pxpx'], ['', 'px'], $match[4]);

				$result .= ''
					.$match[1].'[[파일:'.$match[2].$match[3].$match[4].']]'
					.'';
			}

			$block = $this->blockParser($match[5]);
		}

		if(preg_match("/^(.*?)(?<!<nowiki>|\[)(https?[^<]*?)(\.jpeg|\.jpg|\.png|\.gif)([?&][^< ']+)(?!<\/nowiki>)(.*)$/i", $block, $match)) {
			$match[4] = str_replace(array('?', '&'), ' ', $match[4]);

			$match[4] = str_ireplace('align=left', '', $match[4]);
			$match[4] = str_ireplace('align=center', 'style="display:block; margin-left:auto; margin-right:auto;"', $match[4]);

			$result .= ''
				. $match[1] . '<img src="' . $match[2] . $match[3] . '"' . $match[4] . '>'
				. '';

			$block = $this->blockParser($match[5]);
		}

		$result .= $block;
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
            foreach ($lines as $key => $line) {
                if ((!$key && !$lines[$key]) || ($key == count($lines) - 1 && !$lines[$key]))
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

            if (self::startsWithi($text, '#!html')) {
                $text = substr($text, 7);
                $text = htmlspecialchars_decode($text);
                require_once("XSSfilter.php");
                $xss = new XssHtml($text);
                return $xss->getHtml();
            } else {
                return '<pre>' . $text . '</pre>';
            }
        }
    }

}