<?php
namespace PHPMailer\PHPMailer;

/**
 * Minimal Standalone PHPMailer Compatibility Class
 * This provides the basic interface the user expects while being 
 * a single-file, zero-dependency SMTP client.
 */
class PHPMailer {
    public $Host;
    public $SMTPAuth = false;
    public $Username;
    public $Password;
    public $SMTPSecure;
    public $Port;
    public $From;
    public $FromName;
    public $Subject;
    public $Body;
    public $AltBody;
    public $CharSet = 'UTF-8';
    public $ContentType = 'text/html';
    public $Mailer = 'smtp';
    
    protected $to = [];
    protected $attachments = [];
    protected $error = '';

    public function isSMTP() { $this->Mailer = 'smtp'; }
    
    public function setFrom($address, $name = '') {
        $this->From = $address;
        $this->FromName = $name;
    }

    public function addAddress($address, $name = '') {
        $this->to[] = $name ? "$name <$address>" : $address;
    }

    public function addAttachment($path, $name = '') {
        if (file_exists($path)) {
            $this->attachments[] = ['path' => $path, 'name' => $name ?: basename($path)];
            return true;
        }
        return false;
    }

    public function isHTML($isHtml = true) {
        $this->ContentType = $isHtml ? 'text/html' : 'text/plain';
    }

    public function send() {
        if ($this->Mailer !== 'smtp') {
            return @mail(implode(',', $this->to), $this->Subject, $this->Body, "From: $this->FromName <$this->From>\r\nContent-Type: $this->ContentType; charset=$this->CharSet");
        }

        // SMTP Implementation
        try {
            $socket = fsockopen(($this->SMTPSecure == 'ssl' ? 'ssl://' : '') . $this->Host, $this->Port, $errno, $errstr, 10);
            if (!$socket) throw new \Exception("Conn Error: $errstr");

            $this->read($socket);
            $this->write($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
            $res = $this->read($socket);

            if ($this->SMTPSecure == 'tls') {
                $this->write($socket, "STARTTLS");
                $this->read($socket);
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->write($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
                $this->read($socket);
            }

            if ($this->SMTPAuth) {
                $this->write($socket, "AUTH LOGIN");
                $this->read($socket);
                $this->write($socket, base64_encode($this->Username));
                $this->read($socket);
                $this->write($socket, base64_encode($this->Password));
                $res = $this->read($socket);
                if (strpos($res, '235') !== 0) throw new \Exception("Auth Failed: $res");
            }

            $this->write($socket, "MAIL FROM: <$this->From>");
            $this->read($socket);
            foreach ($this->to as $t) {
                preg_match('/<(.*)>/', $t, $m);
                $addr = $m[1] ?? $t;
                $this->write($socket, "RCPT TO: <$addr>");
                $this->read($socket);
            }

            $this->write($socket, "DATA");
            $this->read($socket);
            
            $boundary = md5(time());
            $header = "Date: " . date('r') . "\r\n";
            $header .= "To: " . implode(', ', $this->to) . "\r\n";
            $header .= "From: $this->FromName <$this->From>\r\n";
            $header .= "Subject: =?UTF-8?B?" . base64_encode($this->Subject) . "?=\r\n";
            $header .= "MIME-Version: 1.0\r\n";
            
            if (empty($this->attachments)) {
                $header .= "Content-Type: $this->ContentType; charset=$this->CharSet\r\n\r\n";
                $this->write($socket, $header . $this->Body . "\r\n.");
            } else {
                $header .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n\r\n";
                $body = "--$boundary\r\n";
                $body .= "Content-Type: $this->ContentType; charset=$this->CharSet\r\n";
                $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
                $body .= $this->Body . "\r\n\r\n";
                
                foreach ($this->attachments as $att) {
                    $content = base64_encode(file_get_contents($att['path']));
                    $chunks = chunk_split($content);
                    $body .= "--$boundary\r\n";
                    $body .= "Content-Type: application/octet-stream; name=\"" . $att['name'] . "\"\r\n";
                    $body .= "Content-Description: " . $att['name'] . "\r\n";
                    $body .= "Content-Disposition: attachment; filename=\"" . $att['name'] . "\"; size=" . filesize($att['path']) . ";\r\n";
                    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
                    $body .= $chunks . "\r\n";
                }
                $body .= "--$boundary--\r\n.";
                $this->write($socket, $header . $body);
            }

            $this->read($socket);
            $this->write($socket, "QUIT");
            fclose($socket);
            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    protected function write($s, $msg) { fputs($s, $msg . "\r\n"); }
    protected function read($s) { 
        $data = "";
        while($line = fgets($s, 512)) {
            $data .= $line;
            if (isset($line[3]) && $line[3] == ' ') break;
        }
        return $data;
    }
    public function getErrorInfo() { return $this->error; }
}

class Exception extends \Exception {}
class SMTP {}
