<?php

class NamuMarkExtra {
	
	public function output($text, $is_html) {
		if($is_html) {
			preg_match('/(<div id="specialchars".*<\/div>)/s', $text, $charinsert);
			$text = preg_replace('/(<div id="specialchars".*<\/div>)/s', '', $text);
			
			$text = $this->external($text);
			$text = $this->imageurl($text);
            $text = $this->printTemplateParameter($text);

			if( count( $charinsert ) > 0 ) {
				$text = $text.$charinsert[0];
			}
			
		} elseif(!$is_html) {
			$text = $this->title($text);
			$text = $this->table($text);
			$text = $this->indent($text);
			$this->getTemplateParameter($text);
		}
		
		return $text;
	}

	private function indent($text) {
		if(preg_match_all('/^( +)([^* ][^\n]*)$/m', $text, $indent, PREG_SET_ORDER)) {
            foreach ($indent as $each_indent) {
                if (!preg_match('/^(1\.|A\.|I\.)/i', $each_indent[2])) {
					if(preg_match('/^>/', $each_indent[2]))
						$each_indent[1] = str_replace(' ', '', $each_indent[1]);
					else
                    	$each_indent[1] = str_replace(' ', ':', $each_indent[1]);
                    $each_indent[0] = '/^'.preg_quote($each_indent[0], '/').'$/m';
                    $text = preg_replace($each_indent[0], $each_indent[1].$each_indent[2], $text);
                }
            }
        }

        return $text;
	}
	
	private function title($text) {
		$text = preg_replace("/^(=+ .* =+)(\s+)?$/m", "\n$1", $text);
		return $text;
	}
	
	private function external($text) {
		$text = preg_replace(
			'/\[(<a rel="nofollow" target="_blank" class=".*?" href=".*?">.*?<\/a>)\]/',
			'$1',
			$text
		);
			
		return $text;			
	}
	
	private function imageurl($text) {
		preg_match_all('/<a rel="nofollow" target="_blank" class="external free" href="(.*?)">.*?<\/a>/', $text, $exurlarr, PREG_SET_ORDER);
	
		foreach ($exurlarr as $image_path) {
			$n = 0;
			if (preg_match('/(.*)(\.jpeg|\.jpg|\.png|\.gif)(.*)/', $image_path[1], $image_url)) {
				$text = str_replace($image_path[0], '<img src="'.$image_url[1].$image_url[2].'"><br>', $text);
				$n = 1;
			}
		
			if ($n == '0') {
				$header = @get_headers($image_path[1], 1);
			}
			if(isset($header['Content-Type']) && !is_array($header['Content-Type']) && preg_match('/image/', $header['Content-Type'])) {
				$text = str_replace("$image_path[0]", "<img src=\"$image_path[1]\"><br>\n", $text);
			}
		}
		
		return $text;
		
	}

	private function getTemplateParameter($text) {
        if(preg_match_all('/\{\{(.*?)\}\}/', $text, $includes, PREG_SET_ORDER)) {
            foreach($includes as $include) {
                $GLOBALS['template'] = explode('|', $include[1]);
            }
        }
        if(preg_match_all('/\{\{(.*?)\}\}/s', $text, $includes, PREG_SET_ORDER)) {
            foreach($includes as $include) {
                $GLOBALS['template'] = explode('|', $include[1]);
            }
        }
    }

    private function printTemplateParameter($text) {
		if(isset($GLOBALS['template'])) {
			$properties = $GLOBALS['template'];
			$i = 1;
			if (is_array($properties)) {
				foreach ($properties as $property) {
					if ($property == $properties[0])
						continue;
					$property = explode('=', $property);
					$property[0] = trim($property[0]);
					if (isset($property[1])) {
						$text = str_replace('@' . $property[0] . '@', $property[1], $text);
					} else {
						$text = str_replace('@' . $i . '@', $property[0], $text);
						$i++;
					}
				}
			}
		}

        return $text;
    }

	public function table($text) {
        $text = preg_replace('/^ \|\|/m', '||', $text); // ���̺� �� ��(||)�� �ٷ� �տ� ������ ���� ��� �����ϵ��� �Ѵ�.
		$text = preg_replace('/^\|([^\|]+?)\|(.*?\|\|)$/m', '||<table caption=$1>$2', $text);

        preg_match_all('/^(\|\|.*?\|\|)\s*$/sm', $text, $tables);
        foreach($tables[1] as $table) {
            $newtable = preg_replace('/^((?:\|\|)+)(<table.*?\>)((?:\|\|)+)(.+)/im', '$1$3$2$4', $table);
			$newtable = preg_replace('/^\|\|\s+/m', '||', $newtable); // ���̺� �� ��(||)�� �ٷ� �ڿ� ������ ���� ��� �����ϵ��� �Ѵ�.
			$newtable = str_replace(['|| <', '> <'], ['||<', '><'], $newtable);
            $text = str_replace($table, str_replace("\n", '<br />', $newtable), $text);
        }
		$text = preg_replace('/\n\|\|$/', '||', $text);

        return $text;
	}

	public function enter($text) {
		$lines = explode("\n", $text);
		$text = '';
		foreach($lines as $line_n => $line) {
			$line = preg_replace('/^\s*/', '', $line);

			if(preg_match('@<pre>@i', $line)) {
				// pre 태그 시작
				$text .= $line . "\n";
				$is_pre = true;
				continue;
			} elseif(isset($is_pre) && $is_pre === true && preg_match('@</pre>@i', $line)) {
				// pre 태그 끝
				$is_pre = false;
			} elseif(isset($is_pre) && $is_pre === true) {
				$text .= $line . "\n";
				continue;
			}

			if(!isset($lines[$line_n - 1])) {
				$text .= $line . "\n";
				continue;
			} else {
				$prev_line = preg_replace('/^\s*/', '', $lines[$line_n - 1]);
			}

			if( $line === '' || $prev_line === '' || preg_match('/^<[^>]*>$/', $line) || preg_match('/^<[^>]*>$/', $prev_line) || preg_match('@(</li>|</div>|</ul>|</h\d>|</table>|<br/>|<br>|<br />|</dl>|<ol.*>|</ol>)$@i', $prev_line) || preg_match('@^(</?p>|<a onclick|<dl>|<dd>|<ul>|<li>|<ol>)@i', $line) )
				$text .= $line . "\n";
			else
				$text .= '<br>' . $line . "\n";
		}
        
		return $text;

	}
	
	
}


?>