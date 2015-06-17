# php-namumark-mediawiki
php-namumark-mediawiki는 나무위키에서 사용하는 나무마크를 미디어위키 확장기능으로 구현한 것입니다.

## 라이선스
본 라이브러리는 GNU Affero GPL 3.0에 따라 자유롭게 사용하실 수 있습니다. 라이선스에 대한 자세한 사항은 첨부 문서를 참고하십시오.

## 사용 방법
1. 미디어위키 extensions 폴더에 NamuMark 폴더를 새로 생성합니다.
2. NamuMark.php 파일과 namumark.php 파일을 넣습니다.
3. LocalSettings.php에 다음을 입력합니다.
> require_once "$IP/extensions/NamuMark/NamuMark.php";
4. 이어서 바로 다음을 입력합니다. 미디어위키의 $wgArticlePath 값에서 /$1를 뺀 값이나 http://yourwiki.com/~~~/Frontpage 와 같은 도메인에서 /~~~부분을 입력하시면 됩니다.
> $namu_articepath = '/w';
	
## 그 외
상당한 발코딩입니다. 항상 죄송스럽게 생각합니다.

아직까지 라이브러리의 기능이 완벽하게 구현돼있지 않습니다. 이점을 참고하시고 실제 미디어위키 사이트에 적용하실 때에는 반드시 사전에 시험해보실 것을 권장하는 바입니다.

