<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drastha Learning</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; -webkit-font-smoothing: antialiased;">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; padding: 40px 0;">
        <tr>
            <td align="center">
                <!-- Wrapper Card with subtle shadow -->
                <table width="600" border="0" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 24px; overflow: hidden; border: 1px solid #f1f5f9; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                    <!-- Header with custom logo styling -->
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #1A2B49 0%, #264790 100%); padding: 40px 0;">
                            <h2 style="color: #ffffff; margin: 0; font-size: 26px; font-weight: 800; letter-spacing: 0.5px;">Drastha Learning</h2>
                            <p style="color: #cbd5e1; margin: 8px 0 0 0; font-size: 13px; font-weight: 500; letter-spacing: 1px; text-transform: uppercase;">Ignite Your Potential</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 48px; color: #1e293b; font-size: 15px; line-height: 1.7;">
                            <div style="white-space: pre-wrap; font-size: 15px; color: #334155; font-weight: 500;">{!! $emailBody !!}</div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 30px 48px; background-color: #f8fafc; border-top: 1px solid #f1f5f9;">
                            <p style="margin: 0; font-size: 12px; color: #94a3b8; font-weight: 500;">
                                Anda menerima email ini karena Anda memiliki pendaftaran tertunda di platform kami.
                            </p>
                            <p style="margin: 6px 0 0 0; font-size: 12px; color: #94a3b8; font-weight: 600;">
                                &copy; {{ date('Y') }} Drastha Learning. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
