import { getResponse } from "./ajax_handler.js";

const currentPage = document.getElementById("page").value;
const parent = document.getElementById("parent").value;
const pagePath = parent ? parent + "/" + currentPage : currentPage;
const descriptionInput = document.getElementById("description");
const addToNavInput = document.getElementById("addToNav");
const urlInput = document.getElementById("url");
const pageNameInput = document.getElementById("pageName");
const editPageForm = document.getElementById("editPageForm");
const response = document.getElementById("response");
const error = document.getElementById("error");

const form = new FormData();
form.append("pagePath", pagePath);

let pageMeta = null;
try {
  pageMeta = await getResponse("PageManager", "getPageParams", form);
} catch (error) {
  console.error("error:", error);
}

if (pageMeta) {
  descriptionInput.value = pageMeta.description;
  pageNameInput.value = pageMeta.pageName;
  urlInput.value = pageMeta.url;
  addToNavInput.checked = pageMeta.addToNav;
}


// Function to handle form submission
editPageForm.addEventListener("submit", async function (event) {
  event.preventDefault();

  const addToNav = addToNavInput.checked;
  // Get the form data
  const editForm = new FormData(editPageForm);
  // delete addToNav from form
  editForm.delete("addToNav");
  editForm.append("addToNav", addToNav);

  // verify if url is valid
  if (!/^[a-zA-Z0-9-]+$/.test(editForm.get("url"))) {
    error.textContent = "Invalid URL. Only letters, numbers, and dashes are allowed.";
    return;
  }


  editForm.append("pagePath", pagePath);

  try {
    await getResponse("PageManager", "editPage", editForm);
    response.innerHTML = 'Page updated successfully!';
    location.reload();
  } catch (error) {
    error.innerHTML = 'Error updating page';
  }

  // try {
  //   const res = await getResponse("PageManager", "add", formData);
  //   window.location.href = `?page=${res.page}&parent=${res.parent}`;
  // } catch (error) {
  //   document.getElementById("response").textContent = error.message;
  // }
});
