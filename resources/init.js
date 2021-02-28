const foldingItemList = document.querySelectorAll(".nm-folding .text");
for (let i = 0; i < foldingItemList.length; i++) {
  foldingItemList[i].addEventListener("click", function (event) {
    const content = event.target.parentNode.querySelector(".folding-content");
    if (content.style.display === "block") {
      content.style.display = "none";
    } else {
      content.style.display = "block";
    }
  });
}
