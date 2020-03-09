var foldingItemList = document.querySelectorAll('.nm-folding .text');
for ( var i = 0; i < foldingItemList.length; i++ ) {
  foldingItemList[i].addEventListener('click', function(event) {
    var content = event.target.parentNode.querySelector('.folding-content');
    if (content.style.display === 'block') {
    	content.style.display = 'none';
    } else {
    	content.style.display = 'block';
    }
  })
}
