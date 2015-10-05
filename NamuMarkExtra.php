<?php

class NamuMarkExtra {
	
	function output($text, $is_html) {
		if($is_html) {
			preg_match('/(<div id="specialchars".*<\/div>)/s', $text, $charinsert);
			$text = preg_replace('/(<div id="specialchars".*<\/div>)/s', '', $text);
			
			$text = $this->external($text);
			$text = $this->imageurl($text);
			
			if( count( $charinsert ) > 0 ) {
				$text = $text.$charinsert[0];
			}
			
		} elseif(!$is_html) {
			$text = $this->title($text);
			$text = $this->dd($text);
			$text = $this->attachment_link($text);
		}
		
		return $text;
	}
	
	private function attachment_link($text) {
		$text = preg_replace('/\[\[(.*?)[\| ]attachment:(.*?(\.jpeg|\.jpg|\.png|\.gif))(\?.*?)?\]\]/i', 'attachment:$2$4``link=$1``', $text);
		
		preg_match_all('/``link=(.*?)``/', $text, $link, PREG_SET_ORDER);
		
		foreach ($link as $filelink) {
			$filelink[1] = str_replace(' ', '_', $filelink[1]);
			$filelink[1] = preg_replace('/wiki:"(.*)"/', '$1', $filelink[1]);
			$text = str_replace($filelink[0], '&link='.$filelink[1], $text);
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
	
	
}


?>