<?php
require __DIR__ . '/vendor/autoload.php';
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Header\HeaderConsts;
use Ddeboer\Imap\SearchExpression;
use Ddeboer\Imap\Search\Email\To;
use Ddeboer\Imap\Search\Date\On;
use Ddeboer\Imap\Search\Text\Subject;
use Ddeboer\Imap\Server;

$directory = 'RECOVERED_FILES_DIR';
$dir = new DirectoryIterator($directory);
const START_DATE = new DateTime("22/11/1234");
const EMAIL_TO = 'EMAIL_MAILBOX_OWNER';
const EMAIL_PASS = 'PASSWORD_MAILBOX';

//connect mailbox
$server = new Server('mail.enguer.com');
// $connection is instance of \Ddeboer\Imap\Connection
$connection = $server->authenticate(EMAIL_TO, EMAIL_PASS);
$mailbox = $connection->getMailbox('INBOX');

function upload_email($mailbox,$message,$mime){
    $to = strtolower($message->getHeaderValue(HeaderConsts::TO));
    if ($to!=EMAIL_TO) throw new Exception('Not the good target '.$to);
    //check date
    $checkDate = new DateTime($message->getHeaderValue(HeaderConsts::ORIG_DATE));
    if ($checkDate<START_DATE) {
        echo "- <!> REJECTED: Before the start date " . START_DATE->format('Y-m-d H:i:s') . " ".$checkDate->format('Y-m-d H:i:s')." \n";
        throw new Exception('Not in the date range');
    }
    //check message is present
    $search = new SearchExpression();
    $search->addCondition(new To(EMAIL_TO));
    $search->addCondition(new Subject($message->getSubject()));
    $search->addCondition(new On(new DateTime($message->getHeaderValue(HeaderConsts::ORIG_DATE))));
    $messages = $mailbox->getMessages($search);
    if (sizeof($messages) > 0) {
        throw new Exception('Message already exists');
    }
    $receivedDate = new DateTime($message->getHeaderValue(HeaderConsts::ORIG_DATE));
    //upload email
    $mailbox->addMessage($mime, '\\Seen',$receivedDate);
}


$num =0;
foreach ($dir as $fileinfo) {
    if (!$fileinfo->isDot()) {
        $file = $directory.$fileinfo->getFilename();
        //retrieve messages from files
        $mailParser = new MailMimeParser();
        $handle = fopen($file, 'r');
        echo "---------------------------------------------------\n";
        echo "- trying $file \n";
        try{
            $message = $mailParser->parse($handle, false);
            $from = $message->getHeaderValue(HeaderConsts::FROM);
            if (empty($from)) throw new Exception('No from address found');
            echo "- FROM: ".$from."\n";     // user@example.com
            echo "- SUBJECT: ".$message->getSubject()."\n";                           // The email's subject
            echo "- DATE: ".$message->getHeaderValue(HeaderConsts::ORIG_DATE)."\n";                           // The email's subject
            upload_email($mailbox,$message,file_get_contents($file));
            //die('<!> only one valid email for now');
            echo "- NUM: ".$num."\n";                           // The email's subject
            $num ++;
        }catch (Throwable $e){
            echo "- <!> ERROR: Not a valid email - ".$e->getMessage()." - ".$e->getLine()."\n";
        }
    }
}

echo "---------------------------------------------------\n";
echo "- TOTAL: ".$num." valid emails\n";                           // The email's subject
echo "---------------------------------------------------\n";





