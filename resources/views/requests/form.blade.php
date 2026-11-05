@extends('layouts.app')

@php
    $isSell = $type === \App\Enums\RequestType::SELL;
    $modelOptions = $models->map(fn ($model) => [
        'id' => $model->id,
        'brand_id' => $model->brand_id,
        'name' => $model->name,
    ])->values()->all();
    $currentYear = (int) date('Y') + 1;
@endphp

@section('content')
    <section class="page-hero">
        <div class="container-page py-8 lg:py-10">
            <x-breadcrumbs :items="$seo->breadcrumbs" />

            <div class="mt-4 max-w-3xl">
                <p class="eyebrow">{{ $isSell ? 'Sell with confidence' : 'A smarter car search' }}</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl lg:text-5xl">{{ $seo->h1 }}</h1>
                <p class="mt-3 text-sm leading-7 text-ink-600 sm:text-base">
                    @if ($isSell)
                        Tell us about your car and what you would like for it. We will verify your number, review the details and call you for a no-obligation valuation conversation.
                    @else
                        Share your budget, preferred body type and must-have features. We will use it to shortlist verified cars that actually fit, instead of sending you a generic list.
                    @endif
                </p>
            </div>
        </div>
    </section>

    <div class="container-page py-8 lg:py-12">
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem] xl:gap-12">
            <div class="card p-5 sm:p-7 lg:p-8">
                <div class="flex items-start gap-3 border-b border-ink-100 pb-5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-500 text-ink">
                        <x-wd-icon :name="$isSell ? 'tag' : 'search'" size="sm" />
                    </span>
                    <div>
                        <h2 class="text-lg font-black">{{ $isSell ? 'Your car details' : 'Your requirements' }}</h2>
                        <p class="mt-1 text-xs leading-5 text-ink-500">
                            Fields marked <span class="font-black text-red-600">*</span> are required. It takes about two minutes.
                        </p>
                    </div>
                </div>

                <form
                    method="POST"
                    action="{{ $isSell ? route('sell.store') : route('buy.store') }}"
                    enctype="multipart/form-data"
                    class="mt-6 space-y-7"
                    x-data="{
                        brandId: '{{ old('brand_id') }}',
                        modelId: '{{ old('vehicle_model_id') }}',
                        models: {{ Js::from($modelOptions) }},
                        get filteredModels() {
                            return this.models.filter((model) => !this.brandId || String(model.brand_id) === String(this.brandId));
                        },
                        brandChanged() {
                            if (!this.filteredModels.some((model) => String(model.id) === String(this.modelId))) {
                                this.modelId = '';
                            }
                        }
                    }"
                >
                    @csrf
                    <input type="hidden" name="type" value="{{ $type->value }}">

                    <div class="hidden" aria-hidden="true">
                        <label for="request-website">Website</label>
                        <input type="text" id="request-website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <fieldset>
                        <legend class="text-xs font-black uppercase tracking-wide text-ink-500">Your contact details</legend>

                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="request-name" class="label">Full name <span class="text-red-600" aria-hidden="true">*</span></label>
                                <input
                                    type="text"
                                    id="request-name"
                                    name="name"
                                    value="{{ old('name') }}"
                                    required
                                    autocomplete="name"
                                    class="input @error('name') border-red-500 @enderror"
                                    @error('name') aria-invalid="true" aria-describedby="request-name-error" @enderror
                                >
                                @error('name')
                                    <p id="request-name-error" class="field-error" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="request-phone" class="label">Mobile number <span class="text-red-600" aria-hidden="true">*</span></label>
                                <input
                                    type="tel"
                                    id="request-phone"
                                    name="phone"
                                    value="{{ old('phone') }}"
                                    required
                                    inputmode="tel"
                                    autocomplete="tel"
                                    placeholder="10-digit mobile number"
                                    class="input @error('phone') border-red-500 @enderror"
                                    @error('phone') aria-invalid="true" aria-describedby="request-phone-error" @enderror
                                >
                                @error('phone')
                                    <p id="request-phone-error" class="field-error" role="alert">{{ $message }}</p>
                                @enderror
                                <p class="help">We will send a 6-digit code to this number.</p>
                            </div>

                            <div>
                                <label for="request-email" class="label">Email <span class="font-medium normal-case tracking-normal text-ink-400">(optional)</span></label>
                                <input
                                    type="email"
                                    id="request-email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    autocomplete="email"
                                    class="input @error('email') border-red-500 @enderror"
                                    @error('email') aria-invalid="true" aria-describedby="request-email-error" @enderror
                                >
                                @error('email')
                                    <p id="request-email-error" class="field-error" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="request-city" class="label">City <span class="text-red-600" aria-hidden="true">*</span></label>
                                <select
                                    id="request-city"
                                    name="city_id"
                                    required
                                    class="input @error('city_id') border-red-500 @enderror"
                                    @error('city_id') aria-invalid="true" aria-describedby="request-city-error" @enderror
                                >
                                    <option value="">Select your city</option>
                                    @foreach ($options['cities'] as $city)
                                        <option value="{{ $city['id'] }}" @selected((string) old('city_id') === (string) $city['id'])>{{ $city['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('city_id')
                                    <p id="request-city-error" class="field-error" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="border-t border-ink-100 pt-6">
                        <legend class="text-xs font-black uppercase tracking-wide text-ink-500">
                            {{ $isSell ? 'Vehicle details' : 'What are you looking for?' }}
                        </legend>

                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="request-brand" class="label">
                                    {{ $isSell ? 'Brand' : 'Preferred brand' }}
                                    @if ($isSell) <span class="text-red-600" aria-hidden="true">*</span> @endif
                                </label>
                                <select
                                    id="request-brand"
                                    name="brand_id"
                                    x-model="brandId"
                                    @change="brandChanged()"
                                    @if ($isSell) required @endif
                                    class="input @error('brand_id') border-red-500 @enderror"
                                    @error('brand_id') aria-invalid="true" aria-describedby="request-brand-error" @enderror
                                >
                                    <option value="">{{ $isSell ? 'Select brand' : 'Any brand' }}</option>
                                    @foreach ($options['brands'] as $brand)
                                        <option value="{{ $brand['id'] }}" @selected((string) old('brand_id') === (string) $brand['id'])>{{ $brand['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('brand_id')
                                    <p id="request-brand-error" class="field-error" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="request-model" class="label">
                                    {{ $isSell ? 'Model' : 'Preferred model' }}
                                    @if ($isSell) <span class="text-red-600" aria-hidden="true">*</span> @endif
                                </label>
                                <select
                                    id="request-model"
                                    name="vehicle_model_id"
                                    x-model="modelId"
                                    @if ($isSell) required @endif
                                    class="input @error('vehicle_model_id') border-red-500 @enderror"
                                    @error('vehicle_model_id') aria-invalid="true" aria-describedby="request-model-error" @enderror
                                >
                                    <option value="">{{ $isSell ? 'Select model' : 'Any model' }}</option>
                                    <template x-for="model in filteredModels" :key="model.id">
                                        <option :value="model.id" x-text="model.name"></option>
                                    </template>
                                </select>
                                <p class="help" x-show="brandId && filteredModels.length === 0" x-cloak>
                                    No models listed for this brand yet. Pick "Any model" or tell us in the notes.
                                </p>
                                @error('vehicle_model_id')
                                    <p id="request-model-error" class="field-error" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="request-year" class="label">
                                    {{ $isSell ? 'Manufacturing year' : 'Preferred year (from)' }}
                                    @if ($isSell) <span class="text-red-600" aria-hidden="true">*</span> @endif
                                </label>
                                <select
                                    id="request-year"
                                    name="year"
                                    @if ($isSell) required @endif
                                    class="input @error('year') border-red-500 @enderror"
                                    @error('year') aria-invalid="true" aria-describedby="request-year-error" @enderror
                                >
                                    <option value="">{{ $isSell ? 'Select year' : 'Any year' }}</option>
                                    @for ($year = $currentYear; $year >= 1990; $year--)
                                        <option value="{{ $year }}" @selected((string) old('year') === (string) $year)>{{ $year }}</option>
                                    @endfor
                                </select>
                                @error('year')
                                    <p id="request-year-error" class="field-error" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="request-km" class="label">
                                    {{ $isSell ? 'Kilometres driven' : 'Maximum kilometres' }}
                                    @if ($isSell) <span class="text-red-600" aria-hidden="true">*</span> @endif
                                </label>
                                <input
                                    type="number"
                                    id="request-km"
                                    name="km_driven"
                                    value="{{ old('km_driven') }}"
                                    min="0"
                                    max="1000000"
                                    step="500"
                                    inputmode="numeric"
                                    placeholder="{{ $isSell ? 'e.g. 48000' : 'Any' }}"
                                    @if ($isSell) required @endif
                                    class="input @error('km_driven') border-red-500 @enderror"
                                    @error('km_driven') aria-invalid="true" aria-describedby="request-km-error" @enderror
                                >
                                @error('km_driven')
                                    <p id="request-km-error" class="field-error" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="request-price" class="label">
                                    {{ $isSell ? 'Expected price' : 'Maximum budget' }}
                                    @if ($isSell) <span class="text-red-600" aria-hidden="true">*</span> @endif
                                </label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-ink-400">₹</span>
                                    <input
                                        type="number"
                                        id="request-price"
                                        name="expected_price"
                                        value="{{ old('expected_price') }}"
                                        min="0"
                                        max="100000000"
                                        step="10000"
                                        inputmode="numeric"
                                        placeholder="{{ $isSell ? 'e.g. 850000' : 'Any' }}"
                                        @if ($isSell) required @endif
                                        class="input pl-8 @error('expected_price') border-red-500 @enderror"
                                        @error('expected_price') aria-invalid="true" aria-describedby="request-price-error" @enderror
                                    >
                                </div>
                                @error('expected_price')
                                    <p id="request-price-error" class="field-error" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="request-condition" class="label">Condition</label>
                                <select id="request-condition" name="condition" class="input @error('condition') border-red-500 @enderror">
                                    <option value="">Select condition</option>
                                    @foreach ($options['conditions'] as $condition)
                                        <option value="{{ $condition['value'] }}" @selected(old('condition') === $condition['value'])>{{ $condition['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('condition')
                                    <p class="field-error" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="request-colour" class="label">Colour</label>
                                <input
                                    type="text"
                                    id="request-colour"
                                    name="colour"
                                    value="{{ old('colour') }}"
                                    maxlength="50"
                                    placeholder="e.g. Pearl white"
                                    class="input @error('colour') border-red-500 @enderror"
                                >
                                @error('colour')
                                    <p class="field-error" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="request-message" class="label">Notes <span class="font-medium normal-case tracking-normal text-ink-400">(optional)</span></label>
                                <textarea
                                    id="request-message"
                                    name="message"
                                    rows="4"
                                    maxlength="2000"
                                    placeholder="{{ $isSell ? 'Service history, recent work, accessories or anything a buyer should know.' : 'Must-have features, preferred fuel type, transmission or anything else that matters.' }}"
                                    class="input resize-y @error('message') border-red-500 @enderror"
                                >{{ old('message') }}</textarea>
                                @error('message')
                                    <p class="field-error" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="border-t border-ink-100 pt-6">
                        <legend class="text-xs font-black uppercase tracking-wide text-ink-500">
                            {{ $isSell ? 'Photos' : 'Reference photos' }}
                            <span class="font-medium normal-case tracking-normal text-ink-400">(optional)</span>
                        </legend>

                        <div class="mt-3" x-data="photoUpload()">
                            <label
                                for="request-photos"
                                class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-ink-200 bg-ink-50 px-4 py-8 text-center transition hover:border-ink-400 hover:bg-white"
                            >
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-ink-500 shadow-sm">
                                    <x-wd-icon name="camera" size="sm" />
                                </span>
                                <span class="mt-3 text-sm font-black text-ink">Choose photos</span>
                                <span class="mt-1 text-xs text-ink-500">JPG, PNG or WebP · up to 6 images · 5 MB each</span>
                                <input
                                    type="file"
                                    id="request-photos"
                                    name="photos[]"
                                    accept="image/jpeg,image/png,image/webp"
                                    multiple
                                    class="sr-only"
                                    @change="onChange($event)"
                                >
                            </label>

                            @error('photos')
                                <p class="field-error" role="alert">{{ $message }}</p>
                            @enderror
                            @error('photos.*')
                                <p class="field-error" role="alert">{{ $message }}</p>
                            @enderror

                            <div class="mt-3 grid grid-cols-3 gap-2" x-show="previews.length" x-cloak>
                                <template x-for="(preview, index) in previews" :key="index">
                                    <div class="overflow-hidden rounded-xl border border-ink-100 bg-white">
                                        <img :src="preview.url" :alt="preview.name" class="aspect-[4/3] w-full object-cover">
                                        <p class="truncate px-2 py-1.5 text-[10px] font-semibold text-ink-500" x-text="preview.name"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="border-t border-ink-100 pt-6">
                        <legend class="text-xs font-black uppercase tracking-wide text-ink-500">Verification code by</legend>

                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            @foreach (\App\Enums\OtpChannel::options() as $value => $label)
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-ink-200 bg-white p-3.5 transition has-[:checked]:border-ink has-[:checked]:bg-ink-50">
                                    <input
                                        type="radio"
                                        name="channel"
                                        value="{{ $value }}"
                                        @checked(old('channel', 'sms') === $value)
                                        class="border-ink-300 text-ink focus:ring-brand-500"
                                    >
                                    <span class="flex items-center gap-2 text-sm font-bold text-ink">
                                        <x-wd-icon :name="$value === 'whatsapp' ? 'whatsapp' : 'phone'" size="sm" class="text-ink-400" />
                                        {{ $label }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('channel')
                            <p class="field-error" role="alert">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <div class="border-t border-ink-100 pt-6">
                        <label class="flex cursor-pointer items-start gap-3">
                            <input
                                type="checkbox"
                                name="consent"
                                value="1"
                                @checked(old('consent'))
                                required
                                class="mt-0.5 rounded border-ink-300 text-ink focus:ring-brand-500"
                            >
                            <span class="text-xs leading-5 text-ink-600">
                                I agree to be contacted by {{ config('seo.site_name') }} about this request and accept the
                                <a href="{{ route('pages.privacy') }}" class="link">privacy policy</a>.
                                <span class="text-red-600" aria-hidden="true">*</span>
                            </span>
                        </label>
                        @error('consent')
                            <p class="field-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-captcha />

                    <div class="border-t border-ink-100 pt-6">
                        <button type="submit" class="btn-primary w-full sm:w-auto">
                            {{ $isSell ? 'Continue to verification' : 'Send my requirements' }}
                            <x-wd-icon name="arrow-right" size="sm" />
                        </button>
                        <p class="mt-3 text-xs leading-5 text-ink-500">
                            You will not be added to a mailing list. The verification code expires after five minutes.
                        </p>
                    </div>
                </form>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start" aria-label="What happens next">
                <div class="rounded-2xl bg-ink p-5 text-white sm:p-6">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-brand-500">What happens next</p>
                    <ol class="mt-4 space-y-4">
                        @foreach ([
                            ['document', 'You share the basics', 'One short form. No account and no listing fee.'],
                            ['phone', 'We verify your number', 'A 6-digit code keeps fake requests out.'],
                            ['key', 'A real person calls', $isSell ? 'We discuss the car, the price expectation and next steps.' : 'We shortlist cars that fit and arrange viewings.'],
                        ] as $index => [$icon, $stepTitle, $copy])
                            <li class="flex gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-brand-500">
                                    <x-wd-icon :name="$icon" size="xs" />
                                </span>
                                <div>
                                    <p class="text-sm font-black text-white">{{ $index + 1 }}. {{ $stepTitle }}</p>
                                    <p class="mt-1 text-xs leading-5 text-ink-300">{{ $copy }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="card p-5">
                    <div class="flex items-center gap-2 text-ink">
                        <x-wd-icon name="shield-check" size="sm" class="text-emerald-600" />
                        <p class="text-sm font-black">Your details stay private</p>
                    </div>
                    <p class="mt-2 text-xs leading-5 text-ink-500">
                        Your number is only shared with the team handling your request. We never publish it on a listing.
                    </p>
                </div>

                <div class="card p-5">
                    <p class="text-sm font-black text-ink">Prefer to talk?</p>
                    <p class="mt-1 text-xs leading-5 text-ink-500">Call or WhatsApp during business hours.</p>
                    <div class="mt-3 space-y-2">
                        <a href="tel:{{ preg_replace('/\s+/', '', config('marketplace.brand.phone')) }}" class="btn-outline btn-sm w-full justify-start">
                            <x-wd-icon name="phone" size="xs" /> {{ config('marketplace.brand.phone') }}
                        </a>
                        <a href="https://wa.me/{{ config('marketplace.brand.whatsapp') }}" target="_blank" rel="noopener noreferrer" class="btn-secondary btn-sm w-full justify-start">
                            <x-wd-icon name="whatsapp" size="xs" /> WhatsApp us
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
@endsection
