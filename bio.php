<?php
$in_bio = 1;
require_once "functions.php";

if (array_key_exists("delete",$_GET) && $u)
{
    if ($u->superadmin)
        QQ("DELETE FROM BIO_INFO WHERE ID = ?",array($req['delete']));
    else
        QQ("DELETE FROM BIO_INFO WHERE UID = ? AND ID = ?",array($u->uid,$req['delete']));
    redirect("bio.php");
    die;
}

$infail = 0;
if (array_key_exists("shde_needbio",$_SESSION) && $_SESSION['shde_needbio'] == 1 && $u)
    $infail = 1;

if (array_key_exists("shde_needbio",$_SESSION) && $_SESSION['shde_needbio'] == 1 && array_key_exists("release",$req))
{
    $_SESSION['shde_needbio'] = 2;
    redirect("index.php");
}



$fn = "";
if (array_key_exists("fn",$req))     $fn = $req['fn'];
$formats = array();
$formats[] = 'android-key';
$formats[] = 'android-safetynet';
$formats[] = 'apple';
$formats[] = 'fido-u2f';
$formats[] = 'packed';
$formats[] = 'tpm';
require_once "vendor/autoload.php";
$WebAuthn = new lbuchs\WebAuthn\WebAuthn($title, $sitedomain,$formats);


$id = 0;
$name = "";
$dname = "";
if ($u)
    {
        $id = $u->uid;
        $name = sprintf("%s %s",$u->lastname,$u->firstname);
        $dname = $name;
    }

if ($fn === 'getCreateArgs')
 {
    $crossPlatformAttachment  = null;
    $createArgs = $WebAuthn->getCreateArgs($id, $name, $dname);
    header('Content-Type: application/json');
    print(json_encode($createArgs));
    // save challange to session. you have to deliver it to processGet later.
    $_SESSION['challenge'] = serialize($WebAuthn->getChallenge());
    die;
}

if ($fn === 'getGetArgs') 
{    
    $ids = array();
    // load registrations from session stored there by processCreate.
    // normaly you have to load the credential Id's for a username
    // from the database.
    $ids = array();
    $q = QQ("SELECT * FROM BIO_INFO");
    while($r = $q->fetchArray())
    {
        $ids[] = base64_decode($r['T1']);
    }

    $getArgs = $WebAuthn->getGetArgs($ids, 20);
    header('Content-Type: application/json');
    print(json_encode($getArgs));

    // save challange to session. you have to deliver it to processGet later.
    $_SESSION['challenge'] = serialize($WebAuthn->getChallenge());
    die;
}




if ($fn === 'processCreate')
{
    $post = trim(file_get_contents('php://input'));
    if ($post) {
        $post = json_decode($post);
    }
    $clientDataJSON = base64_decode($post->clientDataJSON);
    $attestationObject = base64_decode($post->attestationObject);
    $challenge = unserialize($_SESSION['challenge']);

    // processCreate returns data to be stored for future logins.
    // in this example we store it in the php session.
    // Normaly you have to store the data in a database connected
    // with the user name.
    $data = $WebAuthn->processCreate($clientDataJSON, $attestationObject, $challenge, false, true, false);

    // add user infos
    $data->userId = $id;
    $data->userName = $name;
    $data->userDisplayName = $dname;

    // Save $data
    QQ("INSERT INTO BIO_INFO (UID,T1,T2) VALUES(?,?,?)",array($u->uid,base64_encode($data->credentialId),$data->credentialPublicKey));
    $msg = 'Η δημιουργία βιομετρικού login ολοκληρώθηκε.';
    if ($data->rootValid === false) {
        $msg = 'Registration ok, but certificate does not match any of the selected root ca.';
    }

    $return = new stdClass();
    $return->success = true;
    $return->msg = $msg;

    header('Content-Type: application/json');
//    print_r($data);
    print(json_encode($return));
    die;
}


if ($fn === 'processGet') 
{
    $post = trim(file_get_contents('php://input'));
    if ($post) {
        $post = json_decode($post);
    }

    $clientDataJSON = base64_decode($post->clientDataJSON);
    $authenticatorData = base64_decode($post->authenticatorData);
    $signature = base64_decode($post->signature);
    $userHandle = base64_decode($post->userHandle);
    $id = base64_decode($post->id);
    $challenge = unserialize($_SESSION['challenge']);
    $credentialPublicKey = null;

    // looking up correspondending public key of the credential id
    // you should also validate that only ids of the given user name
    // are taken for the login.
/*    if (is_array($_SESSION['registrations'])) {
        foreach ($_SESSION['registrations'] as $reg) {
            if ($reg->credentialId === $id) {
                $credentialPublicKey = $reg->credentialPublicKey;
                break;
            }
        }
    }
*/
    $q = QQ("SELECT * FROM BIO_INFO");
    $whatrow = null;
    while($r = $q->fetchArray())
    {
        $cid = base64_decode($r['T1']);
        if ($cid === $id) 
        {
            $credentialPublicKey = $r['T2'];
            $whatrow = $r;
            break;
        }
    }



    if ($credentialPublicKey === null) {
        throw new Exception('Public Key for credential ID not found!');
    }

    // if we have resident key, we have to verify that the userHandle is the provided userId at registration
    $requireResidentKey = 0;
    if ($requireResidentKey && $userHandle !== hex2bin($reg->userId)) {
        throw new \Exception('userId doesnt match (is ' . bin2hex($userHandle) . ' but expect ' . $reg->userId . ')');
    }

    // process the get request. throws WebAuthnException if it fails
    $WebAuthn->processGet($clientDataJSON, $authenticatorData, $signature, $credentialPublicKey, $challenge, null);

    $return = new stdClass();
    $return->success = true;

    // whatrow contains the login row 
    $r1 = QQ("SELECT * FROM USERS WHERE ID = ?",array($whatrow['UID']))->fetchArray();
    if ($r1)
    {
        $_SESSION['shde_bio'] = $whatrow;
        $_SESSION['shde_username'] = $r1['USERNAME'];
        $_SESSION['shde_firstname'] = $r1['FIRSTNAME'];
        $_SESSION['shde_lastname'] = $r1['LASTNAME'];
        $_SESSION['shde_title'] = $r1['TITLE'];
    }
        
    header('Content-Type: application/json');
    print(json_encode($return));
    die;
}

// Show

require_once "output.php";
echo '<div id="content" style="margin:20px;"><script src="cbor.js"></script><script src="base64_2.js"></script>';

?>

<script>


function recursiveBase64StrToArrayBuffer(obj) 
{
    let prefix = '=?BINARY?B?';
    let suffix = '?=';
    if (typeof obj === 'object') {
        for (let key in obj) {
            if (typeof obj[key] === 'string') {
                let str = obj[key];
                if (str.substring(0, prefix.length) === prefix && str.substring(str.length - suffix.length) === suffix) {
                    str = str.substring(prefix.length, str.length - suffix.length);

                    let binary_string = window.atob(str);
                    let len = binary_string.length;
                    let bytes = new Uint8Array(len);
                    for (let i = 0; i < len; i++)        {
                        bytes[i] = binary_string.charCodeAt(i);
                    }
                    obj[key] = bytes.buffer;
                }
            } else {
                recursiveBase64StrToArrayBuffer(obj[key]);
            }
        }
    }
}

function arrayBufferToBase64(buffer) 
{
    let binary = '';
    let bytes = new Uint8Array(buffer);
    let len = bytes.byteLength;
    for (let i = 0; i < len; i++) 
    {
        binary += String.fromCharCode( bytes[ i ] );
    }
    return window.btoa(binary);
}



function newregistration() 
{

    // get default args
    window.fetch('bio.php?fn=getCreateArgs', {method:'GET',cache:'no-cache'}).then(function(response) {
        return response.json();

    // convert base64 to arraybuffer
    }).then(function(json) {

    // error handling
    if (json.success === false) {
        throw new Error(json.msg);
    }

    // replace binary base64 data with ArrayBuffer. a other way to do this
    // is the reviver function of JSON.parse()
    recursiveBase64StrToArrayBuffer(json);
    return json;

   // create credentials
}).then(function(createCredentialArgs) {
    console.log(createCredentialArgs);
    return navigator.credentials.create(createCredentialArgs);

    // convert to base64
}).then(function(cred) {
    return {
        clientDataJSON: cred.response.clientDataJSON  ? arrayBufferToBase64(cred.response.clientDataJSON) : null,
        attestationObject: cred.response.attestationObject ? arrayBufferToBase64(cred.response.attestationObject) : null
    };

    // transfer to server
}).then(JSON.stringify).then(function(AuthenticatorAttestationResponse) {
    return window.fetch('bio.php?fn=processCreate', {method:'POST', body: AuthenticatorAttestationResponse, cache:'no-cache'});

    // convert to JSON
}).then(function(response) {
 /*   response.text().then((text) => {
        console.log(text);
    });*/
    return response.json();

    // analyze response
}).then(function(json) {
//    debugger;
   if (json.success) {
//       reloadServerPreview();
       window.alert(json.msg || 'Successful.');
       window.location = "bio.php";
   } else {
       throw new Error(json.msg);
   }

   // catch errors
}).catch(function(err) {
//    reloadServerPreview();
    window.alert(err.message || 'Unknown error occured');
});
}

function checkregistration() 
{
    if (!window.fetch || !navigator.credentials || !navigator.credentials.create) {
        window.alert('Browser not supported.');
        return;
    }

    // get default args
    window.fetch('bio.php?fn=getGetArgs', {method:'GET',cache:'no-cache'}).then(function(response) {
//        response.text().then((text) => {
 //       console.log(text);
  //  });
        return response.json();

        // convert base64 to arraybuffer
    }).then(function(json) {

        // error handling
        if (json.success === false) {
            throw new Error(json.msg);
        }

        // replace binary base64 data with ArrayBuffer. a other way to do this
        // is the reviver function of JSON.parse()
        recursiveBase64StrToArrayBuffer(json);
        return json;

    // create credentials
    }).then(function(getCredentialArgs) {
        return navigator.credentials.get(getCredentialArgs);

        // convert to base64
    }).then(function(cred) {
        console.log(cred);
        return {
            id: cred.rawId ? arrayBufferToBase64(cred.rawId) : null,
            clientDataJSON: cred.response.clientDataJSON  ? arrayBufferToBase64(cred.response.clientDataJSON) : null,
            authenticatorData: cred.response.authenticatorData ? arrayBufferToBase64(cred.response.authenticatorData) : null,
            signature: cred.response.signature ? arrayBufferToBase64(cred.response.signature) : null,
            userHandle: cred.response.userHandle ? arrayBufferToBase64(cred.response.userHandle) : null
        };

        // transfer to server
    }).then(JSON.stringify).then(function(AuthenticatorAttestationResponse) {
        return window.fetch('bio.php?fn=processGet', {method:'POST', body: AuthenticatorAttestationResponse, cache:'no-cache'});

        // convert to json
    }).then(function(response) {
        return response.json();

        // analyze response
    }).then(function(json) {
    if (json.success) {
//        window.alert(json.msg || 'login success');
        window.location = "index.php";
    } else {
        throw new Error(json.msg);
    }

    // catch errors
    }).catch(function(err) {
//        window.alert('Το βιομετρικό login απέτυχε! Πρέπει πρώτα να μπείτε με το κανονικό login και να πατήσετε το κουμπί "Biometric Login" για να ορίσετε το βιομετρικό login!');
//        window.alert(err.message || 'unknown error occured');
        window.location = "index.php";
    });
}


</script>

<?php

if (array_key_exists("login",$_GET))
{
    ?>
    <script>
        checkregistration();
    </script>
    <?php
    die;
}

if ($u && $u->uid > 0)
{

    if (!$infail)
        {
            PrintHeader('index.php','&nbsp; <button class="is-primary button block" onclick="newregistration();">Δημιουργία Βιομετρικού Login</button>');
    }
        $cnt = 0;
    if (!$infail)
        printf("Με τα βιομετρικά login μπορείτε να κάνετε είσοδο με το δακτυλικό αποτύπωμα ή την αναγνώριση προσώπου σας. Αν έχετε κινητό τηλέφωνο με δακτυλικό αποτύπωμα, πατώντας το κουμπί Δημιουργία μπορείτε να επιλέξετε την οθόνη κλειδώματος ως βιομετρικό login.<br><br><b>Το βιομετρικό κλειδί είναι απαραίτητο αν δημιουργείτε διαβαθμισμένα έγγραφα.</b>");
    $q = QQ("SELECT * FROM BIO_INFO WHERE UID = ? ",array($u->uid));
    while($r = $q->fetchArray())
    {
        if ($cnt == 0 && !$infail)
            printf("<br><br>Λίστα βιομετρικών login<hr>");
        $cnt++;
        if (!$infail)
            printf('<li>Βιομετρικό login [%s] &mdash; <button class="button is-small is-warning autobutton" href="bio.php?delete=%s">Διαγραφή</button></li>',$r['T1'],$r['ID']);
    }
    $cnt = 0;
    if ($u->superadmin && !$infail)
    {
        $q = QQ("SELECT * FROM BIO_INFO");
        $cnt = 0;
        while($r = $q->fetchArray())
        {
        if ($cnt == 0)
            printf("<br><br>Λίστα όλων των βιομετρικών login<hr>");
        $cnt++;           
                $tr = UserRow($r['UID']);
        printf('<li>Βιομετρικό login [%s %s] [%s] &mdash; <button class="button is-small is-danger autobutton" href="bio.php?delete=%s">Διαγραφή</button></li>',$tr['LASTNAME'],$tr['FIRSTNAME'],$r['T1'],$r['ID']);
        }
    }
}


if ($infail)
{
    $_SESSION['shde_needbio'] = 2;
    redirect("index.php");
/*    printf('<br><br>Είστε διαβαθμισμένος χρήστης. Για λόγους ασφαλείας μπορείτε να μπαίνετε στο ΣΗΔΕ <b>μόνο με βιομετρικό login</b> εκτός και αν επεξεργαστείτε μόνο αδιαβάθμιτα έγγραφα. <br><br>');
    $q = QQ("SELECT * FROM BIO_INFO WHERE UID = ?",array($u->uid))->fetchArray();
    if (!$q)
        printf('<button class="is-primary button is-small block" onclick="newregistration();">Δημιουργία Βιομετρικού Login</button> ');
        printf('<button class="autobutton button is-small is-warning" href="bio.php?release=1">Συνέχεια χωρίς διαβάθμιση</button> ');
        printf('<button class="autobutton button is-small is-danger" href="logout.php?return=index.php">Logout</button>');
*/

}