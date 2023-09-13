<?php

require_once "functions.php";
require_once "output.php";
PrintHeader();



$panels = 0;
$alimos2 = 0;
if (isset($alimos))
  $alimos2 = 1;

function SuperAdminSection()
{
  global $alimos2;
  ?>
  <div id="m_9" style="display:none;">
  <article class="message is-danger block">
  <div class="message-header">
    <p>Superadmin</p>
  </div>
  <div class="message-body">
    <button class="autobutton is-warning button block" href="users.php">Χρήστες</button>
    <button class="autobutton is-primary button block" href="apikeys.php">API Keys</button>
    <button class="autobutton is-danger button block" href="roles.php">Ρόλοι</button>
    <button class="autobutton is-success button block" href="organizations.php">Φορείς</button>
    <button class="autobutton is-primary button block" href="endpoints.php">Endpoints</button>
    <button class="autobutton is-link button block" href="orgchart.php?reload=1">Reload Οργανόγραμμα</button>
    <button class="autobutton is-danger button block" href="impersonate.php">Impersonation</button>
    <button class="autobutton is-link button block" href="update.php">SHDE Update</button>
    <?php
    if (isset($alimos2))
      echo '<button class="autobutton is-info button block" href="alimos.php">Λειτουργίες ΜΣΑ</button>';

    ?>
  </div>
 </article>
 </div>
<?php 
}

function AdminSection()
{
  ?>
  <div id="m_2" style="display:none;">
  <article class="message is-warning block">
  <div class="message-header">
    <p>Διαχειριστής</p>
  </div>
  <div class="message-body">
    <button class="autobutton is-danger button block" href="roles.php">Ρόλοι</button>
    <button class="autobutton is-primary button block" href="endpoints.php">Endpoints</button>
    <button class="autobutton is-success button block" href="backup.php">Backup</button>
    <button class="autobutton is-success button block" href="restore.php">Restore</button>
  </div>
 </article>
 </div>
<?php
}

function CanWriteDocument()
{
  global $u;
  $r = QQ("SELECT * FROM ROLES WHERE UID = ?",array($u->uid))->fetchArray();
  if ($r)
    return true;
  return false;

}

function UserSection()
{
  global $u,$CanHostOthers;
  if (!$u)
    return;
  ?>
  <div id="m_1" style="display:none;">
  <article class="message is-primary block">
  <div class="message-header">
    <p>Χρήστης</p>
  </div>
  <div class="message-body">
  <?php
    if (CanWriteDocument())
    {
      echo '<button class="autobutton is-primary button block" href="eggr.php">Τα έγγραφά μου</button> ';
      echo '<button class="autobutton is-warning button block" href="ab.php">Βιβλίο Διευθύνσεων</button> ';
      echo '<button class="autobutton is-warning button block" href="orgchart.php">Οργανόγραμμα</button> ';
    }
    if ($CanHostOthers)
    {
      echo '<button class="autobutton is-danger button block" href="applyoid.php">Αίτημα Δημιουργίας Φορέα</button>';
    }
    ?>
    </article>
 </div>
<?php
}



function SettingsSection()
{
  global $u;
  if (!$u)
    return;
  ?>
  <div id="m_8" style="display:none;">
  <article class="message is-secondary block">
  <div class="message-header">
    <p>Ρυθμίσεις</p>
  </div>
  <div class="message-body">
  <button class="autobutton is-link button block" href="options.php">Επιλογές</button>
    <button class="autobutton is-primary button block" href="notify.php">Ειδοποιήσεις</button>
    <button class="autobutton is-danger button block" href="bio.php">Βιομετρικό Κλειδί</button>
  </div>
 </article>
 </div>
<?php
}
?>

<?php
if ($u && $panels == 1)
{
?>
<div style="margin: 20px" class="tabs is-centered is-medium is-boxed is-toggle-rounded is-toggle">
    <ul>
      <?php
        echo '<li class="is-active mm mm1"><a href="javascript:tgl(\'.mm1\',m1);">Χρήστης</a></li>';
        if (count($u->epadmin) || count($u->fadmin))
            echo '<li class="mm mm2"><a href="javascript:tgl(\'.mm2\',m2);">Διαχειριστής</a></li>';
        echo '<li class="mm mm8"><a href="javascript:tgl(\'.mm8\',m8);">Ρυθμίσεις</a></li>';
        if ($u->superadmin)
            echo '<li class="mm mm9"><a href="javascript:tgl(\'.mm9\',m9);">Superadmin</a></li>';
        ?>
    </ul>
</div>

<?php
}
?>

<script>
  AutoBu();
  var m1;
  var m2;
  var m8;
  var m9;
  $(document).ready(function()
      {
      m1 = $('#m_1').html();
      m2 = $('#m_2').html();
      m8 = $('#m_8').html();
      m9 = $('#m_9').html();
      tgl('.mm1',m1);
      });
</script>

<?php

function FullSection()
{
  global $u;
  echo '<div class="columns">
  <div class="column">';
  
  UserSection();
  echo '
  </div>
  <div class="column">
  ';


  SettingsSection();
  echo '
  </div>';
  echo '</div>';

  echo '<div class="columns">
  ';

  if (count($u->epadmin) || count($u->fadmin))
    {
      echo '<div class="column">';
      AdminSection();
      echo '
      </div>
      ';
    }    
  if ($u->superadmin)
    {
      echo '<div class="column">';
      SuperAdminSection();
    echo '
    </div>';
    }
    echo '</div>';
    
    echo '<script>
  $("#m_1").show();
  $("#m_2").show();
  $("#m_8").show();
  $("#m_9").show();
  </script>';
}

if (array_key_exists("resetshde",$req))
{
  $q1 = QQ("SELECT * FROM ORGANIZATIONS");
  while($r1 = $q1->fetchArray())
  {
    $a = UserAccessOID($r1['ID'],$u->uid);
    if ($a == 0)
      continue;
    $loge = sprintf("shde_login_%s",$r1['ID']);
    unset($_SESSION[$loge]);
  }
  redirect("index.php");
}  


 if ($u)
 {

  // Authenticate SHDE
  $q1 = QQ("SELECT * FROM ORGANIZATIONS");
  while($r1 = $q1->fetchArray())
  {
    $a = UserAccessOID($r1['ID'],$u->uid);
    if ($a == 0)
      continue;
    
    if ($r1['SHDECODE'] && $r1['SHDECLIENT'] && strlen($r1['SHDECODE'] && strlen($r1['SHDECLIENT'])))
    {
      $loge = sprintf("shde_login_%s",$r1['ID']);
      if (!array_key_exists($loge,$_SESSION))
      {
        redirect(sprintf("shdeauth.php?oid=%s&ret=index.php",$r1['ID']));
        die;
      }
      else
      {
        if (!array_key_exists("AccessToken",$_SESSION[$loge]))
          printf("ΚΣΗΔΕ Login στο Φορέα %s: αποτυχία <a href=\"index.php?resetshde=1\">Reload</a><br><br>",$r1['NAME']);
          else
          printf("ΚΣΗΔΕ Login στο Φορέα %s: Access Token %s <a href=\"index.php?resetshde=1\">Reload</a><br><br>",$r1['NAME'],$_SESSION[$loge]["AccessToken"] != '' ? 'Ενεργό': 'Αποτυχία');
      }
    }
  }
  if ($panels == 0)
    FullSection();
  else
  {
  UserSection();
  SettingsSection();
  if (count($u->epadmin) || count($u->fadmin))
    AdminSection();
  if ($u->superadmin)
    SuperAdminSection();
  }

 ?>

<?php
 }
 
 if (!$u)
 {
 ?>
    
 Το <b>Σύστημα Ηλεκτρονικής Διακίνησης Εγγράφων</b> του Μουσικού Σχολείου Αλίμου είναι <a href="https://www.msa-apps.com/13381.pdf" target="_blank">συνδεδεμένο</a>  με το <a href="https://support.mindigital-shde.gr/" target="_blank">ΚΣΗΔΕ</a> και <a href="https://www.msa-apps.com/award.php" target="_blank"><b>βραβευμένο</b></a> από το Υπουργείο Ψηφιακής Διακυβέρνησης.
 <div class="columns">
  <div class="column">
 <br><br>Χαρακτηριστικά:<hr>
<li>Σύνδεση με SQLite3/MySQL</li>
<li>Login/Logout με Taxis/Βιομετρικό Login/ΠΣΔ</li>
<li>Απεριόριστοι Φορείς</li>
<li>Απεριόριστα EndPoints</li>
<li>Απεριόριστοι χρήστες</li>
<li>Απεριόριστα έγγραφα</li>
<li>Διαβαθμίσεις μέχρι άκρως απόρρητο</li>
<li>Αυτόματο πρωτόκολλο μέσω διεπαφής</li>
<li>Αποστολείς, Κοινοποιήσεις, Κρυφές Κοινοποιήσεις, Εσωτερική Διανομή</li>
<li>Ρόλοι χρηστών ανά φορέα/ανά endpoint</li>
<li>Push Notifications</li>
<li>Διεπαφή με το ΥΨηΔ ΚΣΗΔΕ σε δοκιμαστικό και παραγωγικό περιβάλλον</li>
<li>Κρυπτογράφηση Διαβαθμισμένων Εγγράφων</li>
<li>Ψηφιακές Υπογραφές</li>
<li>Πολλαπλοί υπογράφοντες</li>
<li>API Keys για διαλειτουργικότητα με άλλα συστήματα</li>
<li>Κανόνες regex εισερχομένων</li>
<li>Δυνατότητα αντιστοίχισης εικονικού email σε endpoint</li>
<li>Κρυπτογραφημένα έγγραφα</li>
<li>Θυρίδες για κάθε χρήστη</li>
<li>Συμβατότητα με Δι@ύγεια</li>
<br><br>
Ο κώδικας του SHDE είναι γραμμένος σε PHP και είναι διαθέσιμος με MIT License στο <a href="https://github.com/WindowsNT/shde" target="_blank"><b>GitHub</b></a>.

  </div>

  <div class="column">
  <br><br>Video Demo:<hr>
  <iframe width="737" height="415" src="https://www.youtube.com/embed/a-8eXiRxMDY" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
  </div>
</div>


 </div>
 <?php
 }



?>



</div>