<?php

class NamuMarkExtra
{

	function __construct($text, $title)
	{
		$this->text = $text;
		$this->title = $title;
	}

	public function indent()
	{
		if (preg_match_all('/^(?! *{{| *}}| *\|)( +)([^* ][^\n]*)$/m', $this->text, $indent, PREG_SET_ORDER)) {
			foreach ($indent as $each_indent) {
				if (!preg_match('/^(1\.|A\.|I\.)/i', $each_indent[2])) {
					if (preg_match('/^>/', $each_indent[2]))
						$each_indent[1] = str_replace(' ', '', $each_indent[1]);
					else
						$each_indent[1] = str_replace(' ', ':', $each_indent[1]);
					$each_indent[0] = '/^' . preg_quote($each_indent[0], '/') . '$/m';
					$this->text = preg_replace($each_indent[0], $each_indent[1] . $each_indent[2], $this->text);
				}
			}
		}
	}

	public function title()
	{
		$this->text = preg_replace("/^(=+ .* =+)(\s+)?$/m", "\n$1", $this->text);
	}

	public function external()
	{
		$this->text = preg_replace(
			'/\[(<a rel="nofollow" target="_blank" class=".*?" href=".*?">.*?<\/a>)\]/',
			'$1',
			$this->text
		);
	}

	public function getTemplateParameter()
	{
		if (preg_match_all('/\{\{(.*?)\}\}/', $this->text, $includes, PREG_SET_ORDER)) {
			foreach ($includes as $include) {
				$GLOBALS['template'] = explode('|', $include[1]);
			}
		}
		if (preg_match_all('/\{\{(.*?)\}\}/s', $this->text, $includes, PREG_SET_ORDER)) {
			foreach ($includes as $include) {
				$GLOBALS['template'] = explode('|', $include[1]);
			}
		}
	}

	public function printTemplateParameter()
	{
		if (isset($GLOBALS['template'])) {
			$properties = $GLOBALS['template'];
			$i = 1;
			if (is_array($properties)) {
				foreach ($properties as $property) {
					if ($property == $properties[0])
						continue;
					$property = explode('=', $property);
					$property[0] = trim($property[0]);
					if (isset($property[1])) {
						$this->text = str_replace('@' . $property[0] . '@', $property[1], $this->text);
					} else {
						$this->text = str_replace('@' . $i . '@', $property[0], $this->text);
						$i++;
					}
				}
			}
		}

	}

	public function table()
	{
		$this->text = preg_replace('/^\|([^\|\}]+?)\|(.*?\|\|)$/m', '||<table caption=$1>$2', $this->text);

		if (preg_match_all('/^(\|\|.*?\|\|)\s*$/sm', $this->text, $tables)) {
			foreach ($tables[1] as $table) {
				$newtable = preg_replace('/^((?:\|\|)+)(<table.*?\>)((?:\|\|)+)(.+)/im', '$1$3$2$4', $table);
				if (preg_match('/\{\{\{(?:\+|-)\d$/m', $newtable))
					$this->text = str_replace($table, $newtable, $this->text);
				else
					$this->text = str_replace($table, str_replace("\n", '<br />', $newtable), $this->text);
			}
		}
		$this->text = preg_replace('/\n\|\|$/', '||', $this->text);

		if (preg_match_all('/(\{\{\{(?:\+|-)\d\n(\|\|.*)\}\}\}) *?\|\|/s', $this->text, $tables, PREG_SET_ORDER)) {
			foreach ($tables as $n => $table) {
				if (preg_match('/(\{\{\{(?:\+|-)\d\n(\|\|.*)\}\}\}) *?\|\|/s', $table[2], $sub_table)) {
					$tEngine = new NamuMarkExtended($sub_table[1], null);
					$newtable = str_replace("\n", '', $tEngine->toHtml());
					$table[1] = str_replace($sub_table[1], $newtable, $table[1]);
				}
				$tEngine = new NamuMarkExtended($table[1], null);
				$newtable = str_replace("\n", '', $tEngine->toHtml());
				$this->text = str_replace($tables[$n][1], $newtable, $this->text);
			}
		}

	}

	public function enter()
	{
		$lines = explode("\n", $this->text);
		$this->text = '';
		foreach ($lines as $line_n => $line) {
			$line = preg_replace('/^\s*/', '', $line);

			if (preg_match('@<pre>@i', $line)) {
				// pre 태그 시작
				$this->text .= $line . "\n";
				$is_pre = true;
				continue;
			} elseif (isset($is_pre) && $is_pre === true && preg_match('@</pre>@i', $line)) {
				// pre 태그 끝
				$is_pre = false;
			} elseif (isset($is_pre) && $is_pre === true) {
				$this->text .= $line . "\n";
				continue;
			}

			if (!isset($lines[$line_n - 1])) {
				$this->text .= $line . "\n";
				continue;
			} else {
				$prev_line = preg_replace('/^\s*/', '', $lines[$line_n - 1]);
			}

			if ($line === '' || $prev_line === '' || preg_match('/^<(?!img)[^>]*>$/', $line) || preg_match('/^<(?!img)[^>]*>$/', $prev_line) || preg_match('@(</li>|</div>|</ul>|</h\d>|</table>|<br/>|<br>|<br />|</dl>|<ol.*>|</ol>|</blockquote>|</t.>)$@i', $prev_line) || preg_match('@^(</?p>|<a onclick|<dl>|<dd>|<ul>|<li>|<ol>)@i', $line))
				$this->text .= $line . "\n";
			else
				$this->text .= '<br>' . $line . "\n";
		}

	}

	public function cutMediawikiTable()
	{
		preg_match_all('/(?<!\{)\{\|(.*?)\|\}(?!\})/s', $this->text, $matches);
		$mediawikiTable = array();

		foreach ($matches[0] as $key => $match) {
			$mediawikiTable[$key] = $match;
			$this->text = str_replace($match, '<preserved type=mediawikiTable no=' . $key . '>', $this->text);
		}

		return $mediawikiTable;
	}

	public function pasteMediawikiTable($mediawikiTable)
	{
		preg_match_all('/<preserved type=mediawikiTable no=(\S*?)>/', $this->text, $matches, PREG_SET_ORDER);

		foreach ($matches as $match) {
			$contents = preg_split('/(?:\|-|\|\+)/', $mediawikiTable[$match[1]], -1);
			foreach ($contents as $contents_key => $each_contents) {
				if ($contents_key == 0)
					continue;
			}

			$this->text = str_replace($match[0], $mediawikiTable[$match[1]], $this->text);
		}

	}
}


