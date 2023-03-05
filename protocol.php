<?php


require_once "functions.php";
if (!$u)
    die;
if ($u->uid == 0)
    die;

if (array_key_exists("items",$req))
{
    if (!is_array($req['items']))
        $req['items'] = explode(",",$req['items']);
    $ret = array();
    QQ("BEGIN TRANSACTION;");
    foreach($req['items'] as $it)
    {
        if (UserAccessDocument($it,$u->uid) != 2)
            continue;
        $dr = DRow($it);
        if ($dr['PROT'] && strlen($dr['PROT']))
            continue;


        $to = '';
        $jrecp = ReceipientArrayText($it);
        foreach($jrecp as $jr)
        {
            $to .= $jr;
            $to .= ' ';
        }

        $px = NewProtocol($dr['TOPIC'],1,$to,$dr['CLASSIFIED']);       
        QQ("UPDATE DOCUMENTS SET PROT = ? WHERE ID = ? AND (PROT IS NULL OR PROT = '')",array(serialize($px),$it));
        $ret[$it] = DocTR($it,2);
    }
    QQ("COMMIT;");
    print(json_encode($ret));
    die;
}