
# DDark Mark
(한글 명칭) **도다 마크**는 [나무위키](https://namu.wiki)에서 사용하는 [나무마크](https://namu.wiki/w/%EB%82%98%EB%AC%B4%EC%9C%84%ED%82%A4:%ED%8E%B8%EC%A7%91%20%EB%8F%84%EC%9B%80%EB%A7%90)를 미디어위키 확장기능으로 구현한 것입니다.

[php-namumark](https://github.com/koreapyj/php-namumark)을 기반으로 동작하는 [오리마크](https://github.com/Oriwiki/php-namumark-mediawiki)를 포크한 확장 기능 입니다.

## 라이선스
본 확장기능은 GNU Affero GPL 3.0에 따라 자유롭게 사용하실 수 있습니다. 라이선스에 대한 자세한 사항은 첨부 문서를 참고하십시오.

## 의존
* [EmbedVideo 확장기능](https://www.mediawiki.org/wiki/Extension:EmbedVideo)
* [Cite 확장기능](https://www.mediawiki.org/wiki/Extension:Cite)
* [Math 확장기능](https://www.mediawiki.org/wiki/Extension:Math) 또는 [SimpleMathJax 확장기능](https://www.mediawiki.org/wiki/Extension:SimpleMathJax) (※ SimpleMathJax 를 권장합니다.)
* [Poem 확장기능](https://www.mediawiki.org/wiki/Extension:Poem)

## 사용 방법
1. 미디어위키 extensions 폴더에 DDarkMark 폴더를 새로 생성합니다. 또는 서버에 직접 git을 이용하실 수 있으면 설치된 미디어위키의 extensions 폴더에서 다음과 같이 명령합니다.

		git clone https://github.com/ddarkr/php-ddarkmark.git DDarkMark

1. [여기](https://github.com/ddarkr/php-ddarkmark/archive/master.zip)를 눌러 다운받은 다음 압축을 풀고, 압축이 풀린 파일을 모두 DDarkMark 폴더에 넣습니다. (git으로 한 경우 필요 없습니다.)
1. LocalSettings.php에 다음을 입력합니다.

    ```php
	require_once "$IP/extensions/DDarkMark/ddarkmark.php";
	$wgRawHtml = true;
	$wgAllowImageTag = true;
	$wgNamespacesWithSubpages[NS_MAIN] = true;
	$wgNamespacesWithSubpages[NS_TEMPLATE] = true;
	$wgAllowDisplayTitle = true;
	$wgRestrictDisplayTitle = false;
	$wgDefaultUserOptions['numberheadings'] = 1;
    ```

	
## 그 외
아직까지 라이브러리의 기능이 완벽하게 구현돼있지 않습니다. 이점을 참고하시고 실제 미디어위키 사이트에 적용하실 때에는 반드시 사전에 시험해보실 것을 권장하는 바입니다.

