export async function addTouchedClass() {
  const formFields = document.querySelectorAll("input, textarea, select");
  formFields.forEach(function (field) {
    if (field.type !== "submit") {
      field.addEventListener("focus", function () {
        field.setAttribute("data-touched", "true");
      });
      field.addEventListener("blur", function () {
        field.setAttribute("data-touched", "true");
      });
    }
  });
}
