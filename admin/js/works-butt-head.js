// handle event on li click
import { getResponse, translate } from "./ajax_handler.js";
import { addTouchedClass } from "./form_validation.js";
import { htmlFormFragment, concatFormData } from "./form_builder.js";

const currentPage = document.getElementById("page").value;
const parent = document.getElementById("parent").value;
const page = parent ? parent + "/" + currentPage : currentPage;

const liBlocs = document.querySelectorAll("li.bloc");
const liLayouts = document.querySelectorAll("li.layout");
const pageContent = document.getElementById("page-content");
const preview = document.getElementById("preview");

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

preview.addEventListener("click", async (event) => {
  event.preventDefault();
  const formData = new FormData();
  formData.append("page", page);
  try {
    const html = await getResponse(
      "ContentManager",
      "getPageContent",
      formData
    );
    const htmlForm = new FormData();
    htmlForm.append("html", html);
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

liLayouts.forEach((li) => {
  li.addEventListener("click", async (event) => {
    const layout = event.currentTarget.id;

    // Create a new FormData object
    const formData = new FormData();

    // Append the bloc to the formData
    formData.append("page", page);
    formData.append("layout", layout);

    // Send request to PHP
    event.preventDefault();

    try {
      await getResponse("ContentManager", "addLayout", formData);
      location.reload();
    } catch (error) {
      console.error("error:", error);
    }
  });
});

// Function to construct and replace the form
function constructAndReplaceForm(
  inputsArray,
  submitId,
  formName = "default-form"
) {
  const firstChild = pageContent.firstChild;
  const tempFragment = htmlFormFragment(inputsArray, submitId, formName);

  // scroll to top of page-content
  pageContent.scrollTop = 0;

  // Check if the first child is a form element
  if (firstChild && firstChild.tagName === "FORM") {
    // Replace the existing form with the new form
    pageContent.replaceChild(tempFragment, firstChild);
  } else {
    // Insert the new form before the first child element
    pageContent.insertBefore(tempFragment, firstChild);
  }
  // Add event listener to the "Cancel" button
  document.getElementById("cancel").addEventListener("click", function () {
    location.reload();
  });

  // preview image file on upload
  document.querySelectorAll('input[type="file"]').forEach((input) => {
    input.addEventListener("change", function (event) {
      const id = event.target.id;

      // replace preview_file by the new file
      const preview = document.getElementById("preview_" + id);
      preview.src = URL.createObjectURL(this.files[0]); 
    });
  });

  addTouchedClass();
}

liBlocs.forEach((li) => {
  li.addEventListener("click", async (event) => {
    const bloc = event.currentTarget.id;

    // Create a new FormData object
    const formData = new FormData();

    // Append the bloc to the formData
    formData.append("bloc", bloc);

    // Send request to PHP
    event.preventDefault();

    try {
      // essai
      const formBuilder = new FormData();
      formBuilder.append("bloc", bloc);
      const inputsArray = await getResponse(
        "ContentManager",
        "getForm",
        formBuilder
      );

      // Construct and replace the form
      constructAndReplaceForm(inputsArray, "create-button");

      document
        .querySelector("#create-button")
        .addEventListener("click", async function (event) {
          try {
            event.preventDefault(); // Prevent the default form submission

            // Access the form element using its name
            const form = document.forms["default-form"];

            if (!form.checkValidity()) {
              form.reportValidity();
              return;
            } else {
              // Create a new FormData object
              let formData = new FormData();

              // Append the page and bloc values to the formData
              formData.append("page", page);
              formData.append("bloc", bloc);
              formData = concatFormData(formData, form);

              await getResponse("ContentManager", "addContent", formData);
              location.reload();
            }
          } catch (error) {
            console.error("error:", error);
          }
        });
    } catch (error) {
      console.error("Error:", error);
    }
  });
});

document.addEventListener("click", async function (event) {
  if (
    event.target.classList.contains("update-button") ||
    event.target.classList.contains("delete-button") ||
    event.target.classList.contains("keep-out-button") ||
    event.target.classList.contains("chevron-up-icon") ||
    event.target.classList.contains("chevron-down-icon")
  ) {
    const form = new FormData();
    form.append("page", page);
    form.append("id", event.target.id);
    const blockId = event.target.id;

    if (event.target.classList.contains("update-button")) {
      try {
        const inputsArray = await getResponse(
          "ContentManager",
          "getForm",
          form
        );
        constructAndReplaceForm(inputsArray, "update-button", "update-form");

        // Submit event listener for the form
        document
          .querySelector("#update-button")
          .addEventListener("click", async function (event) {
            event.preventDefault(); // Prevent the default form submission

            // Access the form element using its name
            const updateForm = document.forms["update-form"];

            if (!updateForm.checkValidity()) {
              updateForm.reportValidity();
              return;
            } else {
              // Create a new FormData object
              let formData = new FormData();

              // Append the page and bloc values to the formData
              formData.append("page", page);
              formData.append("id", blockId);

              formData = concatFormData(formData, updateForm);
              try {
                await getResponse("ContentManager", "updateContent", formData);
                location.reload();
              } catch (error) {
                console.error("error:", error);
              }
            }
          });
        // location.reload();
      } catch (error) {
        console.error("error:", error);
      }
    } else if (event.target.classList.contains("delete-button")) {
      // open modal
      const modal = document.getElementById("modal");
      modal.style.display = "block";
      const modalContent = document.getElementById("modal-content");
      modalContent.innerHTML = await translate(
        "Are you sure you want to delete this bloc ?"
      );
      document.getElementById("modal-close").addEventListener("click", () => {
        modal.style.display = "none";
      });
      document
        .getElementById("modal-save")
        .addEventListener("click", async () => {
          try {
            await getResponse("ContentManager", "deleteContent", form);
            location.reload();
          } catch (error) {
            console.error("error:", error);
          }
        });
    } else if (event.target.classList.contains("keep-out-button")) {
      // get data-layout-id from event
      const layoutID = event.target.getAttribute("data-layout-id");
      form.append("layout", layoutID);
      try {
        await getResponse("ContentManager", "removeBlockFromLayout", form);
        location.reload();
      } catch (error) {
        console.error("error:", error);
      }
    } else if (event.target.classList.contains("chevron-up-icon")) {
      const layoutID = event.target.getAttribute("data-layout-id");
      form.append("layout", layoutID);
      try {
        await getResponse("ContentManager", "moveBlockUp", form);
        location.reload();
      } catch (error) {
        console.error("error:", error);
      }
    } else if (event.target.classList.contains("chevron-down-icon")) {
      const layoutID = event.target.getAttribute("data-layout-id");
      form.append("layout", layoutID);
      try {
        await getResponse("ContentManager", "moveBlockDown", form);
        location.reload();
      } catch (error) {
        console.error("error:", error);
      }
    }
  }
});

const dragStartHandler = (event) => {
  event.dataTransfer.setData("text/plain", event.target.id);
};

const dragOverHandler = (event) => {
  event.preventDefault();
};

const dropHandler = async (event) => {
  event.preventDefault();

  const droppedElementId = event.dataTransfer.getData("text/plain");
  const droppedElement = document.getElementById(droppedElementId);

  // Get the $contentArray['id'] of the dropped element
  const droppedElementIdValue = droppedElement.id;

  // Find the nearest parent element with the class 'drop-container'
  const dropContainer = event.target.closest(".drop-container");
  const layoutID = dropContainer.parentElement.id;

  if (dropContainer) {
    const contentDiv = dropContainer.nextElementSibling;
    if (contentDiv && contentDiv.classList.contains("content")) {
      contentDiv.appendChild(droppedElement);
    }
  } else {
    event.target.appendChild(droppedElement);
  }

  try {
    const form = new FormData();
    form.append("page", page);
    form.append("blockID", droppedElementIdValue);
    form.append("layoutID", layoutID);
    await getResponse("ContentManager", "addBlockToLayout", form);
    location.reload();
  } catch (error) {
    console.error("error:", error);
  }
  // Use the droppedElementIdValue as needed
};

const draggableElements = document.querySelectorAll(".draggable");
const dropContainers = document.querySelectorAll(".drop-container");

draggableElements.forEach((element) => {
  element.addEventListener("dragstart", dragStartHandler);
});

dropContainers.forEach((container) => {
  container.addEventListener("dragover", dragOverHandler);
  container.addEventListener("drop", dropHandler);
});
