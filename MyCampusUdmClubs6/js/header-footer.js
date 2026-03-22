// software.js

const menuBtn = document.querySelector(".menu-btn");
const sideMenu = document.getElementById("sideMenu");
const closeMenu = document.querySelector(".close-menu");
const menuBackdrop = document.getElementById("menuBackdrop");

function openMenu() {
  sideMenu.classList.add("open");
  menuBackdrop.classList.add("open");
  document.body.classList.add("no-scroll");
  sideMenu.setAttribute("aria-hidden", "false");
}

function closeMenuFn() {
  sideMenu.classList.remove("open");
  menuBackdrop.classList.remove("open");
  document.body.classList.remove("no-scroll");
  sideMenu.setAttribute("aria-hidden", "true");
}

if (menuBtn) {
  menuBtn.addEventListener("click", openMenu);
}

if (closeMenu) {
  closeMenu.addEventListener("click", closeMenuFn);
}

if (menuBackdrop) {
  menuBackdrop.addEventListener("click", closeMenuFn);
}

// Close with ESC key
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape" && sideMenu.classList.contains("open")) {
    closeMenuFn();
  }
});
