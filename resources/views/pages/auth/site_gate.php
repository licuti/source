<!DOCTYPE html>
<html>

<head>
    <title>Yêu cầu mật khẩu</title>
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
    <style>
        body {
            background: #f4f7f6;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            font-family: 'Inter', sans-serif;
        }

        .wp-fix {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }

        h1 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #333;
        }

        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-bottom: 15px;
            box-sizing: border-box;
        }

        .btn-enter {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-enter:hover {
            background: #0056b3;
        }
    </style>
</head>

<body>
    <div class="wp-fix">
        <h1>Vui lòng nhập mật khẩu truy cập</h1>
        <form method="POST" action="">
            <input type="text" name="xac_nhan" placeholder="Mật khẩu" required autofocus>
            <button type="submit" class="btn-enter">Đăng nhập</button>
        </form>
    </div>
</body>

</html>