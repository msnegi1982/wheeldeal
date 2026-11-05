@if (session('status'))
    <div class="border-b border-emerald-200 bg-emerald-50" role="status" aria-live="polite">
        <div class="container-page flex items-start gap-3 py-3.5">
            <x-wd-icon name="check-circle" size="sm" class="mt-0.5 text-emerald-700" />
            <p class="text-sm font-semibold leading-6 text-emerald-900">{{ session('status') }}</p>
        </div>
    </div>
@endif

@if (session('error'))
    <div class="border-b border-red-200 bg-red-50" role="alert">
        <div class="container-page flex items-start gap-3 py-3.5">
            <x-wd-icon name="alert" size="sm" class="mt-0.5 text-red-700" />
            <p class="text-sm font-semibold leading-6 text-red-900">{{ session('error') }}</p>
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="border-b border-red-200 bg-red-50" role="alert" aria-live="assertive">
        <div class="container-page py-4">
            <div class="flex items-start gap-3">
                <x-wd-icon name="alert" size="sm" class="mt-0.5 text-red-700" />
                <div>
                    <p class="text-sm font-black text-red-900">Please check the highlighted fields.</p>
                    <ul class="mt-1.5 list-disc space-y-1 pl-5 text-sm font-medium text-red-800">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endif
