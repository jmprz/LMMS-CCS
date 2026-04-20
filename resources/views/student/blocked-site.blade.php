<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Blocked</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .icon { font-size: 80px; margin-bottom: 20px; }
        h1 { font-size: 28px; font-weight: 900; color: #1a1a1a; margin-bottom: 15px; }
        p { font-size: 16px; color: #666; line-height: 1.6; margin-bottom: 20px; }
        .message {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .message strong { color: #856404; font-weight: 700; }
        .suggestions {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }
        .suggestions h3 {
            font-size: 14px;
            font-weight: 700;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 15px;
        }
        .suggestion-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }
        .suggestion-item {
            background: #f8f9fa;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            color: #667eea;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🚫</div>
        <h1>Access Blocked</h1>
        
        <div class="message">
            <strong>{{ $message ?? 'This website is not allowed during lab sessions.' }}</strong>
        </div>
        
        <p>
            This website is not on the approved whitelist for this class. 
            Your professor controls which sites you can access during lab sessions.
        </p>
        
        <p style="font-size: 14px; color: #999;">
            ⚠️ This attempt has been logged.
        </p>
        
        <div class="suggestions">
            <h3>Try These Instead:</h3>
            <div class="suggestion-list">
                <span class="suggestion-item">Google</span>
                <span class="suggestion-item">Wikipedia</span>
                <span class="suggestion-item">W3Schools</span>
                <span class="suggestion-item">Stack Overflow</span>
            </div>
        </div>
    </div>
</body>
</html>