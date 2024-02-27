<div id="footer-content"></div>

<script type="module">
    import { getResponse } from "./js/ajax_handler.js";
    import { htmlFormFragment, concatFormData } from "./js/form_builder.js";

    const footerContent = document.getElementById("footer-content");

    try {
        // update
        const updateForm = new FormData();
        updateForm.append("page", "bh-footer");
        updateForm.append("id", "bh-footer");
        const inputsArray = await getResponse(
            "ContentManager",
            "getForm",
            updateForm
        );
        const form = htmlFormFragment(inputsArray, 'update-button', 'update-form');
        footerContent.appendChild(form);

        document
            .querySelector("#update-button")
            .addEventListener("click", async function (event) {
                event.preventDefault(); // Prevent the default form submission

                const updateForm = document.forms["update-form"];
                let formData = new FormData();
                formData.append("page", "bh-footer");
                formData.append("id", "bh-footer");
                formData = concatFormData(formData, updateForm)

                try {
                    const res = await getResponse(
                        "ContentManager",
                        "updateContent",
                        formData
                    );
                    location.reload();
                } catch (error) {
                    console.error("updateContent error:", error);
                }
            });


    } catch (error) {
        // Create
        const formData = new FormData();
        formData.append("block", 'footer.html');

        // Send a POST request to handle_works-butt-head.php using fetch
        const inputsArray = await getResponse(
            "ContentManager",
            "getForm",
            formData
        );

        // Create the form and append it to the response element
        const form = htmlFormFragment(inputsArray, 'create-button', 'create-form');
        footerContent.appendChild(form);

        document
            .querySelector("#create-button")
            .addEventListener("click", async function (event) {
                event.preventDefault(); // Prevent the default form submission
                const form = document.forms["create-form"];
                // Create a new FormData object
                let formData = new FormData();

                // Append the page and block values to the formData
                formData.append("page", "bh-footer");
                formData.append("block", "footer.html");
                formData = concatFormData(formData, form);

                try {
                    await getResponse("ContentManager", "addContent", formData);
                    location.reload();
                } catch (error) {
                    console.error("addContent error:", error);
                }
            });
    }
</script>