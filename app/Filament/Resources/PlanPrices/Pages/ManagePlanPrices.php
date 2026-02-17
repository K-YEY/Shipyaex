<?php

namespace App\Filament\Resources\PlanPrices\Pages;

use App\Filament\Resources\PlanPrices\PlanPriceResource;
use App\Models\Governorate;
use App\Models\Plan;
use App\Models\PlanPrice;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ManagePlanPrices extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = PlanPriceResource::class;

    protected string $view = 'filament.resources.plan-prices.pages.manage-plan-prices';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('اختيار الباقة')
                    ->schema([
                            Select::make('plan_id')
                                ->label('الباقة')
                                ->relationship('plan', 'name')
                                ->searchable()
                                ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->loadPrices($state);
                            })
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('أسعار الشحن للمحافظات')
                    ->description('قم بتحديد سعر الشحن لكل محافظة لهذه الباقة')
                    ->schema(function ($get) {
                        $planId = $get('plan_id');
                        if (! $planId) {
                            return [];
                        }

                        $governorates = Governorate::all();
                        $inputs = [];

                        foreach ($governorates as $gov) {
                            $inputs[] = TextInput::make("prices.{$gov->id}")
                                ->label($gov->name)
                                ->numeric()
                                ->prefix('EGP')
                                ->default(0);
                        }

                        return $inputs;
                    })
                    ->columns([ 
                        'default' => 1,
                        'sm' => 2,
                        'md' => 3,
                        'lg' => 4,
                    ])
                    ->visible(fn ($get) => $get('plan_id') !== null),
            ])
            ->statePath('data');
    }

    public function loadPrices($planId)
    {
        if (! $planId) {
            return;
        }

        $existingPrices = PlanPrice::where('plan_id', $planId)
            ->pluck('price', 'location_id')
            ->toArray();

        $prices = [];
        foreach (Governorate::all() as $gov) {
            $prices[$gov->id] = $existingPrices[$gov->id] ?? 0;
        }

        $this->form->fill([
            'plan_id' => $planId,
            'prices' => $prices,
        ]);
    }

    public function save()
    {
        $data = $this->form->getState();
        $planId = $data['plan_id'] ?? null;
        $prices = $data['prices'] ?? [];

        if (! $planId) {
            return;
        }

        foreach ($prices as $governorateId => $price) {
            PlanPrice::updateOrCreate(
                [
                    'plan_id' => $planId,
                    'location_id' => $governorateId,
                ],
                [
                    'price' => $price,
                ]
            );
        }

        Notification::make()
            ->title('تم حفظ الأسعار بنجاح ✅')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('حفظ التعديلات')
                ->submit('save'),
        ];
    }
}
