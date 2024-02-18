/**
 * Return response from a class method
 * @param {string} className
 * @param {string} methodName
 * @param {FormData} formData
 * @returns {Promise<any>}
 */
export async function getResponse(className, methodName, formData, constructorParams = []) {
  if (formData instanceof FormData) {
    formData.append("method", methodName);
    formData.append("class", className);
  } else {
    formData = new FormData();
    formData.append("method", methodName);
    formData.append("class", className);
  }
  const response = await fetch("handlers/ajax_handler.php", {
    method: "POST",
    body: formData
  });
  if (!response.ok) {
    throw new Error(await response.text());
  } else {
    const data = await response.json();
    return data;
  }
}

export async function translate(text, textVar) {
  const form = new FormData();
  form.append("text", text);
  if (textVar) {
    form.append("textVar", textVar);
  }
  try {
    const response = await fetch("handlers/translation.php", {
      method: "POST",
      body: form,
    });
    if (response && response.ok) {
      const data = await response.json(); // Await the JSON data
      return data.translation;
    }
  } catch (error) {
    return text
  }
}


