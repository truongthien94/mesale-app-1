{{-- Block HTML/Văn bản tùy chỉnh do Admin tạo trong trình dựng trang --}}
@if(!empty(trim($s['content'] ?? '')))
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 mt-16">
    {!! $s['content'] !!}
</div>
@endif
