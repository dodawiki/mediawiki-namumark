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
			$text = $this->dd($text);
			$this->getTemplateParameter($text);
		}
		
		return $text;
	}

	private function dd($text) {
		preg_match_all('/^ +(.*)$/m', $text, $indent);
		foreach ($indent[1] as $each_indent) {
			if (!isset($i)) {
				$i = 0;
			}
			if(!preg_match('/^(\*|\|)/m', $each_indent)) {
				$tar_in[$i] = $each_indent;
				$i++;
			}
		
		}
		if (isset($tar_in)) {
			foreach($tar_in as $each_tar_in) {
				$each_tar_in_q = preg_quote($each_tar_in, '/');
				preg_match('/^( +?)'.$each_tar_in_q.'$/m', $text, $blank);
				if (isset($blank[0]) && strlen($blank[0]) > 1) {
					if(!preg_match('/^ 1\.|^ A\.|^ I\./i', $blank[0])) {				
						$blank[1] = preg_replace('/ /', ':', $blank[1]);
						$text = str_replace($blank[0], $blank[1].$each_tar_in, $text);
					}
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

        return $text;
    }
	
	
}


?>