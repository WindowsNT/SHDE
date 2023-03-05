<?php

$bs4 = 1;
if (array_key_exists("pushscript",$_GET))
{
?>
'use strict';
<?php
    printf("var url = 'https://www.msa-apps.com/shde';\r\n");
?>
self.addEventListener('push', function(event) {
  console.log('[Service Worker] Push Received.');
  console.log(`[Service Worker] Push had this data: "${event.data.text()}"`);
  const title = schname;
  const options = {
    body: event.data.text(),
    icon: 'admin.png',
    badge: 'admin.png'

  };
  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function(event) {
  console.log('[Service Worker] Notification click Received.');

  event.notification.close();

  event.waitUntil(
    clients.openWindow(url)
  );
});

<?php

    die;
}

require_once "functions.php";
require_once "not.php";
if (!$u)
    diez();

if (array_key_exists("delete",$req))
{
    QQ("DELETE FROM PUSH WHERE UID=? AND ID=?",array($u->uid,$req['delete']));
}


if (array_key_exists("pushanswer",$req))
{
    if ($req['pushvalue'] == "granted")
    {
        $j = $req['str'];
        QQ("INSERT INTO PUSH (UID,PUSH) VALUES(?,?)",array($u->uid,$j));
        printf('notify.php');
    }
    else
    {
        // Normal redirect...
        redirect(sprintf('notify.php'));
    }
    die; // OK

}


require "output.php";


NOT_Scripts();

if (array_key_exists("test",$req))
{

    Push($u->uid,"Αυτό είναι ένα δείγμα ειδοποίησης.",0,"Καλώς ορίσατε στις ειδοποιήσεις.","Έχετε ενεργοποιήσει επιτυχώς τις ειδοποιήσεις.",$siteroot."/notify.php");
    redirect("notify.php");
    die;
}


?>

<script>


    var curser = "";
    var sub;
    function deletepush(id)
    {
        sub.unsubscribe();
        window.location = "notify.php?delete=" + id;
    }

    $(document).ready(
        function()
        {
            $('#enable').show();
            if ('serviceWorker' in navigator && 'PushManager' in window) {
                navigator.serviceWorker.register('<?= $not_js ?>')
                .then(function(swReg) {
                    sw = swReg;
                    swReg.pushManager.getSubscription()
                        .then(function(subscription) {
                            curser = JSON.stringify(subscription);
                            sub = subscription;
                            $('.divs').each(
                                function(index)
                                {
                                    var te = JSON.stringify($(this).data("text"));
                                    if (te == curser)
                                    {
                                        $(this).show();
                                        $('#enable').hide();
                                    }
                                }
                                );


                        });
                })
            } else {
            }

        });


</script>

<?php

PrintHeader('index.php');
echo '<div class="content">';


{
    $q = QQ("SELECT * FROM PUSH WHERE UID = ?",array($u->uid));
    $found = 0;
    while($r = $q->fetchArray())
    {
        // Check this one
        $qe = $r['PUSH'];
        printf("<div class=\"divs\" id=\"n%s\" style=\"display:none;\" data-text='%s'>",$r['ID'],$qe);
        printf('Οι ενημερώσεις ειναι ενεργοποιημένες μόνο για αυτόν τον browser.<br><br><button class="block autobutton button is-primary" href="notify.php?test=1">Δοκιμή</button> <button class="autobutton button is-warning" href="javascript:deletepush(%s)">Απενεργοποίηση ειδοποιήσεων</button>',$r['ID']);
        printf("</div>\r\n");
        $found = 1;
    }

?>

<script>

    var pub = '<?= $pushpub ?>';
    var ru = '<?= $not_answer ?>';

    function askpush()
    {
        ha = '<?= $not_js ?>';
        AskNotification("",ru,ha,pub);
    }
</script>


<div id="enable">
<button class="autobutton button is-primary" href="javascript:askpush()">Ενεργοποίηση ειδοποιήσεων</button>
</div>
<?php
    die;
}



