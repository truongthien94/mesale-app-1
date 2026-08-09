<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Translation;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class LanguageController extends Controller
{
    /**
     * Hiển thị danh sách các ngôn ngữ trong Admin Panel.
     * Hỗ trợ sắp xếp theo thứ tự hiển thị và ngày tạo.
     */
    public function index()
    {
        $languages = Language::orderBy('order', 'asc')->orderBy('created_at', 'desc')->get();
        return view('admin.languages.index', compact('languages'));
    }

    /**
     * Lưu ngôn ngữ mới vào hệ thống.
     * Tự động tạo tệp dịch JSON tương ứng và dọn dẹp cache.
     */
    public function store(Request $request)
    {
        // 1. Xác thực dữ liệu đầu vào
        $request->validate([
            'name' => 'required|string|max:100|unique:languages,name',
            'code' => 'required|string|max:10|alpha_dash|unique:languages,code',
            'flag' => 'nullable|string|max:255',
            'flag_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'order' => 'nullable|integer|min:0',
        ], [
            'name.required' => 'Tên ngôn ngữ không được để trống.',
            'name.unique' => 'Tên ngôn ngữ này đã tồn tại.',
            'code.required' => 'Mã ngôn ngữ không được để trống.',
            'code.unique' => 'Mã ngôn ngữ này đã được sử dụng.',
            'code.alpha_dash' => 'Mã ngôn ngữ chỉ được chứa chữ cái, số, dấu gạch ngang và gạch dưới.',
            'flag_file.image' => 'Lá cờ tải lên phải là định dạng hình ảnh.',
            'flag_file.max' => 'Dung lượng ảnh lá cờ không được vượt quá 2MB.',
        ]);

        $data = [
            'name' => $request->name,
            'code' => strtolower($request->code),
            'order' => $request->input('order', 0),
            'is_active' => $request->has('is_active'),
            'is_default' => $request->has('is_default'),
        ];

        // 2. Xử lý tải lên tệp ảnh lá cờ
        if ($request->hasFile('flag_file')) {
            $file = $request->file('flag_file');
            
            // Lấy extension an toàn
            $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
            if (in_array($extension, ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps', 'phar', 'htaccess', 'js'])) {
                $extension = 'png';
            }

            $fileName = 'flag_' . strtolower($request->code) . '_' . time() . '.' . $extension;
            // Tạo thư mục lưu trữ nếu chưa có
            $uploadPath = public_path('uploads/flags');
            if (!File::exists($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true);
            }
            $file->move($uploadPath, $fileName);
            $data['flag'] = '/uploads/flags/' . $fileName;
        } elseif ($request->filled('flag')) {
            // Nhận ảnh từ thư viện CKFinder
            $data['flag'] = $request->flag;
        } else {
            $data['flag'] = '/assets/flags/' . strtolower($request->code) . '.png';
        }

        // 3. Tạo ngôn ngữ trong Database
        $language = Language::create($data);

        // 4. Nghiệp vụ mở rộng: Tự động khởi tạo tệp JSON dịch tương ứng để tránh lỗi hệ thống
        $langPath = base_path('lang');
        if (!File::exists($langPath)) {
            File::makeDirectory($langPath, 0755, true);
        }
        $jsonFile = $langPath . '/' . $language->code . '.json';
        if (!File::exists($jsonFile)) {
            File::put($jsonFile, json_encode([
                "Trang chủ" => "Home",
                "Blog" => "Blog",
                "Điểm danh" => "Check-in",
                "Giới thiệu" => "Referral",
                "Rút tiền" => "Withdraw",
                "Ví cá nhân" => "My Wallet",
                "Đăng nhập" => "Login",
                "Đăng ký" => "Register"
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        // 5. Xóa cache ngôn ngữ để hệ thống cập nhật tức thì
        Cache::forget('system_default_locale');
        Cache::forget('active_languages_list');

        // 6. Ghi nhật ký hoạt động của quản trị viên
        ActivityLog::log("Thêm mới ngôn ngữ thành công: {$language->name} ({$language->code})", auth()->id());

        return redirect()->route('admin.languages.index')->with('success', 'Thêm mới ngôn ngữ thành công!');
    }

    /**
     * Cập nhật thông tin ngôn ngữ hiện có.
     * Xử lý thay đổi file ảnh lá cờ, rename file JSON dịch cũ và dọn dẹp cache.
     */
    public function update(Request $request, Language $language)
    {
        // 1. Xác thực dữ liệu cập nhật
        $request->validate([
            'name' => 'required|string|max:100|unique:languages,name,' . $language->id,
            'code' => 'required|string|max:10|alpha_dash|unique:languages,code,' . $language->id,
            'flag' => 'nullable|string|max:255',
            'flag_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'order' => 'nullable|integer|min:0',
        ], [
            'name.required' => 'Tên ngôn ngữ không được để trống.',
            'name.unique' => 'Tên ngôn ngữ này đã tồn tại.',
            'code.required' => 'Mã ngôn ngữ không được để trống.',
            'code.unique' => 'Mã ngôn ngữ này đã được sử dụng.',
            'code.alpha_dash' => 'Mã ngôn ngữ chỉ được chứa chữ cái, số, dấu gạch ngang và gạch dưới.',
            'flag_file.image' => 'Lá cờ tải lên phải là định dạng hình ảnh.',
            'flag_file.max' => 'Dung lượng ảnh lá cờ không được vượt quá 2MB.',
        ]);

        $oldCode = $language->code;
        $newCode = strtolower($request->code);

        $data = [
            'name' => $request->name,
            'code' => $newCode,
            'order' => $request->input('order', 0),
            'is_active' => $request->has('is_active'),
            'is_default' => $request->has('is_default'),
        ];

        // 2. Bảo vệ hệ thống: Không cho phép vô hiệu hóa hoặc tắt trạng thái mặc định của ngôn ngữ mặc định duy nhất
        if ($language->is_default) {
            $data['is_active'] = true;
            $data['is_default'] = true;
        }

        // 3. Xử lý ảnh lá cờ mới
        if ($request->hasFile('flag_file')) {
            // Xóa ảnh cũ nếu có
            if ($language->flag && File::exists(public_path($language->flag)) && str_contains($language->flag, '/uploads/flags/')) {
                File::delete(public_path($language->flag));
            }

            $file = $request->file('flag_file');
            
            // Lấy extension an toàn
            $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
            if (in_array($extension, ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps', 'phar', 'htaccess', 'js'])) {
                $extension = 'png';
            }

            $fileName = 'flag_' . $newCode . '_' . time() . '.' . $extension;
            $uploadPath = public_path('uploads/flags');
            if (!File::exists($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true);
            }
            $file->move($uploadPath, $fileName);
            $data['flag'] = '/uploads/flags/' . $fileName;
        } elseif ($request->filled('flag')) {
            // Nhận ảnh từ thư viện CKFinder
            $data['flag'] = $request->flag;
        }

        // 4. Cập nhật Model ngôn ngữ trong DB
        $language->update($data);

        // 5. Cập nhật tệp JSON dịch nếu mã code thay đổi
        if ($oldCode !== $newCode) {
            $langPath = base_path('lang');
            $oldJsonFile = $langPath . '/' . $oldCode . '.json';
            $newJsonFile = $langPath . '/' . $newCode . '.json';

            if (File::exists($oldJsonFile)) {
                File::move($oldJsonFile, $newJsonFile);
            } else {
                File::put($newJsonFile, json_encode([
                    "Trang chủ" => "Home",
                    "Blog" => "Blog",
                    "Điểm danh" => "Check-in"
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }
        }

        // 6. Xóa cache hệ thống liên quan
        Cache::forget('system_default_locale');
        Cache::forget('active_languages_list');

        // 7. Ghi nhật ký hoạt động
        ActivityLog::log("Cập nhật ngôn ngữ ID {$language->id}: {$language->name} ({$language->code})", auth()->id());

        return redirect()->route('admin.languages.index')->with('success', 'Cập nhật ngôn ngữ thành công!');
    }

    /**
     * Xóa ngôn ngữ khỏi hệ thống.
     * Ngăn chặn việc xóa ngôn ngữ mặc định.
     */
    public function destroy(Language $language)
    {
        // Ràng buộc bảo mật: Không được xóa ngôn ngữ mặc định để tránh hệ thống bị mất locale gốc
        if ($language->is_default) {
            return redirect()->route('admin.languages.index')->with('error', 'Không thể xóa ngôn ngữ mặc định của hệ thống.');
        }

        // Xóa file ảnh lá cờ đại diện
        if ($language->flag && File::exists(public_path($language->flag)) && str_contains($language->flag, '/uploads/flags/')) {
            File::delete(public_path($language->flag));
        }

        // Xóa tệp dịch JSON tương ứng nếu muốn (Ở đây chọn giữ lại tệp dịch để tránh mất bản dịch đã làm, hoặc xóa đi)
        $jsonFile = base_path('lang') . '/' . $language->code . '.json';
        if (File::exists($jsonFile)) {
            File::delete($jsonFile);
        }

        $langName = $language->name;
        $langCode = $language->code;
        $language->delete();

        // Xóa cache hệ thống liên quan
        Cache::forget('system_default_locale');
        Cache::forget('active_languages_list');

        // Ghi nhật ký hoạt động
        ActivityLog::log("Xóa ngôn ngữ khỏi hệ thống: {$langName} ({$langCode})", auth()->id());

        return redirect()->route('admin.languages.index')->with('success', 'Xóa ngôn ngữ thành công!');
    }

    /**
     * Hiển thị danh sách bản dịch của một ngôn ngữ cụ thể.
     * Hỗ trợ tìm kiếm, lọc chưa dịch và phân trang.
     */
    public function translations(Language $language, Request $request)
    {
        // Tự động đồng bộ từ file JSON vào Database nếu bảng translations của locale này trống
        $count = Translation::where('language_code', $language->code)->count();
        if ($count === 0) {
            $jsonFile = base_path('lang') . '/' . $language->code . '.json';
            if (File::exists($jsonFile)) {
                $translationsArray = json_decode(File::get($jsonFile), true);
                if (is_array($translationsArray)) {
                    foreach ($translationsArray as $key => $value) {
                        Translation::create([
                            'language_code' => $language->code,
                            'key' => $key,
                            'value' => $value
                        ]);
                    }
                }
            } else {
                // Nếu chưa có tệp JSON nào, lấy các key từ ngôn ngữ mặc định làm mẫu
                $defaultLang = Language::where('is_default', true)->first();
                if ($defaultLang && $defaultLang->code !== $language->code) {
                    $defaultTranslations = Translation::where('language_code', $defaultLang->code)->get();
                    foreach ($defaultTranslations as $dt) {
                        Translation::create([
                            'language_code' => $language->code,
                            'key' => $dt->key,
                            'value' => ''
                        ]);
                    }
                }
            }
        }

        // Truy vấn danh sách bản dịch
        $query = Translation::where('language_code', $language->code);

        // Tìm kiếm theo key hoặc value
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                  ->orWhere('value', 'like', "%{$search}%");
            });
        }

        // Lọc chưa dịch (value rỗng hoặc value bằng key)
        if ($request->input('filter') === 'untranslated') {
            $query->where(function ($q) {
                $q->whereNull('value')
                  ->orWhere('value', '')
                  ->orWhereRaw('`value` = `key`');
            });
        }

        $perPage = $request->integer('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100, 200])) {
            $perPage = 10;
        }

        $translations = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        return view('admin.languages.translations', compact('language', 'translations'));
    }

    /**
     * Cập nhật bản dịch của một key (gọi qua AJAX).
     */
    public function updateTranslation(Request $request, Language $language)
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'nullable|string'
        ]);

        Translation::updateOrCreate(
            [
                'language_code' => $language->code,
                'key' => $request->key
            ],
            [
                'value' => $request->value
            ]
        );

        // Ghi lại tệp JSON tương ứng ngay lập tức
        $this->writeJsonFile($language->code);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật bản dịch thành công!'
        ]);
    }

    /**
     * Xóa một key dịch (gọi qua AJAX hoặc form).
     */
    public function deleteTranslation(Request $request, Language $language)
    {
        $request->validate([
            'key' => 'required|string'
        ]);

        Translation::where('language_code', $language->code)
            ->where('key', $request->key)
            ->delete();

        // Cập nhật lại tệp JSON dịch
        $this->writeJsonFile($language->code);

        return response()->json([
            'success' => true,
            'message' => 'Xóa bản dịch thành công!'
        ]);
    }

    /**
     * Dịch tự động từ Tiếng Việt sang ngôn ngữ đích qua Google Translate API miễn phí.
     */
    public function autoTranslate(Request $request, Language $language)
    {
        $request->validate([
            'text' => 'required|string'
        ]);

        $text = $request->text;
        $target = $language->code;
        $sl = 'vi'; // Ngôn ngữ nguồn mặc định là Tiếng Việt

        $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=" . urlencode($sl) . "&tl=" . urlencode($target) . "&dt=t&q=" . urlencode($text);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        $translatedText = '';
        if (is_array($result) && isset($result[0])) {
            foreach ($result[0] as $item) {
                if (isset($item[0])) {
                    $translatedText .= $item[0];
                }
            }
        }

        // Cập nhật vào CSDL
        Translation::updateOrCreate(
            [
                'language_code' => $language->code,
                'key' => $text
            ],
            [
                'value' => $translatedText
            ]
        );

        // Ghi lại tệp JSON dịch
        $this->writeJsonFile($language->code);

        return response()->json([
            'success' => true,
            'translated_text' => $translatedText
        ]);
    }

    /**
     * Đồng bộ bản dịch từ CSDL ra tệp JSON tĩnh.
     */
    public function syncFileTranslations(Language $language)
    {
        $this->writeJsonFile($language->code);

        ActivityLog::log("Đồng bộ bản dịch từ CSDL ra tệp JSON: {$language->name} ({$language->code})", auth()->id());

        return redirect()->back()->with('success', 'Đồng bộ bản dịch ra tệp JSON thành công!');
    }

    /**
     * Tạo lại bản dịch bằng cách quét toàn bộ mã nguồn tìm kiếm chuỗi dịch mới.
     */
    public function rebuildTranslations(Language $language)
    {
        $paths = [
            resource_path('views'),
            app_path()
        ];
        $foundKeys = [];

        foreach ($paths as $path) {
            if (!is_dir($path)) continue;
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
            foreach ($files as $file) {
                if ($file->isDir() || !in_array($file->getExtension(), ['php', 'blade'])) continue;
                $content = file_get_contents($file->getPathname());

                // Sử dụng Regex quét: __('...') hoặc trans('...') hoặc @lang('...')
                preg_match_all('/(?:__\(|trans\(|@lang\()\s*[\'"]([^\'"]+)[\'"]\s*\)/U', $content, $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $key) {
                        $key = trim($key);
                        // Loại bỏ key rỗng hoặc key chứa biến động php ($)
                        if ($key !== '' && !str_contains($key, '$')) {
                            $foundKeys[] = $key;
                        }
                    }
                }
            }
        }

        $foundKeys = array_unique($foundKeys);
        $addedCount = 0;

        foreach ($foundKeys as $key) {
            $exists = Translation::where('language_code', $language->code)->where('key', $key)->exists();
            if (!$exists) {
                Translation::create([
                    'language_code' => $language->code,
                    'key' => $key,
                    'value' => ''
                ]);
                $addedCount++;
            }
        }

        // Cập nhật lại tệp JSON dịch
        $this->writeJsonFile($language->code);

        ActivityLog::log("Quét mã nguồn và đồng bộ {$addedCount} chuỗi dịch cho ngôn ngữ {$language->name} ({$language->code})", auth()->id());

        return redirect()->back()->with('success', "Quét mã nguồn thành công! Đã thêm {$addedCount} chuỗi dịch mới.");
    }

    protected function writeJsonFile($locale)
    {
        $translations = Translation::where('language_code', $locale)
            ->get(['key', 'value'])
            ->pluck('value', 'key')
            ->toArray();

        $cleanTranslations = [];
        foreach ($translations as $key => $val) {
            $cleanTranslations[$key] = $val ?? '';
        }

        $langPath = base_path('lang');
        if (!File::exists($langPath)) {
            File::makeDirectory($langPath, 0755, true);
        }

        $jsonPath = $langPath . '/' . $locale . '.json';
        File::put($jsonPath, json_encode($cleanTranslations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
