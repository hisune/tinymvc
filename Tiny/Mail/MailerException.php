<?php
/**
 * PHPMailer exception handler
 * @package PHPMailer
 */
namespace Tiny\Mail;

class MailerException extends \Exception
{
    /**
     * Prettify error message output
     * @return string
     */
    public function errorMessage()
    {
        $errorMsg = '<strong>' . $this->getMessage() . "</strong><br />\n";
        return $errorMsg;
    }
}