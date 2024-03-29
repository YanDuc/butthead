// handle event on li click
import { getResponse, translate } from "./ajax_handler.js";
import { addTouchedClass } from "./form_validation.js";
import { htmlFormFragment, concatFormData } from "./form_builder.js";

const currentPage = document.getElementById("page").value;
const parent = document.getElementById("parent").value;
const page = parent ? parent + "/" + currentPage : currentPage;
const liBlocs = document.querySelectorAll("li.block");
const liLayouts = document.querySelectorAll("li.layout");
const pageContent = document.getElementById("page-content");
const blockContent = document.querySelectorAll("section");

const layoutBlocs = document.querySelectorAll(".layout-blocks");
layoutBlocs.forEach((block) => {
  block.addEventListener("change", async (event) => {
    const block = event.target.value;
    const layoutID = event.target.id;
    const res = await createBlock(block);

    if (res.id) {
      const form = new FormData();
      form.append("page", page);
      form.append("blockID", res.id);
      form.append("layoutID", layoutID);
      await getResponse("ContentManager", "addBlockToLayout", form);
      location.reload();
    } else {
      location.reload();
    }

  });
});

blockContent.forEach((section) => {
  section.addEventListener("mouseenter", async (event) => {
      const actions = event.currentTarget.querySelector(".actions");
      actions.style.visibility = "visible";
  });

  section.addEventListener("mouseleave", async (event) => {
      const actions = event.currentTarget.querySelector(".actions");
      actions.style.visibility = "hidden";
  });
});

liLayouts.forEach((li) => {
  li.addEventListener("click", async (event) => {
    const layout = event.currentTarget.id;

    // Create a new FormData object
    const formData = new FormData();

    // Append the block to the formData
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

async function createBlock(block) {
  return new Promise(async (resolve, reject) => {    
    // Create a new FormData object
    const formData = new FormData();
  
    // Append the block to the formData
    formData.append("block", block);
  
    try {
      const formBuilder = new FormData();
      formBuilder.append("block", block);
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
  
              // Append the page and block values to the formData
              formData.append("page", page);
              formData.append("block", block);
              formData = concatFormData(formData, form);
  
              resolve(await getResponse("ContentManager", "addContent", formData));
            }
          } catch (error) {
            reject(error);
          }
        });
    } catch (error) {
      reject(error);
    }
  })
}

liBlocs.forEach((li) => {
  li.addEventListener("click", async (event) => {
    event.preventDefault();
    const block = event.currentTarget.id;
    try {
      const blockCreated = await createBlock(block);
      location.reload();
    } catch (error) {
      console.error("error:", error);
    }
  });
});

document.addEventListener("click", async function (event) {
  if (
    event.target.classList.contains("update-button") ||
    event.target.classList.contains("delete-button") ||
    event.target.classList.contains("copy-button") ||
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

              // Append the page and block values to the formData
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
        "Are you sure you want to delete this block ?"
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
    } else if (event.target.classList.contains("copy-button")) {
      await getResponse("ContentManager", "copy", form);
      location.reload();
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
  const blockID = droppedElement.id;
  const layoutID = event.currentTarget.id;

  try {
    const form = new FormData();
    form.append("page", page);
    form.append("blockID", blockID);
    form.append("layoutID", layoutID);
    await getResponse("ContentManager", "addBlockToLayout", form);
    location.reload();
  } catch (error) {
    console.error("error:", error);
  }
  // Use the droppedElementIdValue as needed
};

const draggableElements = document.querySelectorAll(".draggable");
const dropContainers = document.querySelectorAll(".layout");

draggableElements.forEach((element) => {
  element.addEventListener("dragstart", dragStartHandler);
});

dropContainers.forEach((container) => {
  container.addEventListener("dragover", dragOverHandler);
  container.addEventListener("drop", dropHandler);
});
