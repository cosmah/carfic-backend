<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Password Reset OTP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #f8fafc;
        }
        .header {
            background-color: #1e40af;
            color: white;
            padding: 24px 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #fff;
            padding: 32px 20px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 8px rgba(30,64,175,0.07);
        }
        .otp {
            font-size: 2.5em;
            font-weight: bold;
            color: #1e40af;
            letter-spacing: 6px;
            margin: 24px 0;
        }
        .footer {
            font-size: 12px;
            color: #666;
            text-align: center;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Password Reset Request</h1>
    </div>

    <div class="content">
        <p>Hello,</p>
        <p>We received a request to reset your password. Use the OTP below to proceed:</p>
        <div class="otp">{{ $otp }}</div>
        <p>This OTP will expire in <strong>5 minutes</strong>.</p>
        <p>If you did not request a password reset, you can safely ignore this email.</p>
    </div>

    <div class="footer">
        <p>
            &copy; {{ date('Y') }} Carfic. All rights reserved.
        </p>
    </div>
</body>
</html>
