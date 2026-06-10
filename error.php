<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oops – Foodify</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&family=Playfair+Display:wght@900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #F8F9FA; margin: 0;
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; text-align: center; padding: 2rem;
        }
        .wrap { max-width: 420px; }
        .logo {
            font-family: 'Playfair Display', serif; font-weight: 900;
            font-size: 2.5rem;
            background: linear-gradient(135deg, #FF6B6B, #FF8E53);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: 2rem;
        }
        .emoji { font-size: 4rem; margin-bottom: 1rem; }
        h1 { font-weight: 800; font-size: 1.5rem; color: #1A1C1E; margin-bottom: 0.5rem; }
        p { color: #7f8c8d; font-size: 0.95rem; line-height: 1.6; margin-bottom: 2rem; }
        a {
            background: linear-gradient(135deg, #FF6B6B, #FF8E53);
            color: white; text-decoration: none;
            padding: 14px 32px; border-radius: 16px;
            font-weight: 800; font-size: 0.9rem;
            display: inline-block; transition: 0.3s;
        }
        a:hover { opacity: 0.88; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="logo">foodify.</div>
        <h1>Something went wrong</h1>
        <p>We're working on fixing this.<br>Please try again in a moment.</p>
        <a href="/">Back to Home</a>
    </div>
</body>
</html>