<form id="addPageForm">

    <label for="pageName">
        <?= _('Title') ?>:
    </label>
    <input type="text" id="pageName" name="pageName" minlength="5" maxlength="80" required>

    <label for="description">
        <?= _('Description') ?>:
    </label>
    <textarea id="description" name="description" minlength="5" maxlength="80" required></textarea>

    <label for="addToNav">
        <?= _("Add to Navigation") ?>
    </label>
    <input type="checkbox" id="addToNav" name="addToNav" checked="true">

    <input type="hidden" name="parent" value="<?= $parent ?>">

    <div class="actions-container">
        <input type="submit" value="Add Page">
    </div>
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
        const formData = new FormData();
        formData.set('pageName', addPageForm.elements['pageName'].value)
        formData.set('description', addPageForm.elements['description'].value)
        formData.set('addToNav', addPageForm.elements['addToNav'].checked)
        formData.set('parent', addPageForm.elements['parent'].value)

        try {
            const res = await getResponse('PageManager', 'add', formData);
            window.location.href = `?page=${res.page}&parent=${res.parent}`
        } catch (error) {
            document.getElementById('response').textContent = error.message;
        }
    });
</script>