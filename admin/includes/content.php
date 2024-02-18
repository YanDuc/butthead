<?php
$page = $_GET['page'] ?? '';

switch ($page) {
  case 'addPage':
    include 'php/modules/addPage/addPage.html';
    break;
  case 'contact':
    include 'php/components/contact.php';
    break;
  default:
    include 'php/components/home.php';
    break;
}
?>