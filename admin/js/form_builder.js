export const htmlFormFragment = (
  inputArray,
  submitId,
  formName = "default-form"
) => {
  const html = `<form enctype="multipart/form-data" name="${formName}" class="dynamic-form">
      ${inputArray.join("")}
      <div class="actions-container">
        <button type="button" class="button-link" id="cancel">Cancel</button>
        <input type="submit" class="button-link" id="${submitId}">
      </div>
      </form>`;

      return document.createRange().createContextualFragment(html);


};


/**
 * add form data after other form data
 * @param {FormData} formData
 * @param {FormData} formValues
 */
export const concatFormData = (formData, formValues) => {
    // Loop through the form elements and append their values to the formData
    formValues.querySelectorAll("input, select, textarea").forEach((element) => {
      if (element.type !== "file" && element.type !== "radio") {
        formData.append(element.name, element.value);
      } else if (element.type === "radio") {
        if (element.checked) {
          formData.append(element.name, element.value);
        }
      } else {
        // Handle file inputs separately by appending the file object
        const fileInput = element;
        if (fileInput.files.length > 0) {
          formData.append(element.name, fileInput.files[0]);
        }
      }
    });
    return formData;
  };