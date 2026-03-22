function showForm(formId) {
  document.querySelectorAll(".form-box").forEach(function(form) {
    form.classList.remove("active");
  });

  document.getElementById(formId).classList.add("active");
}