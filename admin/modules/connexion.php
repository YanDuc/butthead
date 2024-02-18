<?php session_start(); ?>
<div class="form-container">
    <h2>
        <?= _('Login Form') ?>
    </h2>
    <form id="loginForm">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" placeholder="Enter your email" required>

        <label for="password">
            <?= _('Password') ?>:
        </label>
        <input type="password" id="password" name="password" placeholder="Enter your password" required>

        <div class="actions-container">
            <input type="submit" value="<?= _('Login') ?>">
        </div>
    </form>
    <div id="responseMessage"></div>
    <div id="error" class="tac"></div>
</div>

<script type="module">
    import { getResponse } from './js/ajax_handler.js';
    const form = document.getElementById('loginForm');
    form.addEventListener('submit', async function (e) {
        e.preventDefault(); // Prevent form submission
        var form = e.target;
        var formData = new FormData(form);
        if (!form.checkValidity()) {
            form.reportValidity();
        } else {
            try {
                const res = await getResponse('UserManager', 'login', formData);
                if (formData.get('email') == 'admin@admin.fr' && formData.get('password') == 'admin') {
                    location.href = '?page=addUser';
                } else {
                    location.reload();
                }
            } catch (error) {
                document.getElementById('error').textContent = error.message;

            }
        }
    });
</script>