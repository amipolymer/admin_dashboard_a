<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
</head>

<body style="margin:0; padding:0; background-color:#f4f6f8;">

<!-- FULL WIDTH BACKGROUND -->
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" bgcolor="#f4f6f8">
    <tr>
        <td align="center" style="padding:20px;">

            <!-- FIXED WIDTH BOX (IMPORTANT) -->
            <table width="600" cellpadding="0" cellspacing="0" role="presentation"
                   style="background:#ffffff;">

                <tr>
                    <td style="padding:24px; font-family:Arial, sans-serif; font-size:14px; color:#333333;">

                        <p>Hello,</p>

                        <p>
                            You are receiving this email because we received a request to reset the password for your account.
                        </p>

                        <p>Please click the button below to reset your password:</p>

                        <!-- BUTTON -->
                        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:30px 0;">
                            <tr>
                                <td align="center">
                                    <table role="presentation" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td bgcolor="#4757df" style="padding:12px 30px;">
                                                <a href="{{ $url }}"
                                                   style="
                                                     font-family: Arial, sans-serif;
                                                     font-size: 16px;
                                                     color: #ffffff;
                                                     text-decoration: none;
                                                     font-weight: bold;
                                                     display: inline-block;
                                                   ">
                                                    Reset Password
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <p>This password reset link will expire in 60 minutes.</p>

                        <p>
                           If you did not request a password reset, you can safely ignore this email. No further action is required.
                        </p>
                        <p>
                            If you have any questions or need assistance, please contact our support team.
                        </p>

                        <hr style="border:none; border-top:1px solid #e5e5e5; margin:20px 0;">

                        <p>
                            Thanks,<br>
                            <strong>APPL Edigital Team</strong>
                        </p>

                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>

</body>
</html>
