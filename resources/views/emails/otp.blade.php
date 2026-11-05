<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode OTP Reset Kata Sandi</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9; padding: 40px 15px;">
        <tr>
            <td align="center">
                <!-- Kartu Utama Email -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 520px; background-color: #ffffff; border-radius: 18px; overflow: hidden; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">
                    
                    <!-- Header Kartu -->
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #090d16 0%, #1e293b 100%); padding: 35px 25px 30px 25px; text-align: center;">
                            <div style="display: inline-block; background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); color: #ffffff; font-weight: 900; font-size: 22px; width: 55px; height: 55px; line-height: 55px; border-radius: 14px; margin-bottom: 15px; box-shadow: 0 6px 16px rgba(79, 70, 229, 0.4);">
                                SCP
                            </div>
                            <h1 style="margin: 0; color: #ffffff; font-size: 20px; font-weight: 800; letter-spacing: -0.5px;">
                                Verifikasi Reset Kata Sandi
                            </h1>
                            <p style="margin: 6px 0 0 0; color: #94a3b8; font-size: 13px; font-weight: 500;">
                                Sistem Informasi Sekolah &middot; School Class Progress
                            </p>
                        </td>
                    </tr>

                    <!-- Isi Email -->
                    <tr>
                        <td style="padding: 35px 30px;">
                            <p style="margin: 0 0 16px 0; font-size: 15px; color: #334155; line-height: 1.6;">
                                Halo, <strong style="color: #0f172a;">{{ $userName }}</strong>
                            </p>
                            <p style="margin: 0 0 25px 0; font-size: 14px; color: #64748b; line-height: 1.6;">
                                Kami menerima permintaan pengaturan ulang kata sandi untuk akun sistem sekolah Anda (<span style="color: #4f46e5; font-weight: 600;">{{ $email }}</span>). Gunakan kode OTP di bawah ini untuk melanjutkan:
                            </p>

                            <!-- Kotak Kode OTP -->
                            <div style="background-color: #f8fafc; border: 2px dashed #6366f1; border-radius: 14px; padding: 22px 15px; text-align: center; margin-bottom: 25px;">
                                <span style="display: block; font-size: 11px; font-weight: 800; letter-spacing: 1.5px; color: #6366f1; text-transform: uppercase; margin-bottom: 6px;">
                                    Kode OTP Anda
                                </span>
                                <div style="font-family: 'Courier New', Courier, monospace; font-size: 38px; font-weight: 900; letter-spacing: 10px; color: #1e1b4b; padding-left: 10px;">
                                    {{ $otp }}
                                </div>
                                <span style="display: inline-block; margin-top: 10px; background-color: #fef3c7; color: #92400e; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 6px;">
                                    Berlaku selama 15 menit
                                </span>
                            </div>

                            <!-- Peringatan Keamanan -->
                            <div style="background-color: #fef2f2; border-left: 4px solid #ef4444; border-radius: 8px; padding: 12px 16px; margin-bottom: 25px;">
                                <p style="margin: 0; font-size: 12px; color: #991b1b; line-height: 1.5;">
                                    <strong>Peringatan Keamanan:</strong> Jangan berikan kode OTP ini kepada siapa pun, termasuk pihak yang mengatasnamakan administrator sekolah. Jika Anda tidak merasa melakukan permintaan ini, abaikan email ini.
                                </p>
                            </div>

                            <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.6;">
                                Salam hangat,<br>
                                <strong style="color: #1e293b;">Tim Administrator SCP Sekolah</strong>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer Email -->
                    <tr>
                        <td align="center" style="background-color: #f8fafc; padding: 20px; border-top: 1px solid #e2e8f0; text-align: center;">
                            <p style="margin: 0; font-size: 11px; color: #94a3b8;">
                                Email ini dikirimkan secara otomatis oleh Sistem SCP. Mohon tidak membalas email ini.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
