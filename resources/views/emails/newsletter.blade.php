<!-- resources/views/emails/newsletter.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $newsletter->title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #1e40af;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
        }
        .footer {
            font-size: 12px;
            color: #666;
            text-align: center;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        .unsubscribe {
            color: #666;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $newsletter->title }}</h1>
    </div>

    <div class="content">
        {!! $newsletter->content !!}
    </div>

    <div class="footer">
        <p>
            You're receiving this email because you subscribed to our newsletter.
            <br>
            <a href="{{ url('/api/newsletter/unsubscribe?email=' . urlencode($newsletter->recipient_email ?? '')) }}" class="unsubscribe">
                Unsubscribe
            </a>
        </p>
    </div>
</body>
</html>
