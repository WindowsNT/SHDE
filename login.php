<?php

require_once "functions.php";
require_once "output.php";

$ret ="index.php";
if (array_key_exists("return",$req))
    $ret = $req['return'];

$_SESSION['shde_hide_comments'] = 1;
$_SESSION['shde_hide_att'] = 1;
$_SESSION['shde_hide_class'] = 1;
$_SESSION['shde_hide_priority'] = 0;
$_SESSION['shde_hide_sxetika'] = 1;

if ($req['type'] == "bio")
    {
        redirect(sprintf("bio.php?login=%s",$ret));
        die;
    }

if (array_key_exists("l",$req) && $login_demo)
{
    if ($req['l'] == 1)
    {
        $_SESSION['shde_username'] = "114789033";
        $_SESSION['shde_firstname'] = "Μιχαήλ";
        $_SESSION['shde_lastname'] = "Χουρδάκης";
        $_SESSION['shde_title'] = "Εκπαιδευτικός";
        redirect($ret);
    }
    if ($req['l'] == 2)
    {
        $_SESSION['shde_username'] = "123456789";
        $_SESSION['shde_firstname'] = "Ευάγγελος";
        $_SESSION['shde_lastname'] = "Μασούρης";
        $_SESSION['shde_title'] = "Διευθυντής";
        redirect($ret);
    }
    if ($req['l'] == 3)
    {
        $_SESSION['shde_username'] = "000111222";
        $_SESSION['shde_firstname'] = "Jack";
        $_SESSION['shde_lastname'] = "Daniels";
        $_SESSION['shde_title'] = "Whatever";
        redirect($ret);
    }
    if ($req['l'] == 4)
    {
        $_SESSION['shde_username'] = "555666777";
        $_SESSION['shde_firstname'] = "Αντιγόνη";
        $_SESSION['shde_lastname'] = "Φρίγγου";
        $_SESSION['shde_title'] = "Secr";
        redirect($ret);
    }
    if ($req['l'] == 5)
    {
        $_SESSION['shde_username'] = "748593652";
        $_SESSION['shde_firstname'] = "Χ";
        $_SESSION['shde_lastname'] = "Παπακωνσταντίνου";
        $_SESSION['shde_title'] = "Διευθυντής";
        redirect($ret);
    }
    if ($req['l'] == 6)
    {
        $_SESSION['shde_username'] = "376581546";
        $_SESSION['shde_firstname'] = "Δ";
        $_SESSION['shde_lastname'] = "Γεωργίου";
        $_SESSION['shde_title'] = "Τμηματάρχης";
        redirect($ret);
    }
    if ($req['l'] == 7)
    {
        $_SESSION['shde_username'] = "000578435";
        $_SESSION['shde_firstname'] = "Ε";
        $_SESSION['shde_lastname'] = "Φαράντου";
        $_SESSION['shde_title'] = "Υπάλληλος";
        redirect($ret);
    }
    die;    
}
?>


<div style="margin:20px;" class="content">

<?php
if (!array_key_exists("type",$req))
    diez();

    if ($req['type'] == "taxis2")
    {
        if (array_key_exists("oauth2_results",$_SESSION))
        {
            $_SESSION['afm'] = 0;
            $xml = simplexml_load_string($_SESSION['oauth2_results']);
            foreach($xml->userinfo[0]->attributes() as $a => $b) 
            {
                if ($a == "taxid")
                    $_SESSION['shde_username'] = trim((string)$b);
                if ($a == "lastname")
                    $_SESSION['shde_lastname'] = trim((string)$b);
                if ($a == "firstname")
                    $_SESSION['shde_firstname'] = trim((string)$b);
            }
        }
        $_SESSION['shde_title'] = "user";
        redirect($ret);
        die;
    }

    if ($req['type'] == "taxis")
    {
        $_SESSION['return_msa'] = "shde";
        redirect($login_taxis);
        die;
    }

    if ($req['type'] == "kdd")
    {
        $_SESSION['return_msa'] = "shde";
        redirect($login_kdd);
        die;
    }

    if ($req['type'] == "psd")
    {
        $_SESSION['return_psd_login'] = "shde";
        redirect($login_psd);
        die;
    }

    if ($req['type'] == "demo" && $login_demo)
    {
?>
<button href="login.php?l=1&return=<?= $ret ?>" class="autobutton button is-small is-primary">Χουρδάκης</button>
<button href="login.php?l=2&return=<?= $ret ?>" class="autobutton button is-small is-primary">Μασούρης</button>
<button href="login.php?l=3&return=<?= $ret ?>" class="autobutton button is-small is-primary">Jack Daniels</button>
<button href="login.php?l=4&return=<?= $ret ?>" class="autobutton button is-small is-primary">Φρίγγου</button>
<button href="login.php?l=5&return=<?= $ret ?>" class="autobutton button is-small is-primary">Παπακωνσταντίνου</button>
<button href="login.php?l=6&return=<?= $ret ?>" class="autobutton button is-small is-primary">Γεωργίου</button>
<button href="login.php?l=7&return=<?= $ret ?>" class="autobutton button is-small is-primary">Φαράντου</button>
<?php
    }
?>

</div>