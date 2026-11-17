<?php
/** @var string $nome */
/** @var string $codigo */
?>
<!doctype html>
<html lang="pt-br">
<body style="margin:0;padding:0;background:#f6f4f1;font-family:Arial,Helvetica,sans-serif;color:#2b2b2b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f4f1;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:12px;padding:32px;">
                    <tr>
                        <td style="text-align:center;padding-bottom:16px;">
                            <h1 style="margin:0;font-size:22px;color:#8b5e3c;">Bia Modas</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:15px;line-height:1.6;">
                            <p style="margin:0 0 16px;">Olá, <?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>.</p>
                            <p style="margin:0 0 24px;">
                                Recebemos um pedido para redefinir a senha da sua conta.
                                Use o código abaixo:
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align:center;padding:8px 0 24px;">
                            <div style="display:inline-block;background:#f6f4f1;border:1px solid #e4ddd5;border-radius:8px;padding:16px 28px;font-size:32px;letter-spacing:8px;font-weight:bold;color:#2b2b2b;">
                                <?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:14px;line-height:1.6;color:#6b6b6b;">
                            <p style="margin:0 0 8px;">O código expira em 10 minutos e só pode ser usado uma vez.</p>
                            <p style="margin:0;">Se não foi você quem pediu, ignore este e-mail. Sua senha continua a mesma.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
