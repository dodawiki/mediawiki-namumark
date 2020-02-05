document.querySelector('.nm-folding .text').addEventListener('click', function(event) {
  var content = event.target.parentNode.querySelector('.folding-content');
  if (content.style.display === 'block') {
  	content.style.display = 'none';
  } else {
  	content.style.display = 'block';
  }
})
