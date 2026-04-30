<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>New message from {{ $senderName }}</title>
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
    <style>
        * { -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; }
        body { margin: 0 !important; padding: 0 !important; background-color: #0a0a0a; }
        table { border-spacing: 0; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        td { border-collapse: collapse; }
        img { border: 0; display: block; line-height: 100%; }
        a { color: #64ffda; text-decoration: none; }
        @media only screen and (max-width: 620px) {
            .container { width: 100% !important; }
            .pad { padding: 24px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#0a0a0a;">

    {{-- Preview text --}}
    <div style="display:none;max-height:0;overflow:hidden;font-size:1px;color:#0a0a0a;">New contact from {{ $senderName }} ({{ $senderEmail }}) — {{ \Illuminate\Support\Str::limit($senderMessage, 90) }}</div>
    <div style="display:none;max-height:0;overflow:hidden;">&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#0a0a0a;">
        <tr>
            <td align="center" style="padding:40px 20px;">

                <table class="container" width="600" cellpadding="0" cellspacing="0" role="presentation" style="width:600px;max-width:600px;">

                    {{-- Logo --}}
                    <tr>
                        <td style="padding-bottom:28px;">
                            <table cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td style="font-family:Arial,Helvetica,sans-serif;font-size:20px;font-weight:700;color:#ffffff;letter-spacing:-0.5px;">
                                        MASIAS
                                    </td>
                                    <td width="10"></td>
                                    <td>
                                        {{-- Cyan dot: VML for Outlook, HTML for others --}}
                                        <!--[if mso]>
                                        <v:oval xmlns:v="urn:schemas-microsoft-com:vml" style="width:7px;height:7px;position:relative;top:1px;" fillcolor="#64ffda" stroked="f">
                                        </v:oval>
                                        <![endif]-->
                                        <!--[if !mso]><!-->
                                        <div style="width:7px;height:7px;background-color:#64ffda;border-radius:50%;display:inline-block;"></div>
                                        <!--<![endif]-->
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Card --}}
                    <tr>
                        <td class="pad" style="background-color:#111111;border:1px solid #1f1f1f;padding:40px;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">

                                {{-- Label --}}
                                <tr>
                                    <td style="padding-bottom:10px;">
                                        <p style="margin:0;font-family:'Courier New',Courier,monospace;font-size:10px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#64ffda;">Contact Form</p>
                                    </td>
                                </tr>

                                {{-- Heading --}}
                                <tr>
                                    <td style="padding-bottom:32px;border-bottom:1px solid #1f1f1f;">
                                        <h1 style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:26px;font-weight:700;color:#ffffff;line-height:1.3;">New message from {{ $senderName }}</h1>
                                    </td>
                                </tr>

                                {{-- Name --}}
                                <tr><td style="padding-top:28px;padding-bottom:6px;">
                                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#555555;">Name</p>
                                </td></tr>
                                <tr><td style="padding-bottom:22px;">
                                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:16px;color:#eeeeee;">{{ $senderName }}</p>
                                </td></tr>

                                {{-- Email --}}
                                <tr><td style="padding-bottom:6px;">
                                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#555555;">Email</p>
                                </td></tr>
                                <tr><td style="padding-bottom:22px;">
                                    <a href="mailto:{{ $senderEmail }}" style="font-family:Arial,Helvetica,sans-serif;font-size:16px;color:#64ffda;text-decoration:none;">{{ $senderEmail }}</a>
                                </td></tr>

                                {{-- Message --}}
                                <tr><td style="padding-bottom:6px;">
                                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#555555;">Message</p>
                                </td></tr>
                                <tr><td style="padding-bottom:36px;">
                                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                        <tr>
                                            <td style="background-color:#0d0d0d;border:1px solid #1f1f1f;padding:20px 24px;">
                                                <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.8;color:#cccccc;white-space:pre-wrap;">{{ $senderMessage }}</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td></tr>

                                {{-- CTA --}}
                                <tr>
                                    <td style="border-top:1px solid #1f1f1f;padding-top:28px;">
                                        <table cellpadding="0" cellspacing="0" role="presentation">
                                            <tr>
                                                <td style="background-color:#64ffda;">
                                                    <a href="mailto:{{ $senderEmail }}?subject=Re%3A Your message" style="display:inline-block;padding:13px 28px;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:700;color:#080808;text-decoration:none;letter-spacing:0.5px;">Reply to {{ $senderName }}</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding-top:28px;text-align:center;">
                            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#444444;">{{ date('Y') }} &bull; Mauricio Masias</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
