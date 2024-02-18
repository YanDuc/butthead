<header id="main-header">
  <ul class="manage-menu">
    <?php if ($_SESSION['loggedIn']['admin']): ?>
      <li>
      <a href="?page=parameters">
        <img class="icon" src="assets/icons/dashicons--admin-generic.svg" width="30" height="30"
          alt="<?= _('Edit parameters') ?>" />
      </a>
    </li>
    <?php endif; ?>
    <li id="build"><?= _('Build') ?></li>
    </ul>
  <nav>
    <ul>
      <li>
        <select id="languageSelect">
          <option value="fr_FR" <?php if ($_SESSION['loggedIn']['lang'] === 'fr_FR')
            echo 'selected'; ?>>
            <?= _('French') ?>
          </option>
          <option value="en_US" <?php if ($_SESSION['loggedIn']['lang'] === 'en_US')
            echo 'selected'; ?>>
            <?= _('English') ?>
          </option>
        </select>
      </li>
      <li><a href="#" onclick="changePage('changePassword'); return false;">
          <?= _('Change Password') ?>
        </a></li>
      <li id="disconnect">
        <?= _('Disconnect') ?>
      </li>
    </ul>
  </nav>
</header>

<script type="module">
  import { getResponse } from './js/ajax_handler.js';
  document.getElementById('disconnect').addEventListener('click', async () => {
    try {
      const res = await getResponse('UserManager', 'logout', new FormData());
      location.reload();
    } catch (error) {
      console.error('Error:', error);
    }
  })
  function changePage(page) {
    location.assign('?page=' + page);
  }

  // Change locale
  document.getElementById('languageSelect').addEventListener('change', async () => {
    const form = new FormData();
    form.append('locale', document.getElementById('languageSelect').value);
    try {
      const res = await getResponse('Parameters', 'changeLocale', form);
      location.reload();
    } catch (error) {
      console.error('Error:', error);
    }
  });

  document.getElementById('build').addEventListener('click', async () => {
    const res = await getResponse('Builder', 'build', new FormData());
  });
</script>