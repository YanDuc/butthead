<?php
include_once('classes/UserManager.php');
$UsersManager = new UserManager();
$users = $UsersManager->getUsersArray();
?>

<div id="modal">
    <div id="modal-content"></div>
    <div class="modal-footer">
        <button id="modal-close" class="modal-button"><?= _('Cancel') ?></button>
        <button id="modal-save" class="modal-button"><?= _('Confirm') ?></button>
    </div>
</div>
<a class="button-link" href="?page=addUser"><?= _('Add user') ?></a>
<div class="tab-container">
    <table class="user-table">
        <thead>
            <tr>
                <th><?= _('Email') ?></th>
                <th><?= _('Role') ?></th>
                <th class="user-actions"><?= _('Actions') ?></th>
            </tr>
        </thead>
        <tbody id="user-table-body">
            <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <?php echo $user['email']; ?>
                    </td>
                    <td>
                        <select class="admin-select" data-email="<?php echo $user['email']; ?>"
                            onchange="this.nextElementSibling.click()">
                            <option value="-" <?php echo $user['admin'] ? '' : 'selected'; ?>>-</option>
                            <option value="Admin" <?php echo $user['admin'] ? 'selected' : ''; ?>><?= _('Admin') ?></option>
                        </select>
                        <button class="hide-button" style="display: none;"></button>
                    </td>
                    <td class="user-actions">
                        <button class="permissions-button" data-email="<?php echo $user['email']; ?>"><?= _('Permission') ?></button>
                        <button class="delete-button" data-email="<?php echo $user['email']; ?>"><?= _('Delete') ?></button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div id="error" class="tac"></div>

<div class="permissions-tab" id="permissions-tab">
    <h2><?= _('Permissions') ?></h2>
    <div id="permissions-container">
        <!-- Permissions checkboxes will be dynamically added here -->
    </div>
</div>

<script type="module">

    import { getResponse, translate } from './js/ajax_handler.js';
    const errorContainer = document.getElementById('error');

    async function deleteUser(email) {
        // open modal
        const modal = document.getElementById('modal');
        modal.style.display = 'block';
        const modalContent = document.getElementById('modal-content');
        modalContent.innerHTML = await translate("Are you sure you want to delete %s ?", email)
        document.getElementById('modal-close').addEventListener('click', () => {
            modal.style.display = 'none';
        })
        document.getElementById('modal-save').addEventListener('click', async () => {
            const deleteForm = new FormData();
            deleteForm.append('email', email);
            try {
                const res = await getResponse('UserManager', 'deleteUser', deleteForm);
                if (res.success) {
                    location.reload();
                }
            } catch (error) {
                showError(error);
                modal.style.display = 'none';
            }
        })
    }

    let pages = []
    async function getPages() {
        try {
            const res = await getResponse('PageManager', 'getPages');
            const pageNames = [];

            function extract(obj, parentUrl = '') {
                for (const key in obj) {
                    const page = obj[key];
                    const url = parentUrl ? `${parentUrl}/${key}` : key;

                    if (page.pageName) {
                        pageNames.push({ pageName: page.pageName, url: url, unauthorizedUsers: page.unauthorizedUsers || [] });
                    }
                    if (page.subPages) {
                        extract(page.subPages, url);
                    }
                }
            }
            extract(res);
            pages = pageNames;
        } catch (error) {
            console.error(error);
        }
    }


    // Select all elements with the "permissions-button" class
    const permissionButtons = document.querySelectorAll('.permissions-button');
    const permissionsTab = document.getElementById("permissions-tab");
    const permissionsContainer = document.getElementById("permissions-container");

    // Add event listener to each button
    permissionButtons.forEach(button => {
        button.addEventListener('click', () => {
            const email = button.dataset.email;
            openPermissionsTab(email);
        });
    });
    async function openPermissionsTab(email) {
        await getPages();
        permissionsTab.style.display = "block";
        permissionsContainer.innerHTML = "";

        let user = document.createElement("h3");
        user.textContent = email;
        permissionsContainer.appendChild(user);

        for (let page of pages) {
            let isChecked = true;
            try {
                if (page.unauthorizedUsers && page.unauthorizedUsers.includes(email)) {
                    isChecked = false;
                }
            } catch (error) {
                console.error(error);
            }

            var label = document.createElement("label");
            var checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.checked = isChecked;
            label.appendChild(checkbox);
            label.appendChild(document.createTextNode(page.pageName));
            permissionsContainer.appendChild(label);

            // Add event listener to checkbox
            checkbox.addEventListener("click", function () {
                handleCheckboxClick(page.url, email, event.target.checked);
            });
        }
    }

    function showError(message) {
        errorContainer.textContent = message;
        setTimeout(() => {
            errorContainer.textContent = '';
        }, 5000);
    }

    async function handleCheckboxClick(pagePath, email, check) {
        // Perform any necessary actions based on the checkbox click
        try {
            const unauthorizedForm = new FormData();
            unauthorizedForm.append('email', email);
            unauthorizedForm.append('pagePath', pagePath);
            if (!check) {
                const res = await getResponse('PageManager', 'addUnauthorizedUsers', unauthorizedForm);
            }
            if (check) {
                const res = await getResponse('PageManager', 'removeUnauthorizedUsers', unauthorizedForm);
            }
            await getPages();
        } catch (error) {
            console.error(error);
        }

    }

    const deleteButtons = document.querySelectorAll('.delete-button');
    // Add event listener to each button
    deleteButtons.forEach(button => {
        button.addEventListener('click', () => {
            const email = button.dataset.email;
            deleteUser(email);
        });
    });

    async function changeRole(email) {
        const selectElement = document.querySelector(`select[data-email="${email}"]`);

        // get selected value
        const selectedValue = selectElement.value;
        const admin = selectedValue === 'Admin' ? true : false;
        const changeRoleForm = new FormData();
        changeRoleForm.append('email', email);
        changeRoleForm.append('admin', admin);

        try {
            const res = await getResponse('UserManager', 'changeRole', changeRoleForm);
        } catch (error) {
            showError(error);
            // keep old value
            selectElement.value = 'Admin';
        }
    }

    // Event listener for select element change
    const selectElements = document.querySelectorAll('.admin-select');
    selectElements.forEach(selectElement => {
        selectElement.addEventListener('change', function () {
            const email = this.getAttribute('data-email');
            changeRole(email);
        });
    });
</script>