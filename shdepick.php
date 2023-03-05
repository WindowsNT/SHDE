<?php

require_once "functions.php";
require_once "output.php";

$selected = array();

if (array_key_exists("from",$req))
{
    $selected = explode(",",$req['from']);
}

function OrgTree3($top = 0,$mustActive = 1,$sel = array(),$restr = array())
{
    $fis = '';

    if ($top == 0)
    {
        $q1 = QQ("SELECT * FROM ORGCHART WHERE PARENT = 0 ORDER BY NAME ASC");
        while($r1 = $q1->fetchArray())
        {
            if (count($restr) > 0 && !in_array($r1['ID'],$restr))
                continue;
            $n = $r1['NAME'];
            if ($r1['ACTIVE'] == 1)
                $n = sprintf('<b>%s</b>',$r1['NAME']);
            $c = $r1['CODE2'];
            if ($r1['SDDD'] == 1)
                $c = sprintf('<b>%s</b>',$r1['CODE2']);

            

            if ($mustActive && $r1['ACTIVE'] == 0)
                $fis .= sprintf('<li id="%s"><a href="#" class="jstree-disabled"><span class="jname">[%s] %s</span></a>', $r1['CODE'],$c,$n);
            else
            if (in_array($r1['CODE'],$sel))
                {
                    $fis .= sprintf('<li id="%s"><a href="#" class="jstree-clicked jstree-open"><span class="jname">[%s] %s</span></a>', $r1['CODE'],$c,$n);
                }
            else
                $fis .= sprintf('<li id="%s"><span class="jname">[%s] %s</span>', $r1['CODE'],$c,$n);
            $fis .= OrgTree3($r1['ID'],$mustActive,$sel,array());
            $fis .= '</li>';
        }
    }
    else
    {
        $fis .= sprintf('<ul>');
        $q2 = QQ("SELECT * FROM ORGCHART WHERE PARENT = ? ORDER BY NAME ASC",array($top));
        while($r2 = $q2->fetchArray())
        {
            if (count($restr) > 0 && !in_array($r2['ID'],$restr))
                continue;
            $n = $r2['NAME'];
            if ($r2['ACTIVE'] == 1)
                $n = sprintf('<b>%s</b>',$r2['NAME']);
            $c = $r2['CODE2'];
            if ($r2['SDDD'] == 1)
                $c = sprintf('<b>%s</b>',$r2['CODE2']);

            if ($mustActive && $r2['ACTIVE'] == 0)
                $fis .= sprintf('<li id="%s"><a href="#" class="jstree-disabled"><span class="jname">[%s] %s</span></a>',$r2['CODE'], $c,$n);
            else
            if (in_array($r2['CODE'],$sel))
                {
                    $fis .= sprintf('<li id="%s"><a href="#" class="jstree-clicked jstree-open"><span class="jname">[%s] %s</span></a>',$r2['CODE'],$c,$n);
                }
            else
                $fis .= sprintf('<li id="%s"><span class="jname">[%s] %s</span>',$r2['CODE'],$c,$n);
            $fis .= OrgTree3($r2['ID'],$mustActive,$sel,array());
            $fis .= '</li>';
        }
        $fis .= '</ul>';
    }
    return $fis;

}

if (!array_key_exists("active",$req))
    $req['active'] = 1;
if (!array_key_exists("restr",$req))
    $req['restr'] = array();
else
    {
        $req['restr'] = explode(",",$req['restr']);
        if (count($req['restr']) == 1 && $req['restr'][0] == "")   
            $req['restr'] = array();

    }

?>
<div class="content" style="margin:20px">
Filter: <input class="input" id="filter" />
<div id="jstree">
<ul>
<?= OrgTree3(0,$req['active'],$selected,$req['restr']); ?>
</ul>
</div>
<br><br><hr>
<button type="button" class="button is-large is-link" onclick="pick(1);">Επιλογή</button>
</div>


<script>
    var jst;
    var input;
    function pick($ok)
    {
        var selectedElmsIds = [];
        var selectedElms = jst.jstree("get_selected", true);
        $.each(selectedElms, function() {
            selectedElmsIds.push(this.id);
        });
        <?php
        if (array_key_exists('function',$req) && array_key_exists('val',$req))
            printf("%s(selectedElmsIds,'%s');",$req['function'],$req['val']);
        ?>
        return true;
    }
    function looppar(li)
    {
        if (!li)
            return;
        var tn = li.tagName.toLowerCase();
        if (tn == "ul" || tn == "li")
            {
                li.style.display = 'list-item';
                looppar(li.parentElement);
            }
    }

  function startjstree() {

    jst = $("#jstree");
    input = document.getElementById('filter');
    input.onkeyup = function () {
        var filter = input.value.toUpperCase();
        jst.jstree("open_all");
        var lis = document.getElementsByTagName('li');
        for (var i = 0; i < lis.length; i++) {
            var li = lis[i];
            var names = li.getElementsByClassName('jname');
            if (names.length == 0)
                {
                    continue;
                }
            var name = names[0].innerHTML;
            name = name.toUpperCase();
            if (name.includes(filter)) 
                {
                    li.style.display = 'list-item';
                    looppar(li);
                }
            else
                lis[i].style.display = 'none';
        }
        if (filter.length == 0)
            jst.jstree("close_all");
    }

    // 6 create an instance when the DOM is ready
        jst.jstree({
            "plugins" : [ "wholerow","checkbox" ],
            "checkbox" : {
            "three_state" : false,
            },
        });
    // 7 bind to events triggered on the tree
    jst.on("changed.jstree", function (e, data) {
      console.log(data.selected);
    });
    // 8 interact with the tree - either way is OK
    $('button').on('click', function () {
     jst.jstree(true).select_node('child_node_1');
    // $('#jstree').jstree('select_node', 'child_node_1');
  //    $.jstree.reference('#jstree').select_node('child_node_1');
    });
  }

    startjstree();
  </script>