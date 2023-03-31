<?php

// Import the configurations
require_once ( __DIR__ . '/configuration.php' );

// Load SBMailer Class
require_once ( __DIR__ . '/../SBMailer.php' );

$result = array();

$from = "";
$fromName = "";
$replyToList = array();
$toList = array();
$ccList = array();
$bccList = array();
$tag = "";
$subject = "";
$isHtml = true;
$body = "";
$textBody = "";

// Creates the default mailer instance as configurations
$mailer = SBMailer::createDefault();
$emailProvider = $mailer->getMailerName();

// Or, create directly by name
// $mailer = SBMailer::createByName('sendgrid', array('api_key' => getenv('SENDGRID_API_KEY')));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
   function getInput($field, $enableHtml = false) {
      if (isset($_POST[$field])) {
         $data = trim($_POST[$field]);
         $data = stripslashes($data);
         if (!$enableHtml) {
               $data = htmlspecialchars($data);
         }
         return $data;
      }
      return "";
   }

   function getEmailsFromInput ($field) {
      $emails = array();
      if (!empty($_POST[$field]) && is_array($_POST[$field])) {
         foreach($_POST[$field] as $i => $e) {
            $email = trim($e);
            $email = stripslashes($email);

            $nameIndex = $field . "Name";
            $name = !empty($_POST[$nameIndex]) && is_array($_POST[$nameIndex]) && !empty($_POST[$nameIndex][$i]) ? $_POST[$nameIndex][$i] : '';
            
            if (!empty($email)) {
               $emails[] = array ("email" => $email, "name" => $name);
            }
         }
      }
      if (count($emails) > 0) {
         return $emails;
      }
      return array();
   }

   /**
     * Return the error description of the uploaded file
     * 
     * @param string $path
     * 
     * @return string|false The function returns the error description or false on failure.
     */
   function getUploadErrorDescription ($uploadErrorCode) {
      $phpFileUploadErrors = array(
         //0 => 'There is no error, the file uploaded with success',
         1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
         2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
         3 => 'The uploaded file was only partially uploaded',
         4 => 'No file was uploaded',
         6 => 'Missing a temporary folder',
         7 => 'Failed to write file to disk.',
         8 => 'A PHP extension stopped the file upload.',
      );

      if (isset($phpFileUploadErrors[$uploadErrorCode])) {
         return $phpFileUploadErrors[$uploadErrorCode];
      }
      return false;
   }

   // Defining true enable Exceptions
   // $mailer = SBMailer::createDefault(true);

   for ($i = 0; $i<1; $i++) {

      // Set the From fields of email
      $from = getInput("from");
      $fromName = getInput("fromName", true);
      $mailer->setFrom($from, $fromName);
      
      $replyToList = getEmailsFromInput ("replyTo");
      foreach($replyToList as $recipient) {
         $mailer->addReplyTo($recipient["email"], $recipient["name"]);
      }

      // Add recipients
      $toList = getEmailsFromInput ("to");
      foreach($toList as $recipient) {
         $mailer->addAddress($recipient["email"], $recipient["name"]);
      }

      // CC
      $ccList = getEmailsFromInput ("cc");
      foreach($ccList as $recipient) {
         $mailer->addCC($recipient["email"], $recipient["name"]);
      }

      // BCC
      $bccList = getEmailsFromInput ("bcc");
      foreach($bccList as $recipient) {
         $mailer->addBcc($recipient["email"], $recipient["name"]);
      }

      // Add attachments
      if (is_array($_FILES['attach']) && is_array($_FILES['attach']['name'])) {
         $attach = $_FILES['attach'];
         foreach($attach['name'] as $index => $name) {
            if (!empty($name)) {
               $errorCode = isset($attach["error"][$index]) ? $attach["error"][$index] : 0;
               if ($errorCode !== UPLOAD_ERR_OK) {
                  $result[] = "Attachment (".$name.") not added due to error: " . 
                     getUploadErrorDescription($errorCode);
               } else {
                  $tmpName = $attach['tmp_name'][$index];
                  $success = $mailer->addAttachment( $tmpName, $name);
                  if (!$success) {
                     $result[] = "Some error when adding Attachment (" . 
                        $name . 
                        "). Details: " . 
                        $mailer->getErrorInfo();
                  }
               }
            }
         }
      }

      // Set the tag
      $tag = getInput("tag");
      if (!empty($tag)) {
         $mailer->setTag($tag);
      }

      // Set the subject and the email body
      $subject = getInput("subject");
      $mailer->setSubject("[$i] - " . $subject);
      //$mailer->Subject = $subject; // PHPMailer compatibility

      // We use HTML by default. Use it if you need text/plain
      $isHtml = getInput("is_html") == "Y";
      $mailer->isHTML($isHtml);
      
      $body = getInput("body", true);
      $mailer->setBody($body);
      //$mailer->Body = $body; // PHPMailer compatibility

      $textBody = getInput("textBody");
      $mailer->setAltBody($textBody);
      //$mailer->AltBody = $textBody; // PHPMailer compatibility

      // Sends the email
      if ($mailer->send ()) {
         $result[] = "SUCCESS! Email has been sent by " . $mailer->getMailerName();
      } else {
         $result[] = $mailer->getErrorInfo();
      }

      // // WHEN SENDING BATCH
      // if (!$mailer->deferToQueue()) {
      //    $result[] = "Error adding to Queue: " . $mailer->getErrorInfo();
      // }
   }
   
   // // WHEN SENDING BATCH
   // $resultList = $mailer->sendQueue();

   // foreach($resultList as $index => $messageResult) {
   //    $result[] = "[$index] - " . $messageResult["status"] . " - " . $messageResult["message"];
   // }



   // // When exceptions are enabled
   // try {
   //     $mailer->send ();
   //     $result[] = "SUCCESS! Email has been sent by " . $mailer->getMailerName();
   // } catch (Exception $e) {
   //     $result[] = $e->getMessage();
   // }
}

function initEmailList (&$emailList) {
   if (empty($emailList)) {
      $emailList = array( array ("email" => "", "name" => "") );
   }
}

initEmailList($replyToList);
initEmailList($toList);
initEmailList($ccList);
initEmailList($bccList);

function createEmailHtml ($emailList, $fieldId, $fieldName, $fieldDescription) {
   $lastIndex = count($emailList);
   foreach ($emailList as $index => $email) {
      $showId = $lastIndex == ($index+1) ? " id=\"row_$fieldId\"" : "";
?>
   <tr<?php echo $showId; ?>>
      <td class="header"><?php echo $fieldDescription; ?>:</td>
      <td><input type="email" name="<?php echo $fieldName; ?>[]" value="<?php echo $email["email"]; ?>" placeholder="Email"></td>
      <td><input type="text" name="<?php echo $fieldName; ?>Name[]" value="<?php echo $email["name"]; ?>" placeholder="Name"></td>
   </tr>
<?php
   }
?>
   <tr>
      <td>&nbsp;</td>
      <td colspan="2"><button type="button" class="add_link" id="button_add_<?php echo $fieldId; ?>">+ Add</button></td>
   </tr>
<?php
}

?><html>
<head>
   <title>Test Email</title>
   <style>
      .error {color: #FF0000;}
      table {
        width: 100%;
      }
      table tr td.header {
        background-color: #eeeeee;
        text-align: right;
      }
      input, textarea {
        width: 100%;
      }
      input[type="checkbox"] {
         width: auto;
      }
      input.button {
        width: 100px;
        font-weight: bold;
      }
      .add_link {
         font-size: 12px;
      }
   </style>
</head>

<body> 
     
   <h2>Test New Email (<?php echo $emailProvider; ?>)</h2>
   
   <p><span class = "error"><?php 
      if (count($result) > 0) {
         echo "Messages: <ul>";
         foreach($result as $error) {
            echo "<li>" . $error . "</li>";
         }
         echo "</ul>";
      }
   ?></span></p>
   
   <form method="POST" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
      <table id="form_table">
         <tr>
            <td class="header">From:</td>
            <td><input type="email" name="from" value="<?php echo $from; ?>" required placeholder="Email"></td>
            <td><input type="text" name="fromName" value="<?php echo $fromName; ?>" placeholder="Name"></td>
         </tr>
         <?php createEmailHtml($replyToList, "reply_to", "replyTo", "Reply To"); ?>
         <?php createEmailHtml($toList, "to", "to", "To"); ?>
         <?php createEmailHtml($ccList, "cc", "cc", "CC"); ?>
         <?php createEmailHtml($bccList, "bcc", "bcc", "BCC"); ?>
         <tr>
            <td class="header">Tag:</td>
            <td colspan="2"><input type="text" name="tag" value="<?php echo $tag; ?>"></td>
         </tr>
         <tr>
            <td class="header">Subject:</td>
            <td colspan="2"><input type="text" name="subject" value="<?php echo $subject; ?>" required></td>
         </tr>
         <tr>
            <td class="header">Main Body: <br>
               <?php
                  $cbHtml = "";
                  if ($isHtml) {
                     $cbHtml = ' checked="checked"';
                  }
               ?>
               <input type="checkbox" name="is_html" value="Y"<?php echo $cbHtml; ?>></input> HTML
            </td>
            <td colspan="2"><textarea rows="5" name="body"><?php echo $body; ?></textarea></td>
         </tr>
         <tr>
            <td class="header">Text Body:</td>
            <td colspan="2"><textarea rows="5" name="textBody"><?php echo $textBody; ?></textarea></td>
         </tr>
         <tr id="row_att">
            <td class="header">Attachment:</td>
            <td colspan="2">
                <input name="attach[]" type="file" value="" />
            </td>
         </tr>
         <tr>
            <td>&nbsp;</td>
            <td colspan="2"><button type="button" class="add_link" id="button_add_att">+ Add</button></td>
         </tr>
         <tr>
            <td colspan="3">
               <input class="button" type="submit" name="submit" value="Send"> 
            </td>
         </tr>
      </table>
   </form>
   <script type="text/javascript">

      var table = document.getElementById("form_table");
      var buttonReplyTo = document.getElementById("button_add_reply_to");
      var buttonTo = document.getElementById("button_add_to");
      var buttonCC = document.getElementById("button_add_cc");
      var buttonBcc = document.getElementById("button_add_bcc");
      var buttonAtt = document.getElementById("button_add_att");

      var index_reply_to = getRowIndex('row_reply_to');
      var index_to = getRowIndex('row_to');
      var index_cc = getRowIndex('row_cc');
      var index_bcc = getRowIndex('row_bcc');
      var index_att = getRowIndex('row_att');

      buttonReplyTo.addEventListener('click', function (event) {
         addEmailField (index_reply_to, "replyTo", "Reply To");
         index_reply_to++;
         index_to++;
         index_cc++;
         index_bcc++;
         index_att++;
      });
      buttonTo.addEventListener('click', function (event) {
         addEmailField (index_to, "to", "To");
         index_to++;
         index_cc++;
         index_bcc++;
         index_att++;
      });
      buttonCC.addEventListener('click', function (event) {
         addEmailField (index_cc, "cc", "CC");
         index_cc++;
         index_bcc++;
         index_att++;
      });
      buttonBcc.addEventListener('click', function (event) {
         addEmailField (index_bcc, "bcc", "BCC");
         index_bcc++;
         index_att++;
      });
      buttonAtt.addEventListener('click', function (event) {
         addFileField (index_att, "attach", "Attachment");
         index_att++;
      });

      function getRowIndex (rowId) {
         var tr = document.getElementById(rowId);
         return (tr.rowIndex + 1);
      }

      function addEmailField (index, name, description) {
         var row   = table.insertRow(index);
         
         var cell1 = row.insertCell(0);
         cell1.innerHTML = description + ":";
         cell1.className += "header";

         var cell2 = row.insertCell(1);
         cell2.innerHTML = "<input type=\"email\" name=\""+name+"[]\" placeholder=\"Email\">";
         
         var cell3 = row.insertCell(2);
         cell3.innerHTML = "<input type=\"text\" name=\""+name+"Name[]\" placeholder=\"Name\">";
      }

      function addFileField (index, name, description) {
         var row   = table.insertRow(index);
         
         var cell1 = row.insertCell(0);
         cell1.innerHTML = description + ":";
         cell1.className += "header";

         var cell2 = row.insertCell(1);
         cell2.colSpan = 2;
         cell2.innerHTML = "<input name=\"attach[]\" type=\"file\" value=\"\" />";
      }

   </script>
</body>
</html>