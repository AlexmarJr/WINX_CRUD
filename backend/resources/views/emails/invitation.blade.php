<div>
    <!-- The whole future lies in uncertainty: live immediately. - Seneca -->
</div>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Convite para a Winx</title>
</head>
<body style="margin:0;padding:32px 16px;background:#f3f7f8;font-family:Arial,Helvetica,sans-serif;color:#183043;">
    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;max-width:560px;margin:0 auto;background:#fff;border:1px solid #e1eaed;border-radius:14px;">
        <tr><td style="padding:34px 36px 12px;">
            <span style="font-size:25px;font-weight:700;letter-spacing:-1px;color:#16364b;">Winx<span style="color:#23aaa9;">.</span></span>
        </td></tr>
        <tr><td style="padding:12px 36px 36px;">
            <p style="margin:0 0 12px;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#168f97;">Convite para a equipe</p>
            <h1 style="margin:0 0 18px;font-size:28px;line-height:1.2;color:#16364b;">Seu espaço na {{ $companyName }} espera por você.</h1>
            <p style="margin:0 0 26px;font-size:15px;line-height:1.65;color:#526575;">Você foi convidado para fazer parte da equipe na Winx. Crie sua conta para começar.</p>
            <a href="{{ $inviteUrl }}" style="display:inline-block;padding:15px 24px;border-radius:8px;background:#17364a;color:#fff;font-size:15px;font-weight:700;text-decoration:none;">Aceitar convite</a>
            <p style="margin:28px 0 8px;font-size:13px;line-height:1.5;color:#687b88;">O convite é válido até {{ $expiresAt->format('d/m/Y') }}. Se o botão não funcionar, copie este link:</p>
            <p style="margin:0;font-size:12px;line-height:1.6;word-break:break-all;"><a href="{{ $inviteUrl }}" style="color:#168f97;">{{ $inviteUrl }}</a></p>
        </td></tr>
    </table>
    <p style="max-width:560px;margin:18px auto 0;text-align:center;font-size:12px;color:#8a9aa5;">Se você não esperava este convite, pode ignorar este email.</p>
</body>
</html>
