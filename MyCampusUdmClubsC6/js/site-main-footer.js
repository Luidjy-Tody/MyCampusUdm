
document.addEventListener("DOMContentLoaded", function () {
  const footerYear = document.querySelector("[data-footer-year]");
  if (footerYear) {
    footerYear.textContent = new Date().getFullYear();
  }
});
 function abonnerNewsletter() {
    const email = document.getElementById("newsletter-email").value.trim();
    const msg   = document.getElementById("newsletter-msg");
    if (!email) { msg.style.color = "#ffe"; msg.textContent = "Veuillez entrer votre email."; return; }
    msg.textContent = "Inscription en cours...";
    const fd = new FormData();
    fd.append("email", email);
    fetch("newsletter_subscribe.php", { method: "POST", body: fd })
      .then(r => r.json())
      .then(data => {
        msg.style.color = data.success ? "#c6ffe6" : "#ffd0d0";
        msg.textContent = data.message;
        if (data.success) document.getElementById("newsletter-email").value = "";
      })
      .catch(() => { msg.style.color="#ffd0d0"; msg.textContent = "Une erreur est survenue."; });
  }
  document.getElementById("newsletter-email")?.addEventListener("keypress", e => {
    if (e.key === "Enter") abonnerNewsletter();
  });