<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\Contracts\AIDriverInterface;

/**
 * Lớp trừu tượng (Abstract Class) đại diện cho lớp cơ sở của các AI Drivers.
 * Nơi chứa các thuộc tính hoặc helper dùng chung cho các Driver cụ thể sau này.
 */
abstract class AbstractDriver implements AIDriverInterface
{
    // Lớp cơ sở dùng để mở rộng thêm các tính năng dùng chung (như format prompt, filter response, v.v.) khi hệ thống mở rộng
}
