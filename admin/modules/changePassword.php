<form id="changePasswordForm">
    <label for="password"><?= _('New Password') ?>:</label>
    <input type="password" id="password" name="password">

    <label for="password2"><?= _('Re type Password') ?>:</label>
    <input type="password" id="password2" name="password2">

    <input type="submit" value="<?= _('Submit') ?>" name="Submit">
</form>

<div id="response"></div>
<div id="error"></div>

<script type="module">
    import { getResponse } from './js/ajax_handler.js';

    // Get the form and response elements
    const changePasswordForm = document.getElementById('changePasswordForm');
    const responseElement = document.getElementById('response');
    const errorElement = document.getElementById('error');

    // Function to handle form submission
    changePasswordForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        // Get the form data
        const formData = new FormData(changePasswordForm);
        
        // verify if passwords match
        if (formData.get('password') !== formData.get('password2')) {
            errorElement.innerHTML = 'Passwords do not match!';
        } else {
            // keep only one password in the form
            formData.delete('password2');
            try {
                const res = await getResponse('UserManager', 'changePassword', formData);
                responseElement.innerHTML = res.message;
            } catch (error) {
                document.getElementById('responseMessage').textContent = error.message;
            }
        }
    })
</script>