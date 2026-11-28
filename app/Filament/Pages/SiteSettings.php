<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\SiteSettings as SiteSettingsSupport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class SiteSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Site settings';

    protected static ?string $slug = 'site-settings';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.site-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            SiteSettingsSupport::KEY_NAME => Setting::get(SiteSettingsSupport::KEY_NAME, (string) config('app.name')),
            SiteSettingsSupport::KEY_PHONE => Setting::get(SiteSettingsSupport::KEY_PHONE, (string) config('marketplace.brand.phone')),
            SiteSettingsSupport::KEY_WHATSAPP => Setting::get(SiteSettingsSupport::KEY_WHATSAPP, (string) config('marketplace.brand.whatsapp')),
            SiteSettingsSupport::KEY_EMAIL => Setting::get(SiteSettingsSupport::KEY_EMAIL, (string) config('marketplace.brand.email')),
            SiteSettingsSupport::KEY_ADDRESS => Setting::get(SiteSettingsSupport::KEY_ADDRESS, (string) config('marketplace.brand.address')),
            SiteSettingsSupport::KEY_LOGO => Setting::get(SiteSettingsSupport::KEY_LOGO, (string) config('marketplace.brand.logo')),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identity')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make(SiteSettingsSupport::KEY_NAME)
                            ->label('Website name')
                            ->required()
                            ->maxLength(120)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make(SiteSettingsSupport::KEY_LOGO)
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->directory('branding')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->imagePreviewHeight('100')
                            ->helperText('Recommended: square PNG or WebP with a transparent background.')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Contact details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make(SiteSettingsSupport::KEY_PHONE)
                            ->label('Phone number')
                            ->tel()
                            ->required()
                            ->maxLength(50),
                        Forms\Components\TextInput::make(SiteSettingsSupport::KEY_WHATSAPP)
                            ->label('WhatsApp number')
                            ->tel()
                            ->required()
                            ->maxLength(20)
                            ->helperText('Use digits only, including country code.'),
                        Forms\Components\TextInput::make(SiteSettingsSupport::KEY_EMAIL)
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->maxLength(190),
                        Forms\Components\Textarea::make(SiteSettingsSupport::KEY_ADDRESS)
                            ->label('Address')
                            ->required()
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $oldLogo = Setting::get(SiteSettingsSupport::KEY_LOGO);
        $logo = $state[SiteSettingsSupport::KEY_LOGO] ?? null;
        $logo = is_string($logo) && $logo !== '' ? $logo : null;

        Setting::set(SiteSettingsSupport::KEY_NAME, trim((string) $state[SiteSettingsSupport::KEY_NAME]));
        Setting::set(SiteSettingsSupport::KEY_PHONE, trim((string) $state[SiteSettingsSupport::KEY_PHONE]));
        Setting::set(
            SiteSettingsSupport::KEY_WHATSAPP,
            preg_replace('/\D+/', '', (string) $state[SiteSettingsSupport::KEY_WHATSAPP]),
        );
        Setting::set(SiteSettingsSupport::KEY_EMAIL, trim((string) $state[SiteSettingsSupport::KEY_EMAIL]));
        Setting::set(SiteSettingsSupport::KEY_ADDRESS, trim((string) $state[SiteSettingsSupport::KEY_ADDRESS]));
        Setting::set(SiteSettingsSupport::KEY_LOGO, $logo);

        if ($oldLogo && $oldLogo !== $logo) {
            Storage::disk('public')->delete($oldLogo);
        }

        SiteSettingsSupport::reload();

        Notification::make()
            ->success()
            ->title('Site settings saved.')
            ->send();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->isSuperAdmin() || $user->can('manage_site_settings');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
