<?php

class NamuMark3 extends NamuMark {
    protected function htmlScan($text) {
        $result = '';
        $len = strlen($text);
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
}
