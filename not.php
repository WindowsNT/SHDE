<?php

require_once "vendor/autoload.php";

if (array_key_exists("manifest",$_GET))
{
    header("Content-Type: text/json");
    ?>
    {
  "gcm_sender_id": "653317226796",
  "name": "Simple Push Demo",
  "short_name": "Push Demo",
  "start_url": "./not.php",
  "display": "standalone",
  "theme_color": "#2e3aa1",
  "background_color": "#ffffff"

}



<?php
    die;
}

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

$hout = '<link rel="manifest" href="not.php?manifest" />


<script>



function ArrayBufferToString(buffer) {
    return BinaryToString(String.fromCharCode.apply(null, Array.prototype.slice.apply(new Uint8Array(buffer))));
}

function StringToArrayBuffer(string) {
    return StringToUint8Array(string).buffer;
}

function BinaryToString(binary) {
    var error;

    try {
        return decodeURIComponent(escape(binary));
    } catch (_error) {
        error = _error;
        if (error instanceof URIError) {
            return binary;
        } else {
            throw error;
        }
    }
}

function StringToBinary(string) {
    var chars, code, i, isUCS2, len, _i;

    len = string.length;
    chars = [];
    isUCS2 = false;
    for (i = _i = 0; 0 <= len ? _i < len : _i > len; i = 0 <= len ? ++_i : --_i) {
        code = String.prototype.charCodeAt.call(string, i);
        if (code > 255) {
            isUCS2 = true;
            chars = null;
            break;
        } else {
            chars.push(code);
        }
    }
    if (isUCS2 === true) {
        return unescape(encodeURIComponent(string));
    } else {
        return String.fromCharCode.apply(null, Array.prototype.slice.apply(chars));
    }
}

function StringToUint8Array(string) {
    var binary, binLen, buffer, chars, i, _i;
    binary = StringToBinary(string);
    binLen = binary.length;
    buffer = new ArrayBuffer(binLen);
    chars  = new Uint8Array(buffer);
    for (i = _i = 0; 0 <= binLen ? _i < binLen : _i > binLen; i = 0 <= binLen ? ++_i : --_i) {
        chars[i] = String.prototype.charCodeAt.call(binary, i);
    }
    return chars;
}

function postsub(rr,sub)
{
//debugger;
            var ser = JSON.stringify(sub);
            var opt = sub.options;
            var key = opt.applicationServerKey;
            var skey = window.btoa(ArrayBufferToString(key));
      //      var key2 = StringToArrayBuffer(skey);

            var nurl = rr;
            $.ajax(
                {   
                url: nurl,
                type: "POST",
                data: { endpoint:sub.endpoint, key:skey,str:ser, pushvalue:Notification.permission, pushanswer:1 },
                success: function(ddd)
                    {
                    window.location = ddd;
                    }
                }
            );



}

function urlB64ToUint8Array(base64String) {
  const padding = \'=\'.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding)
    .replace(/\-/g, \'+\')
    .replace(/_/g, \'/\');

  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
}

function subscribeUser(rr,k) {
//debugger;
  if ("serviceWorker" in navigator) {
    navigator.serviceWorker.ready.then(function(reg) {

      reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlB64ToUint8Array(k),
      }).then(function(sub) {
        console.log("Endpoint URL: ", sub.endpoint);
        postsub(rr,sub);
      }).catch(function(e) {
           window.location = rr + "&pushvalue=error";
      });
    })
  }
}


function granted(rr,h,k)
{
    if ("serviceWorker" in navigator) {
        navigator.serviceWorker.register(h).then(function(reg) {
            console.log("Service Worker Registered!", reg);

            reg.pushManager.getSubscription().then(function(sub) {
            if (sub === null)
                {
            // Update UI to ask user to register for Push
            console.log("Not subscribed to push service!");
            subscribeUser(rr,k);
            }
            else
            {
            // We have a subscription, update the database
            console.log("Subscription object: ", sub);
            console.log("Endpoint URL: ",sub.endpoint);
            postsub(rr,sub);
            }
        });
    })
   .catch(function(err) {
    console.log("Service Worker registration failed: ", err);
  });
}
}

function AskNotification(q,rr,h,k)
{
//debugger;

if (Notification.permission === "granted")
    {
granted(rr,h,k);
}
else if (Notification.permission === "blocked" || Notification.permission === "denied")
    {
                var rrr = rr + "&pushvalue=" + Notification.permission;
                window.location = rrr;
    }
else
    {
    if (q == "")
{
          Notification.requestPermission(function(status) {

                if (status == "granted")
                    {
                    granted(rr,h,k);
                    return;
                    }
                var rrr = rr + "&pushvalue=" + status;
                window.location = rrr;
            });

return;
}


    bootbox.confirm(q, function(result)
        {
        if (!result)
                window.location = rr + "&pushvalue=cancel";

        if (result)
            {
            Notification.requestPermission(function(status) {
                window.location = rr + "&pushvalue=" + status;
            });
        }
});
    }
}

</script>
';


function NOT_Scripts()
{
    global $hout;
    echo $hout;
}

function NOT_Push($endpoint,$dkey,$authd,$sub,$pushpub,$pushpriv,$payload)
{

//    var_dump($payload);

    $auth = [
        'VAPID' => [
            'subject' => $sub, // can be a mailto: or your website address
            'publicKey' => $pushpub, // (recommended) uncompressed public key P-256 encoded in Base64-URL
            'privateKey' => $pushpriv, // (recommended) in fact the secret multiplier of the private key encoded in Base64-URL

        ],
    ];

    $su = Subscription::create([
                'endpoint' => $endpoint,
                'publicKey' => $dkey,
                'authToken' => $authd,
                'contentEncoding' => 'aesgcm',
            ]);



    $webPush = new WebPush($auth);


    $r = $webPush->sendOneNotification($su,$payload,true);
    return $r;
}

function NOT_PushMultiple($arz,$sub,$pushpub,$pushpriv,$payload)
{

    //    var_dump($payload);

    $auth = [
        'VAPID' => [
            'subject' => $sub, // can be a mailto: or your website address
            'publicKey' => $pushpub, // (recommended) uncompressed public key P-256 encoded in Base64-URL
            'privateKey' => $pushpriv, // (recommended) in fact the secret multiplier of the private key encoded in Base64-URL

        ],
    ];
    $webPush = new WebPush($auth);
    foreach ($arz as $ar)
    {
        $su = Subscription::create($ar);

        $r = $webPush->sendOneNotification($su,$payload);

    }
    $r = $webPush->flush();


    return $r;
}



function NOT_PushMultiple2($arz,$sub,$pushpub,$pushpriv,$payload)
{

    //    var_dump($payload);

    $auth = [
        'VAPID' => [
            'subject' => $sub, // can be a mailto: or your website address
            'publicKey' => $pushpub, // (recommended) uncompressed public key P-256 encoded in Base64-URL
            'privateKey' => $pushpriv, // (recommended) in fact the secret multiplier of the private key encoded in Base64-URL

        ],
    ];
    $webPush = new WebPush($auth);
    foreach ($arz as $ar)
    {
        $su = Subscription::create($ar);
        $webPush->queueNotification($su,$payload);

    }
    foreach ($webPush->flush() as $report) {
        $endpoint = $report->getRequest()->getUri()->__toString();
    
        if ($report->isSuccess()) {
         //   echo "[v] Message sent successfully for subscription {$endpoint}.";
         return 1;
        } else {
           //  echo "[x] Message failed to sent for subscription {$endpoint}: {$report->getReason()}";
           return 0;
        }
    }
    return 0; 
}




function Push($uid,$msg,$testonly = 0,$titlex = 0,$messagex = 0 ,$urlx = 0)
{
    global $siteroot;
    global $pushpub;
    global $pushpriv;
    global $lastRowID;
    global $u;
    $res = 0;
    $a = array();
    $qqp = QQ("SELECT * FROM PUSH WHERE UID = ?",array($uid));
    if (!$qqp)
        return 0;
    while($tr = $qqp->fetchArray())
    {
        $qe = $tr['PUSH'];
        if (strlen($qe) > 5)
        {
            if ($testonly == 1)
                return 1;

            $sub = json_decode($qe,true);
            $auth = $sub['keys']['auth'];
            $p256dh = $sub['keys']['p256dh'];

            $aa = array(      'endpoint' => $sub['endpoint'],
        'publicKey' => $p256dh,
        'authToken' => $auth,
        'contentEncoding' => 'aesgcm');
            array_push($a,$aa);
        }
    }

    if ($testonly == 1)
        return 0;
    try
        {
            if ($urlx !== 0)
            {            
 //               $lastRowID = 0;
   //             QQ("INSERT INTO PUSHENTRY (TITLE,DESCRIPTION,URL,ALLOWTYPE,ALLOWID) VALUES(?,?,?,?,?)",array($titlex,$messagex,$urlx,$ty,$id));
     //           if ($lastRowID > 0)
       //             $msg = sprintf("[%s] %s",$lastRowID,$msg);
            }
            $res = NOT_PushMultiple2($a,$siteroot,$pushpub,$pushpriv,$msg);
            return $res;         
        }
        catch(Exception $e)
        {
            return 0;
        } 
}


function PushMany($ids,$msg,$titlex = 0,$messagex = 0 ,$urlx = 0)
{
    $ids = array_unique($ids);
    global $siteroot;
    global $pushpub;
    global $pushpriv;
    global $lastRowID;

    $res = 0;
    $a = array();
    $qqp = QQ("SELECT * FROM PUSH");
    if (!$qqp)
        return 0;
    while($tr = $qqp->fetchArray())
    {
        if (!in_array($tr['UID'],$ids))
            continue;

        $qe = $tr['PUSH'];
        if (strlen($qe) > 5)
        {
            $sub = json_decode($qe,true);
            $auth = $sub['keys']['auth'];
            $p256dh = $sub['keys']['p256dh'];

            $aa = array(      'endpoint' => $sub['endpoint'],
        'publicKey' => $p256dh,
        'authToken' => $auth,
        'contentEncoding' => 'aesgcm');
            array_push($a,$aa);
        }
    }

    try
        {
            if ($urlx !== 0)
            {
//                $lastRowID = 0;
  //              QQ("INSERT INTO PUSHENTRY (TITLE,DESCRIPTION,URL,ALLOWTYPE,ALLOWID) VALUES(?,?,?,?,?)",array($titlex,$messagex,$urlx,$ty,0));
    //            if ($lastRowID > 0)
      //              $msg = sprintf("[%s] %s",$lastRowID,$msg);
            }

            $res = NOT_PushMultiple2($a,$siteroot,$pushpub,$pushpriv,$msg);
            return $res;         
        }
        catch(Exception $e)
        {
            return 0;
        } 
}


