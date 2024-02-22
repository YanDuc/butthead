<input type="hidden" id="page" value="<?= $page ?>">
<input type="hidden" id="parent" value="<?= $parent ?>">

<header id="page-header">
    <h1>
        <?php if ($parent) {
            echo $parent . ' / ';
        } ?>
        <?= $page; ?>
    </h1>
    <ul>
        <li><a href="#" class="button-link" onclick="removeURLParams('edit'); return false;">
                <?= _('Content') ?>
            </a></li>
    </ul>
</header>
<div>
    <form id="editPageForm">

        <?php if ($page !== 'root'): ?>
            <label for="pageName">URL:</label>
            <div style="display: flex; align-items: baseline; color: #4a4a4a;">
                <?php if ($parent) {
                    echo $parent . '/';
                } ?>
                <input type="text" id="url" name="url">
            </div>
        <?php else: ?>
            <input type="hidden" id="url" name="url" value="root">
        <?php endif; ?>

        <label for="pageName">
            <?= _("Page Name") ?>:
        </label>
        <input type="text" id="pageName" name="pageName" minlength="5" maxlength="80" required>

        <label for="description">
            <?= _("Description") ?>:
        </label>
        <textarea id="description" name="description" minlength="5" maxlength="160" required></textarea>

        <?php if ($page !== 'root'): ?>
            <label for="addToNav">
                <?= _("Add to Navigation") ?>
            </label>
            <input type="checkbox" id="addToNav" name="addToNav" checked="true">
        <?php else: ?>
            <input type="hidden" id="addToNav" name="addToNav" value="false">
        <?php endif; ?>

        <div class="actions-container">
            <input type="submit" value="<?= _('Edit Page') ?>">
        </div>
    </form>
    <div id="error"></div>
    <div id="response"></div>
</div>

<script src="js/edit_page_meta.js" type="module"></script>
<script>
    function removeURLParams(param) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.delete(param);
        window.location.search = urlParams.toString();
    }
</script>