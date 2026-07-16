<!-- gitnexus:start -->
# GitNexus — Code Intelligence

This project is indexed by GitNexus as **source** (6484 symbols, 17245 relationships, 300 execution flows). Use the GitNexus MCP tools to understand code, assess impact, and navigate safely.

> If any GitNexus tool warns the index is stale, run `npx gitnexus analyze` in terminal first.

## Always Do

- **MUST run impact analysis before editing any symbol.** Before modifying a function, class, or method, run `gitnexus_impact({target: "symbolName", direction: "upstream"})` and report the blast radius (direct callers, affected processes, risk level) to the user.
- **MUST run `gitnexus_detect_changes()` before committing** to verify your changes only affect expected symbols and execution flows.
- **MUST warn the user** if impact analysis returns HIGH or CRITICAL risk before proceeding with edits.
- When exploring unfamiliar code, use `gitnexus_query({query: "concept"})` to find execution flows instead of grepping. It returns process-grouped results ranked by relevance.
- When you need full context on a specific symbol — callers, callees, which execution flows it participates in — use `gitnexus_context({name: "symbolName"})`.

## Never Do

- NEVER edit a function, class, or method without first running `gitnexus_impact` on it.
- NEVER ignore HIGH or CRITICAL risk warnings from impact analysis.
- NEVER rename symbols with find-and-replace — use `gitnexus_rename` which understands the call graph.
- NEVER commit changes without running `gitnexus_detect_changes()` to check affected scope.

## Resources

| Resource | Use for |
|----------|---------|
| `gitnexus://repo/source/context` | Codebase overview, check index freshness |
| `gitnexus://repo/source/clusters` | All functional areas |
| `gitnexus://repo/source/processes` | All execution flows |
| `gitnexus://repo/source/process/{name}` | Step-by-step execution trace |

## CLI

| Task | Read this skill file |
|------|---------------------|
| Understand architecture / "How does X work?" | `.claude/skills/gitnexus/gitnexus-exploring/SKILL.md` |
| Blast radius / "What breaks if I change X?" | `.claude/skills/gitnexus/gitnexus-impact-analysis/SKILL.md` |
| Trace bugs / "Why is X failing?" | `.claude/skills/gitnexus/gitnexus-debugging/SKILL.md` |
| Rename / extract / split / refactor | `.claude/skills/gitnexus/gitnexus-refactoring/SKILL.md` |
| Tools, resources, schema reference | `.claude/skills/gitnexus/gitnexus-guide/SKILL.md` |
| Index, status, clean, wiki CLI commands | `.claude/skills/gitnexus/gitnexus-cli/SKILL.md` |

<!-- gitnexus:end -->

---

# 📐 Quy Tắc Lập Trình Dự Án (Project Coding Standards)

> **BẮT BUỘC ĐỌC TRƯỚC KHI CODE BẤT KỲ FILE NÀO.**
> Dự án này là Custom MVC lấy cảm hứng từ Laravel. Mọi code phải tuân theo tư duy Laravel — không được viết kiểu PHP thuần thủ tục.

## 1. Transaction — Phải dùng DB Facade

**KHÔNG BAO GIỜ** được viết:
```php
$pdo = SomeModel::getConnection();
$pdo->beginTransaction();
```

**PHẢI** dùng:
```php
use App\Core\Database\DB;

// Luôn ưu tiên cách này (ngắn, sạch, tự động rollback)
DB::transaction(function () use ($data) {
    SomeModel::create($data);
    OtherModel::updateOrCreate([...], [...]);
});
```

## 2. Module Admin — Tuân theo Blueprint chuẩn

Mỗi khi viết hoặc sửa một Admin Controller, **BẮT BUỘC đọc Skill Blueprint trước:**
- **Skill file:** `.agents/skills/admin-module-blueprint/SKILL.md`
- Blueprint này định nghĩa: cấu trúc method, cách dùng Transaction, DRY helpers, eager loading, v.v.

## 3. Helper dùng chung — Đặt vào BaseAdminController

Nếu một đoạn logic được dùng ở **2 Controller trở lên**, **KHÔNG ĐƯỢC** copy-paste. Phải:
1. Tạo `protected function` trong `BaseAdminController`
2. Các Controller con gọi `$this->methodName()`

Các helper đã có sẵn trong `BaseAdminController`:
- `$this->getLangName(string $code)` — lấy tên ngôn ngữ
- `$this->getActiveModules()` — lấy danh sách module đang bật, đã sort

## 4. Eager Loading — KHÔNG để N+1 Query

**KHÔNG BAO GIỜ** query trong vòng lặp:
```php
// SAI — N+1 problem
foreach ($items as $item) {
    $item->translations = TranslationModel::where('id', $item->id)->get();
}
```

**PHẢI** dùng eager loading:
```php
SomeModel::query()->with('translations')->get();
// hoặc khi paginate:
SomeModel::query()->with('translations')->paginate($limit, $page);
```

## 5. Validation — Luôn dùng FormRequest

**KHÔNG** validate trong Controller. **PHẢI** tạo file riêng:
- Location: `app/Requests/Admin/SomeModelRequest.php`
- Extend: `App\Core\FormRequest`

## 6. View & Data — Không truyền dữ liệu "raw" phức tạp

- View **KHÔNG** nhận mảng lồng nhau 3 cấp trở lên.
- View nhận Model object và tự truy cập quan hệ qua `$item->relation`.
- **KHÔNG** tạo biến `$translations` truyền thủ công — dùng `$item->translations`.

## 7. PHP Compatibility

- Code phải tương thích tối thiểu **PHP 7.4**.
- Không dùng: `match()`, Nullsafe `?->`, Union Types, Named Arguments, Constructor Promotion.
- **Được dùng:** Arrow functions `fn()`, Typed Properties, `??=`, `...spread`.
