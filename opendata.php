<?php

require_once "functions.php";

$dvg_username = '10599_api';
$dvg_password = 'User@10599';



function BuildJsonFor($did,$mid)
{
    $x = json_decode('{
        "publish": true,
        "signerIds": [
          "10911"
        ],
        "unitIds": [
          "10602"
        ],
        "decisionTypeId": "Β.1.3",
        "subject": "ΑΠΟΦΑΣΗ ΑΝΑΛΗΨΗΣ ΥΠΟΧΡΕΩΣΗΣ",
        "thematicCategoryIds": [
          "20"
        ],
        "protocolNumber": "2014/1/001",
        "issueDate": "2014-06-20T00:00:00.000Z",
        "organizationId": "10599",
        "extraFieldValues": {
          "financialYear": 2014,
          "budgettype": "Τακτικός Προϋπολογισμός",
          "entryNumber": "1000",
          "partialead": false,
          "recalledExpenseDecision": false,
          "amountWithVAT": {
            "amount": 150,
            "currency": "EUR"
          },
          "amountWithKae": [
            {
              "kae": "1234",
              "amountWithVAT": 100
            },
            {
              "kae": "4321",
              "amountWithVAT": 50
            }
          ],
          "relatedDecisions": []
        }
      }');


/*      $metadata['actions'] = array(
        array("name"=>"notifyRecipients", "args"=>$recipients)
    );
    */

    // $metadata['subject'] = 'ΑΠΟΦΑΣΗ ΑΝΑΛΗΨΗΣ ΥΠΟΧΡΕΩΣΗΣ [ΔΙΟΡΘΩΣΗ ΠΡΑΞΗΣ ΜΕ ΣΥΝΗΜΜΕΝΟ]';

    // Create attachments array. Each item is (file, mimetype, description)
$attachments = array(
    array("Attachment.docx",
          "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
          "This is an attachment"),
          
    array("Attachment.xlsx",
          "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
          "This is another attachment"),
);


}

function svg_Send($did,$mid)
{
    global $dvg_username,$dvg_password;
    global $basedvgurl;
    $url = $basedvgurl . '/decisions';


    $metadata = BuildJsonFor($did,$mid);

    $c = curl_init();
    curl_setopt($c, CURLOPT_USERPWD, $dvg_username . ":" . $dvg_password);      
    curl_setopt($c, CURLOPT_URL, $url );

    /*
       // Add metadata and decision document
        $req->addPostData('metadata', json_encode($metadata));
        $req->addFile('decisionFile', $pdf, 'application/pdf' );
        
        if ($attachments !== null && (sizeof($attachments) > 0)) {
            $this->_addAttachments($req, $attachments);
        }
        
        $result = $req->sendRequest();
        */

        

    $response = curl_exec($c);
    curl_close($c);
    return $response;
    }

function dvg_GetSites()
{
    global $basedvgurl;
    $url = $basedvgurl . '/organizations/10599/details';

}

function dvg_GetTypes()
{
    global $basedvgurl;
    $url = $basedvgurl . '/types';

}