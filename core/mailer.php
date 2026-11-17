<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Envia os e-mails transacionais do projeto por SMTP.
 *
 * A configuração vem de variáveis de ambiente para que o mesmo código sirva
 * ao Mailpit (desenvolvimento) e a um provedor real (produção):
 *
 *   MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD,
 *   MAIL_SECURE (tls|ssl|vazio), MAIL_FROM, MAIL_FROM_NAME
 */
class Mailer
{
    public function __construct(
        private string $host,
        private int $port,
        private string $username,
        private string $password,
        private string $secure,
        private string $from,
        private string $fromName
    ) {
    }

    public static function fromEnv(): self
    {
        return new self(
            (string) (getenv('MAIL_HOST') ?: 'mailpit'),
            (int) (getenv('MAIL_PORT') ?: 1025),
            (string) (getenv('MAIL_USERNAME') ?: ''),
            (string) (getenv('MAIL_PASSWORD') ?: ''),
            (string) (getenv('MAIL_SECURE') ?: ''),
            (string) (getenv('MAIL_FROM') ?: 'nao-responda@biamodas.local'),
            (string) (getenv('MAIL_FROM_NAME') ?: 'Bia Modas')
        );
    }

    /**
     * @throws PHPMailerException quando o SMTP recusa ou não responde.
     */
    public function enviarCodigo(string $para, string $nome, string $codigo): void
    {
        $corpo = $this->renderizar('codigo_recuperacao', [
            'nome' => $nome,
            'codigo' => $codigo,
        ]);

        $mail = new PHPMailer(true);
        $mail->Timeout = 10;
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = $this->host;
        $mail->Port = $this->port;

        // Sem usuário configurado (Mailpit) não há autenticação nem TLS.
        $mail->SMTPAuth = $this->username !== '';

        if ($mail->SMTPAuth) {
            $mail->Username = $this->username;
            $mail->Password = $this->password;
        }

        if ($this->secure !== '') {
            $mail->SMTPSecure = $this->secure;
        }

        $mail->setFrom($this->from, $this->fromName);
        $mail->addAddress($para, $nome);

        $mail->isHTML(true);
        $mail->Subject = 'Seu código de recuperação - Bia Modas';
        $mail->Body = $corpo;
        $mail->AltBody = "Seu código de recuperação é {$codigo}. Ele expira em 10 minutos.";

        $mail->send();
    }

    private function renderizar(string $template, array $dados): string
    {
        $caminho = __DIR__ . '/../views/emails/' . $template . '.php';

        extract($dados, EXTR_SKIP);

        ob_start();
        require $caminho;

        return (string) ob_get_clean();
    }
}
