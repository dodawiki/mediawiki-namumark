# 나무마크 for MediaWiki

**나무마크**는 [나무위키](https://namu.wiki)에서 사용하는 [문법](https://namu.wiki/w/%EB%82%98%EB%AC%B4%EC%9C%84%ED%82%A4:%ED%8E%B8%EC%A7%91%20%EB%8F%84%EC%9B%80%EB%A7%90) 중 일부를 미디어위키 확장 기능으로 구현한 것입니다.

원 구현체: [php-namumark](https://github.com/koreapyj/php-namumark), [Orimark](https://github.com/Oriwiki/php-namumark-mediawiki)

## 라이선스

본 확장 기능은 GNU Affero GPL 3.0에 따라 자유롭게 사용하실 수 있습니다. 라이선스에 대한 자세한 사항은 LICENSE.txt를 참고하십시오.

## 의존

- MediaWiki 버전 1.32 이상
- [**Cite** 확장기능](https://www.mediawiki.org/wiki/Extension:Cite)
- [**SimpleMathJax** 확장기능](https://www.mediawiki.org/wiki/Extension:SimpleMathJax) 또는 [**Math** 확장기능](https://www.mediawiki.org/wiki/Extension:Math) (※ SimpleMathJax를 권장합니다.)
- [**Poem** 확장기능](https://www.mediawiki.org/wiki/Extension:Poem)

## 사용 방법

1.  미디어위키 extensions 폴더에 NamuMark 폴더를 새로 생성합니다. 또는 서버에 git([다운로드](https://git-scm.com/download))이 설치되어 있어 git을 이용하실 수 있으면 설치된 미디어위키의 extensions 폴더에서 다음과 같이 명령합니다.

    ```bash
    git clone https://github.com/dodawiki/mediawiki-namumark.git NamuMark
    ```

2.  [여기](https://github.com/dodawiki/mediawiki-namumark/releases/latest)를 눌러 .zip 파일 혹은 .tar.gz 파일을 다운받은 다음 압축을 풀고, 압축이 풀린 파일을 모두 NamuMark 폴더에 넣습니다. (git으로 한 경우 필요 없습니다.)
3.  LocalSettings.php에 다음을 입력합니다.

    ```php
    wfLoadExtension( 'NamuMark' );

    $wgRawHtml = true;
    $wgAllowExternalImages = true;
    $wgNamespacesWithSubpages[NS_MAIN] = true;
    $wgNamespacesWithSubpages[NS_TEMPLATE] = true;
    $wgAllowDisplayTitle = true;
    $wgRestrictDisplayTitle = false;
    $wgDefaultUserOptions['numberheadings'] = 1;
    $wgAllowSlowParserFunctions = true; # [pagecount(이름공간)] 문법을 사용하기 위해서는 켜야 합니다.
    ```

## 기존 나무마크 미디어위키판 (일명 '오리마크')에서의 전환

해당 확장 기능은 기본적으로 동일한 소스를 포크한 것이기 때문에, 전환이 어렵지 않습니다.

1. 기존에 `require_once`로 확장을 로딩하셨다면, **사용 방법** 문단에 따라 `wfLoadExtension`으로 전환하시기 바랍니다. **`require_once`를 통한 로딩을 지원하지 않기 때문입니다.**
2. [EmbedVideo 확장 기능](https://www.mediawiki.org/wiki/Extension:EmbedVideo)이 의존성에서 제외되었습니다. 나무마크 때문에 해당 확장을 사용하셨다면 제거하셔도 됩니다.

다만 실제 위키에 적용하시기 전에, 테스트는 필수적으로 진행하시기 바랍니다. 일부 소스가 변경되거나 제거되어 미작동하는 부분이 존재할 수 있는 점 참고해주세요.

## 그 외

- **정상 작동을 보증하지 않습니다.**
- 나무위키와 다르거나 일부 지원되지 않는 문법이 존재합니다. 해당 익스텐션은 완벽한 호환을 목표로 하지 않습니다.
- 소스 코드가 많이 더럽습니다. 😅
- `$wgAllowSlowParserFunctions` 옵션을 켜면 제공되는 `{{PAGESINNAMESPACE}}` 매직 워드는 성능 이슈가 있을 수 있습니다.
