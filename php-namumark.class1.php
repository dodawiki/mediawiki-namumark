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

	protected function blockParser($block) {
		return $this->formatParser($block);
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

            if(self::startsWithi($text, '#!html')) {
                return '<html>' . preg_replace('/UNIQ--.*?--QINU/', '', substr($text, 7)) . '</html>';
            } elseif(self::startsWithi($text, '#!wiki') && preg_match('/([^\n]*)\n(((((.*)(\n)?)+)))/', substr($text, 7), $match)) {
                return '<div '.$match[1].'>'.$match[2].'</div>';
            } elseif(self::startsWithi($text, '#!syntax') && preg_match('/#!syntax ([^\s]*)/', $text, $match)) {
                return '<syntaxhighlight lang="' . $match[1] . '" line="1">' . preg_replace('/#!syntax ([^\s]*)/', '', $text) . '</syntaxhighlight>';
            } elseif(preg_match('/^\+([1-5])(.*)$/sm', $text, $size)) {
                for ($i=1; $i<=$size[1]; $i++){
                    if(isset($big_before) && isset($big_after)) {
                        $big_before .= '<big>';
                        $big_after .= '</big>';
                    } else {
                        $big_before = '<big>';
                        $big_after = '</big>';
                    }
                }

                $lines = explode("\n", $size[2]);
                $size[2] = '';
                foreach($lines as $line) {
                    if($line !== '')
                        $size[2] .= $line . "\n";
                }

                if(self::startsWith($size[2], '||')) {
                    $offset = 0;
                    $size[2] = $this->tableParser($size[2], $offset);
                }

                return $big_before.$this->formatParser($size[2]).$big_after;
            } elseif(preg_match('/^\-([1-5])(.*)$/sm', $text, $size)) {
                for ($i = 1; $i <= $size[1]; $i++) {
                    if (isset($small_before) && isset($small_after)) {
                        $small_before .= '<small>';
                        $small_after .= '</small>';
                    } else {
                        $small_before = '<small>';
                        $small_after = '</small>';
                    }
                }

                $lines = explode("\n", $size[2]);
                $size[2] = '';
                foreach($lines as $line) {
                    if($line !== '')
                        $size[2] .= $line . "\n";
                }

                if(self::startsWith($size[2], '||')) {
                    $offset = 0;
                    $size[2] = $this->tableParser($size[2], $offset);
                }

                return $small_before . $this->formatParser($size[2]) . $small_after;
            } else {
                return '<pre>' . $text . '</pre>';
            }
        }
	}




	
}
