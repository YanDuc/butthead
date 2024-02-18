<form id="addUserForm">
    
    <label for="email">User email:</label>
    <input type="email" id="email" name="email">

    <input type="submit" value="Add User">
</form>

<div id="response"></div>
<div id="error"></div>

<script type="module">
    import { getResponse } from './js/ajax_handler.js';
    // Get the form and response elements
    const addUserForm = document.getElementById('addUserForm');
    const responseElement = document.getElementById('response');

    // Function to handle form submission
    addUserForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        // Get the form data
        const formData = new FormData(addUserForm);

        try {
            const res = await getResponse('UserManager', 'addUser', formData);
            responseElement.textContent = 'User created. Password : ' + res.password;
        } catch (error) {
            document.getElementById('error').textContent = error.message;
        }
    });
</script>