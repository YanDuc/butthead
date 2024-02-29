// handle event on li click
import { getResponse } from "./ajax_handler.js";

const currentPage = document.getElementById("page").value;
const parent = document.getElementById("parent").value;
const page = parent ? parent + "/" + currentPage : currentPage;
const preview = document.getElementById("preview");
const publish = document.getElementById("publish");

async function confirmDelete(page) {
  const deleteForm = new FormData();
  deleteForm.append("pagePath", page);
  try {
    await getResponse("PageManager", "removePage", deleteForm);
    location.reload();
  } catch (error) {
    console.error("error:", error);
  }
}
window.confirmDelete = confirmDelete;

async function confirmCopy(page) {
  const copyForm = new FormData();
  copyForm.append("pagePath", page);
  try {
    await getResponse("PageManager", "copyPage", copyForm);
    location.reload();
  } catch (error) {
    console.error("error:", error);
  }
}
window.confirmCopy = confirmCopy;

publish.addEventListener("click", async (event) => {
  event.preventDefault();
  const form = new FormData();
  form.append("page", page);
  try {
    await getResponse("Builder", "build", form);
    location.reload();
  } catch (error) {
    console.error("error:", error);
  }
})

preview.addEventListener("click", async (event) => {
  event.preventDefault();

  // get page Content
  const formData = new FormData();
  formData.append("page", page);
  try {
    // compile html
    const htmlForm = new FormData();
    htmlForm.append("path", page);
    const compiledHtml = await getResponse(
      "HTMLProcessor",
      "compile",
      htmlForm
    );

    // open compiled html in new tab
    const wnd = window.open("about:blank", "", "_blank");
    wnd.document.write(compiledHtml);
  } catch (error) {
    console.error("error:", error);
  }
});
