<form id="addPageForm">
    
    <label for="pageName">Page Name:</label>
    <input type="text" id="pageName" name="pageName">
    
    <label for="description">Description:</label>
    <textarea id="description" name="description"></textarea>
    
    <input type="hidden" name="parent" value="<?= $parent ?>">

    <input type="submit" value="Add Page">
</form>

<div id="response"></div>

<script type="module">
    import { getResponse } from './js/ajax_handler.js';
    // Get the form and response elements
    const addPageForm = document.getElementById('addPageForm');
    const responseElement = document.getElementById('response');

    // Function to handle form submission
    addPageForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        // Get the form data
        const formData = new FormData(addPageForm);

        try {
            const res = await getResponse('PageManager', 'add', formData);
            window.location.href = `?page=${res.page}&parent=${res.parent}`
        } catch (error) {
            document.getElementById('response').textContent = error.message;
        }
    });
</script>