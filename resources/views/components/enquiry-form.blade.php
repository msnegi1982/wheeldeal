@props(['vehicle' => null])

@php
    $defaultMessage = $vehicle
        ? 'I am interested in the '.($vehicle->full_name ?: $vehicle->title).'. Please share availability and the next steps.'
        : '';
@endphp

<form method="POST" action="{{ route('enquiries.store') }}" class="space-y-4">
    @csrf

    @if ($vehicle)
        <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">
    @endif

    <div class="hidden" aria-hidden="true">
        <label for="enquiry-website">Website</label>
        <input type="text" id="enquiry-website" name="website" tabindex="-1" autocomplete="off">
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="enquiry-name" class="label">Full name <span class="text-red-600" aria-hidden="true">*</span></label>
            <input
                type="text"
                id="enquiry-name"
                name="name"
                value="{{ old('name') }}"
                required
                autocomplete="name"
                class="input @error('name') border-red-500 @enderror"
                @error('name') aria-invalid="true" aria-describedby="enquiry-name-error" @enderror
            >
            @error('name')
                <p id="enquiry-name-error" class="mt-1 text-xs font-bold text-red-700" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="enquiry-phone" class="label">Mobile number <span class="text-red-600" aria-hidden="true">*</span></label>
            <input
                type="tel"
                id="enquiry-phone"
                name="phone"
                value="{{ old('phone') }}"
                required
                inputmode="tel"
                autocomplete="tel"
                placeholder="10-digit mobile number"
                class="input @error('phone') border-red-500 @enderror"
                @error('phone') aria-invalid="true" aria-describedby="enquiry-phone-error" @enderror
            >
            @error('phone')
                <p id="enquiry-phone-error" class="mt-1 text-xs font-bold text-red-700" role="alert">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="enquiry-email" class="label">Email <span class="font-medium normal-case tracking-normal text-ink-400">(optional)</span></label>
        <input
            type="email"
            id="enquiry-email"
            name="email"
            value="{{ old('email') }}"
            autocomplete="email"
            class="input @error('email') border-red-500 @enderror"
            @error('email') aria-invalid="true" aria-describedby="enquiry-email-error" @enderror
        >
        @error('email')
            <p id="enquiry-email-error" class="mt-1 text-xs font-bold text-red-700" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="enquiry-message" class="label">Message <span class="text-red-600" aria-hidden="true">*</span></label>
        <textarea
            id="enquiry-message"
            name="message"
            rows="4"
            required
            class="input resize-y @error('message') border-red-500 @enderror"
            @error('message') aria-invalid="true" aria-describedby="enquiry-message-error" @enderror
        >{{ old('message', $defaultMessage) }}</textarea>
        @error('message')
            <p id="enquiry-message-error" class="mt-1 text-xs font-bold text-red-700" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <x-captcha />

    <button type="submit" class="btn-primary w-full">
        Send enquiry
        <x-wd-icon name="arrow-right" size="sm" />
    </button>

    <p class="text-xs leading-5 text-ink-500">
        By sending an enquiry you agree to our
        <a href="{{ route('pages.privacy') }}" class="link">privacy policy</a>.
        We only use your number to respond to this enquiry.
    </p>
</form>
