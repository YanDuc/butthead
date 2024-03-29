<?php
if (!isset($_SESSION)) {
  session_start();
}
include_once 'includes/locale_setup.php';
?>
<!DOCTYPE html>
<html>

<head>
  <title>
    <?= _('Admin Panel') ?>
  </title>
  <link rel="stylesheet" href="styles/style.css">
  <link rel="stylesheet" href="styles/forms.css">
  <link rel="stylesheet" href="styles/connexion.css">
  <link rel="stylesheet" href="styles/manageUsers.css">
  <script type="module">
    import { addTouchedClass } from "./js/form_validation.js";
    // Call the addTouchedClass function after the DOM is loaded
    document.addEventListener("DOMContentLoaded", function () {
      addTouchedClass();
    });
  </script>
</head>

<body>
  <?php
  if (!isset($_SESSION['loggedIn']) || !$_SESSION['loggedIn']) {
    echo '<div class="login-container">';
    include 'modules/connexion.php';
    echo '</div>';
  } else {
    $isAdmin = $_SESSION['loggedIn']['admin']; ?>
    <div class="main-container">
      <?php include 'includes/sidebar.php'; ?>
      <div style="width: 100%;">
        <?php include 'includes/header.php'; ?>
        <main>
          <?php
          $page = !empty($_GET['page']) ? $_GET['page'] : '';
          $parent = !empty($_GET['parent']) ? $_GET['parent'] : null;
          switch ($page) {
            case 'parameters':
              if ($isAdmin) {
                include 'modules/users/manageUsers.php';
              } else {
                include 'modules/404.php';
              }
              break;
            case 'addUser':
              if ($isAdmin) {
                include 'modules/users/addUser.php';
              } else {
                include 'modules/404.php';
              }
              break;
            case 'addPage':
              include 'modules/addPage.php';
              break;
            case 'changePassword':
              include 'modules/changePassword.php';
              break;
            case '404':
              include 'modules/404.php';
              break;
            case 'header':
              include 'modules/header.php';
              break;
            case 'footer':
              include 'modules/footer.php';
              break;
            default:
              include 'modules/works-butt-head.php';
              break;
          }
  }
  ?>
      </main>
    </div>
  </div>
</body>

</html>