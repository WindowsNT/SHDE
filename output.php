<?php

$icf = "icon.svg";

?>
<title><?= $title ?></title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#999999" />

<link rel="icon" href="<?= $icf ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
<link href="https://fonts.googleapis.com/css?family=Noto+Serif|Tinos" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bm/dt-1.13.2/date-1.3.0/fc-4.2.1/fh-3.3.1/r-2.4.0/datatables.min.css"/>
<script type="text/javascript" src="https://cdn.datatables.net/v/bm/dt-1.13.2/date-1.3.0/fc-4.2.1/fh-3.3.1/r-2.4.0/datatables.min.js"></script>

 <link href="https://unpkg.com/treeflex/dist/css/treeflex.css" rel="stylesheet">
<!-- CSS -->
<link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.min.css">
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.jquery.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/themes/default/style.min.css" />
<!-- include summernote css/js -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.blockUI/2.70/jquery.blockUI.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet"></link>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/jstree.min.js"></script>


<script src="scripts.php"></script>
<style>

    
       .treetop 
        {
            list-style-type: none !important;
            padding-left: 0 !important;
            list-style-position: inside;
        }
        ul, .treenested
        {
            list-style-type: none !important;
            padding-left: 0 !important;
            list-style-position: inside;
        }


        .treenested {
            display: none;
        }

        .treeactive {
            display: block;
        }
        
        .treecaret {
            cursor: pointer;
            -webkit-user-select: none; /* Safari 3.1+ */
            -moz-user-select: none; /* Firefox 2+ */
            -ms-user-select: none; /* IE 10+ */
            user-select: none;
        }

        
        .treecaret::before {
        content: "\25B6";
        color: black;
        display: inline-block;
        margin-right: 6px;
        }

        .treecaret-down::before {
        -ms-transform: rotate(90deg); /* IE 9 */
        -webkit-transform: rotate(90deg); /* Safari */'
        transform: rotate(90deg);  
        }

        .button.is-smaller
        {
            font-size: .60rem;
        }

/*        html { 
  background: url(shdebg3.jpg) no-repeat center center fixed; 
  -webkit-background-size: cover;
  -moz-background-size: cover;
  -o-background-size: cover;
  background-size: cover;

}
*/

.button.is-rounded {
  border-radius: 290486px;
}

.field.has-addons .control:first-child .is-rounded {
  border-bottom-left-radius: 290486px;
  border-top-left-radius: 290486px;
}
.field.has-addons .control:last-child .is-rounded {
  border-bottom-right-radius: 290486px;
  border-top-right-radius: 290486px;
}

</style>
