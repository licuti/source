<?php

namespace App\Core;

/**
 * ExceptionHandler
 * Xử lý và hiển thị lỗi — trang debug đẹp khi debug=true, trang 500 khi production.
 */
class ExceptionHandler
{
    public static function handle(\Throwable $e)
    {
        // Xử lý riêng cho lỗi HTTP 404 (Not Found)
        if ($e instanceof \App\Exceptions\HttpException && $e->getCode() == 404) {
            self::handle404();
            exit;
        }

        // Xử lý riêng cho lỗi Validation
        if ($e instanceof \App\Exceptions\ValidationException) {
            $request = \App\Core\App::getInstance()->request;
            
            // Xả output buffer nếu đang có
            while (ob_get_level()) { ob_end_clean(); }

            if ($request->expectsJson()) {
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ.',
                    'errors' => $e->errors()
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Fallback: Redirect back
            $referer = $_SERVER['HTTP_REFERER'] ?? '/';
            header("Location: $referer");
            exit;
        }

        // Xử lý riêng cho lỗi CSRF
        if ($e instanceof \App\Exceptions\TokenMismatchException) {
            $request = \App\Core\App::getInstance()->request;
            while (ob_get_level()) { ob_end_clean(); }

            if ($request->expectsJson() || $request->isAjax()) {
                http_response_code(419);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            self::renderErrorPage(
                419,
                'Trang đã hết hạn',
                'Phiên làm việc của bạn đã kết thúc vì lý do bảo mật. Vui lòng tải lại trang và thử lại.'
            );
            exit;
        }

        // Ghi log lỗi hệ thống
        (new Logger())->error($e->getMessage() . "\n" . $e->getTraceAsString());

        // Xả output buffer nếu đang có
        while (ob_get_level()) {
            ob_end_clean();
        }

        http_response_code(500);

        // Kiểm tra debug mode — dùng hằng số trực tiếp từ config() nếu có,
        // fallback về true nếu config chưa khởi tạo (để luôn thấy lỗi khi dev)
        $isDebug = true;
        try {
            $isDebug = (bool) config('debug', true);
        } catch (\Throwable $ignored) {}

        if ($isDebug) {
            echo self::renderDebugPage($e);
        } else {
            self::renderErrorPage();
        }

        exit;
    }

    // ─────────────────────────────────────────────
    // Xử lý lỗi 404 & Redirect 301
    // ─────────────────────────────────────────────
    private static function handle404()
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($requestUri, PHP_URL_PATH);
        $checkUrl = '/' . ltrim($path, '/');

        http_response_code(404);
        try {
            echo view('pages/404', ['com' => trim($path, '/')]);
        } catch (\Throwable $e) {
            echo "<h1>404 - Không tìm thấy trang</h1>";
        }
    }

    // ─────────────────────────────────────────────
    // Trang Debug (Development)
    // ─────────────────────────────────────────────
    private static function renderDebugPage(\Throwable $e): string
    {
        $type    = get_class($e);
        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $file    = htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8');
        $line    = $e->getLine();
        $trace   = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');

        // Lấy đoạn code quanh dòng lỗi
        $snippet = self::getCodeSnippet($e->getFile(), $e->getLine());

        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>⚠ Application Error</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', system-ui, sans-serif; background: #0f0f13; color: #e2e8f0; min-height: 100vh; }

  .header { background: linear-gradient(135deg, #dc2626, #991b1b); padding: 28px 40px; display:flex; align-items:center; gap:16px; }
  .header .icon { font-size: 36px; }
  .header h1 { font-size: 22px; font-weight: 700; color: #fff; }
  .header .type { font-size: 13px; color: #fca5a5; margin-top: 4px; font-family: monospace; }

  .container { max-width: 1100px; margin: 32px auto; padding: 0 24px; display: flex; flex-direction: column; gap: 20px; }

  .card { background: #1e1e2e; border: 1px solid #2d2d44; border-radius: 10px; overflow: hidden; }
  .card-header { background: #252535; padding: 12px 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; border-bottom: 1px solid #2d2d44; }
  .card-body { padding: 20px; }

  .message-box { font-size: 18px; font-weight: 600; color: #f87171; line-height: 1.6; word-break: break-word; }

  .location { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
  .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
  .badge-file { background: #1e3a5f; color: #60a5fa; font-family: monospace; word-break: break-all; }
  .badge-line { background: #3b2a1e; color: #fb923c; font-family: monospace; }

  .snippet-wrap { overflow-x: auto; }
  table.snippet { width: 100%; border-collapse: collapse; font-family: 'Cascadia Code', 'Fira Code', monospace; font-size: 13px; line-height: 1.7; }
  table.snippet td { padding: 1px 12px; white-space: pre; }
  table.snippet td.ln { color: #4a5568; text-align: right; user-select: none; min-width: 44px; border-right: 2px solid #2d2d44; padding-right: 14px; }
  table.snippet tr.active { background: #3b1a1a; }
  table.snippet tr.active td.ln { color: #f87171; border-color: #dc2626; font-weight: bold; }
  table.snippet tr.active td.code { color: #fca5a5; }
  table.snippet tr:not(.active) td.code { color: #a0aec0; }

  .trace-box { font-family: 'Cascadia Code', 'Fira Code', monospace; font-size: 12.5px; line-height: 1.9; color: #94a3b8; white-space: pre-wrap; word-break: break-word; }
  .trace-box .frame-num { color: #6366f1; font-weight: bold; }
  .trace-box .frame-file { color: #60a5fa; }
  .trace-box .frame-fn { color: #34d399; }

  .footer { text-align: center; color: #4a5568; font-size: 12px; padding: 24px; }
</style>
</head>
<body>

<div class="header">
  <div class="icon">💥</div>
  <div>
    <h1>Application Error</h1>
    <div class="type">{$type}</div>
  </div>
</div>

<div class="container">

  <div class="card">
    <div class="card-header">🔴 Error Message</div>
    <div class="card-body">
      <div class="message-box">{$message}</div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">📍 Location</div>
    <div class="card-body location">
      <span class="badge badge-file">{$file}</span>
      <span class="badge badge-line">Line {$line}</span>
    </div>
  </div>

  <div class="card">
    <div class="card-header">📄 Code Snippet</div>
    <div class="card-body snippet-wrap">
      <table class="snippet">{$snippet}</table>
    </div>
  </div>

  <div class="card">
    <div class="card-header">📋 Stack Trace</div>
    <div class="card-body">
      <div class="trace-box">{$trace}</div>
    </div>
  </div>

</div>

<div class="footer">Hiển thị vì <code>debug = true</code>. Đổi thành <code>false</code> trước khi deploy production.</div>

</body>
</html>
HTML;
    }

    // ─────────────────────────────────────────────
    // Lấy đoạn code quanh dòng lỗi
    // ─────────────────────────────────────────────
    private static function getCodeSnippet(string $file, int $errorLine, int $context = 7): string
    {
        if (!file_exists($file) || !is_readable($file)) {
            return '<tr><td class="code" colspan="2">Không thể đọc file.</td></tr>';
        }

        $lines  = file($file);
        $start  = max(0, $errorLine - $context - 1);
        $end    = min(count($lines) - 1, $errorLine + $context - 1);
        $html   = '';

        for ($i = $start; $i <= $end; $i++) {
            $lineNum    = $i + 1;
            $isActive   = ($lineNum === $errorLine);
            $rowClass   = $isActive ? ' class="active"' : '';
            $code       = htmlspecialchars($lines[$i], ENT_QUOTES, 'UTF-8');
            $html      .= "<tr{$rowClass}><td class=\"ln\">{$lineNum}</td><td class=\"code\">{$code}</td></tr>";
        }

        return $html;
    }

    // ─────────────────────────────────────────────
    // Trang lỗi thân thiện (Production)
    // ─────────────────────────────────────────────
    private static function renderErrorPage($code = 500, $title = 'Lỗi hệ thống', $desc = 'Đã có lỗi xảy ra. Vui lòng thử lại sau.'): void
    {
        echo <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>{$code} - {$title}</title>
<style>
  body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; display:flex; align-items:center; justify-content:center; min-height:100vh; }
  .box { text-align:center; padding: 48px; }
  h1 { font-size: 72px; font-weight: 800; color: #dc2626; }
  h2 { font-size: 24px; color: #333; margin-top: 10px; }
  p { color: #64748b; font-size: 18px; margin-top: 12px; }
  a.btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #0ea5e9; color: #fff; text-decoration: none; border-radius: 5px; }
</style>
</head>
<body>
  <div class="box">
    <h1>{$code}</h1>
    <h2>{$title}</h2>
    <p>{$desc}</p>
    <a href="javascript:history.back()" class="btn">Quay lại</a>
  </div>
</body>
</html>
HTML;
    }
}
