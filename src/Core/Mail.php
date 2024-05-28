<?php

namespace Tabel\Core\Mantle;

use PHPMailer\PHPMailer\PHPMailer;

class Mail {
    private $host;
    private $username;
    private $password;
    private $isSMTP;
    private $SMTPAuth;
    private $SMTPSecure;
    private $port;
    private $mailer;

    public function __construct(array $config) {
        $this->host = $config['host'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->isSMTP = $config['smtp'];
        $this->SMTPAuth = $config['smtp_auth'];
        $this->SMTPSecure = $config['smtp_secure'];
        $this->port = $config['port'];

       /// $this->mailer = new PHPMailer(true);
    }

    public function setUp() {
        $this->mailer->isSMTP($this->isSMTP);
        $this->mailer->Host = $this->host;
        $this->mailer->SMTPAuth = $this->SMTPAuth;
        $this->mailer->Username = $this->username;
        $this->mailer->Password = $this->password;
        $this->mailer->SMTPSecure = $this->SMTPSecure ?? 'tls';
        $this->mailer->Port = $this->port ?? 2525;

        return $this;
    }

    public function addSender(string $emailAddress, string $name) {
        $this->mailer->setFrom($emailAddress, $name);
        return $this;
    }

    public function addReplyTo(string $emailAddress, string $name) {
        $this->mailer->addReplyTo($emailAddress, $name);
        return $this;
    }

    public function addRecipient(string $emailAddress, string $name) {
        $this->mailer->addAddress($emailAddress, $name);
        return $this;
    }

    public function addSubject(string $subject) {
        $this->mailer->Subject = $subject;
        return $this;
    }

    public function addBody(string $emailBody) {
        $this->mailer->isHTML(true);
        $this->mailer->Body = $emailBody;
        return $this;
    }

    public function send() {
        try {
            $this->mailer->send();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
