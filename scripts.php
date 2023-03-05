<?php
header("Content-Type: text/javascript");
$mobile = 0;
?>

function gotop(q)
{
     window.location = q;
}

function goblank(q)
{
  var win = window.open(q, '_blank');
  win.focus();
}


function tgl(fo,msg2)
{
    $(".mm").removeClass("is-active");
    $(fo).addClass("is-active");
    $('#c').hide().html(msg2).fadeIn('slow');
    AutoBu();
}

function hasTouch() {
<?php
if ($mobile == 1)
  echo 'return true;';
?>
  return false;
}

function unblock()
    {
    $.unblockUI();
    }

function block()
{
  $.blockUI({ message: '<br><center><i class="fa fa-circle-o-notch fa-spin fa-4x"></i></center></br>',
    css: {        border: 'none',         padding: '15px',        backgroundColor: '#000',        '-webkit-border-radius': '10px',        '-moz-border-radius': '10px',        opacity: .5,        color: '#fff'    } });
}

function chosen()
{
  if (!hasTouch())
    $(".chosen-select").chosen({search_contains: true}); 
}

function datatab(resp = true,fi = false)
{
var dt = $('.datatable');
if (dt)
    {
    dt.dataTable({
        dom: 'lfrtipB',
        paging: true,
        "pageLength": 20,
        "searching": true,
        bInfo: false,
        fixedHeader: true,
        responsive: resp,
        fixedHeader: fi,
        bFilter: false,
        aaSorting: [],
        "search": {
         "regex": true
        },
        lengthMenu: [
            [1,5,10, 25, 50, -1],
            [1,5,10, 25, 50, 'All'],
        ],
    });
    }
}


function AutoBu()
{
   $(".autobutton").click(
        function(event)
            {
            var e = $(this).attr("exist");
            if (e == "exist")
                {
                event.preventDefault();
                return;
                }
            var nv = $(this).html();
            var nv2 = '<span class="fa fa-circle-o-notch fa-spin" aria-hidden="true"></span> ' + nv;
            $(this).html(nv2);

            var url = $(this).attr("href");
            var rep = 0;
            if (url == "" || url === undefined)
               {
rep = 1;
 url = $(this).attr("hrefr");
}
            if (url == "" || url === undefined)
                {
                // Form?
                var form = $(this).parents('form:first');
                if(form !== undefined)
                    {
                    $(this).attr("exist","exist");
//                    $('input.btn-primary').prop("disabled", "disabled");
                    //$(this).prop('disabled',true);
                    // block();
                    elblock($(this));
                    form.submit();
                    }

                return;
                }

            $(this).attr("exist","exist");
            $(this).prop('disabled',true);
            var trg = $(this).attr("trg");

            if (trg == "self")
                g(url);
             else
                {
if (rep == 1)


window.location.replace(url);
else
                gotop(url);
            }
            }
        );
}



var iV = null;
function summersel(v,mh)
{
    v.summernote({


	

            height: mh,
            toolbar: [
   		  		
    ['style', ['style']],
    ['font', ['bold', 'italic', 'underline', 'clear']],
    ['fontname', ['fontsize']],
    ['color', ['color']],
    ['para', ['ul', 'ol', 'paragraph']],
    ['height', ['height']],
    ['table', ['table']],
    ['insert', ['link', 'picture', 'hr', 'equation']],
    ['view', ['fullscreen', 'codeview']],
    ['undo', ['undo', 'redo']],
    ['help', ['help']]
                ],
            callbacks:
                {
					onPaste: function (e) {
						
		if (!confirm("Use plain text paste?"))
		{

		}
		else
		{
			var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
			e.preventDefault();
			document.execCommand('insertText', false, bufferText);

		}

    },

	
                    onInit: function(e)
                        {
                     /*   if ("undefined" === typeof remember)
                            return;
                        var s = $("#msg").summernote("code");
                        var f = localStorage.getItem(remember);
                        if (f != "[object Object]" && f != null && f.length > 10)
                            $("#msg").summernote("code", f); */
                        }
                }
                });
}


function summer(vx = "#msg")
{
   var msglines = parseInt($(vx).attr("data-lines"));
    if (msglines == 0 || isNaN(msglines))
        msglines = 100;
    var mh = 10*msglines;

    var remember = $(vx).attr("data-remember");
    clearInterval(iV);
    summersel($(vx),mh);
        if ($(vx).length)
        {
        if ("undefined" !== typeof remember)
            iV = setInterval(function()
                {
                var code = $(vx).summernote("code");
                if (code.length > 20)
                    localStorage.setItem(remember, code);
                }, 5000); // every 5 second interval
        }

}

function setCookie(cname, cvalue, exdays) {
  const d = new Date();
  d.setTime(d.getTime() + (exdays*24*60*60*1000));
  let expires = "expires="+ d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}


function deleteCookie(cname) {
  let expires = "expires=Thu, 01 Jan 1970 00:00:00 UTC";
  document.cookie = cname + "=" + ";" + expires + ";path=/";
}


function getCookie(cname) {
  let name = cname + "=";
  let decodedCookie = decodeURIComponent(document.cookie);
  let ca = decodedCookie.split(';');
  for(let i = 0; i < ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}


$(document).ready(function()
{
    AutoBu();
    datatab();
    chosen();
    summer();

});