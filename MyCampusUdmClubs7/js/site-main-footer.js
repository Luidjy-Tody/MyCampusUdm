
document.addEventListener("DOMContentLoaded", function () {
  const footerYear = document.querySelector("[data-footer-year]");
  if (footerYear) {
    footerYear.textContent = new Date().getFullYear();
  }
});
