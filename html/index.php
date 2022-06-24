<?php

// Load SBMailer Class
require_once ( __DIR__ . '/../SBMailer.php' );

// Import the configurations
require_once ( __DIR__ . '/configuration.php' );

$result = "";

$from = "";
$fromName = "";
$replayTo = "";
$replayToName = "";
$to = "";
$toName = "";
$cc = "";
$ccName = "";
$bcc = "";
$bccName = "";
$subject = "";
$body = "";

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

    // Creates the default mailer instance as configurations
    $mailer = SBMailer::createDefault();

    // Defining true would enable Exceptions
    // $mailer = SBMailer::createDefault(true);

    // Set the From fields of email
    $from = getInput("from");
    $fromName = getInput("fromName");
    $mailer->setFrom($from, $fromName);
    
    $replyTo = getInput("replyTo");
    $replyToName = getInput("replyToName");
    if (!empty($replyTo)) {
        $mailer->addReplyTo($replyTo, $replyToName);
    }

    // Add recipients
    $to = getInput("to");
    $toName = getInput("toName");
    $mailer->addAddress ($to, $toName);
    
    // CC
    $cc = getInput("cc");
    $ccName = getInput("ccName");
    if (!empty($cc)) {
        $mailer->addCC($cc, $ccName);
    }
    
    // BCC
    $bcc = getInput("bcc");
    $bccName = getInput("bccName");
    if (!empty($bcc)) {
        $mailer->addBcc($bcc, $bccName);
    }

    // Add attachments
    if (isset($_FILES['attach']) && !empty($_FILES['attach']["tmp_name"])) {
        $mailer->addAttachment( $_FILES['attach']['tmp_name'], $_FILES['attach']['name']);
    }

    // Set the subject and the email body
    // Always HTML body
    $subject = getInput("subject");
    $mailer->setSubject($subject);
    //$mailer->Subject = (getInput("subject")); // PHPMailer compatibility

    //$mailer->isHTML(false); // We use HTML by default. Use it if you need text/plain
    $body = getInput("body");
    $mailer->setBody(getInput("body", true));
    //$mailer->Body = getInput("body", true); // PHPMailer compatibility

    //$mailer->setAltBody("Alternative Body when reader does not support HTML");
    //$mailer->AltBody = "Alternative Body when reader does not support HTML"; // PHPMailer compatibility

    // Sends the email
    if ($mailer->send ()) {
        $result = "Email has been sent.";
    } else {
        $result = $mailer->getErrorInfo();
    }

    // // When exceptions enabled
    // try {
    //     $mailer->send ();
    //     echo "Email sent.";
    // } catch (Exception $e) {
    //     echo $e->getMessage();
    // }

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
      input.button {
        width: 100px;
        font-weight: bold;
      }
   </style>
</head>

<body> 
     
   <h2>Test New Email</h2>
   
   <p><span class = "error"><?php echo $result; ?></span></p>
   
   <form method="POST" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
      <table>
         <tr>
            <td class="header">From:</td>
            <td><input type="email" name="from" value="<?php echo $from; ?>" required placeholder="Email"></td>
            <td><input type="text" name="fromName" value="<?php echo $fromName; ?>" placeholder="Name"></td>
         </tr>
         <tr>
            <td class="header">Reply To:</td>
            <td><input type="email" name="replyTo" value="<?php echo $replyTo; ?>" placeholder="Email"></td>
            <td><input type="text" name="replyToName" value="<?php echo $replyToName; ?>" placeholder="Name"></td>
         </tr>
         <tr>
            <td class="header">To:</td>
            <td><input type="email" name="to" value="<?php echo $to; ?>" required placeholder="Email"></td>
            <td><input type="text" name="toName" value="<?php echo $toName; ?>" placeholder="Name"></td>
         </tr>
         <tr>
            <td class="header">CC:</td>
            <td><input type="email" name="cc" value="<?php echo $cc; ?>" placeholder="Email"></td>
            <td><input type="text" name="ccName" value="<?php echo $ccName; ?>" placeholder="Name"></td>
         </tr>
         <tr>
            <td class="header">BCC:</td>
            <td><input type="email" name="bcc" value="<?php echo $bcc; ?>" placeholder="Email"></td>
            <td><input type="text" name="bccName" value="<?php echo $bccName; ?>" placeholder="Name"></td>
         </tr>
         <tr>
            <td class="header">Subject:</td>
            <td colspan="2"><input type="text" name="subject" value="<?php echo $subject; ?>" required></td>
         </tr>
         <tr>
            <td class="header">Html Body:</td>
            <td colspan="2"><textarea rows="5" name="body"><?php echo $body; ?></textarea></td>
         </tr>
         <tr>
            <td class="header">Attachment:</td>
            <td colspan="2">
                <input type="hidden" name="MAX_FILE_SIZE" value="10000" />
                <input name="attach" type="file" />
            </td>
         </tr>
         <tr>
            <td colspan="3">
               <input class="button" type="submit" name="submit" value="Send"> 
            </td>
         </tr>
      </table>
   </form>
   
</body>
</html>