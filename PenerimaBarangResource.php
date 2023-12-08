<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Filament\Resources\PenerimaBarangResource\Pages;
use App\Filament\Resources\PenerimaBarangResource\RelationManagers;
use App\Models\Order;
use App\Models\Barang;
use Filament\Forms;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;



class PenerimaBarangResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Transaksi';
    protected static ?string $navigationGroup = 'Gudang';
    protected static ?string $slug = 'pembelian-barang';
    protected static ?string $breadcrumb = "Transaksi";
    // protected static ?string $navigationParentItem = 'Transaksi';



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema(static::getDetailsFormSchema())
                            ->columns(2),

                        Forms\Components\Section::make('Order Barang')
                            ->headerActions([
                                // Action::make('delete')
                                //     ->modalHeading('Apa Kamu Yakin?')
                                //     ->modalDescription('Semua Yang Berhubungan Dengan Data Ini Juga Akan Terhapus.')
                                //     ->requiresConfirmation()
                                //     ->color('danger')
                                //     ->action(fn (Forms\Set $set) => $set('items', [])),
                            ])
                            ->schema([
                                static::getItemsRepeater(),
                            ]),
                    ])
                    ->columnSpan(['lg' => fn (?Order $record) => $record === null ? 3 : 2]),

                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Created at')
                            ->content(fn (Order $record): ?string => $record->created_at?->diffForHumans()),

                        Forms\Components\Placeholder::make('updated_at')
                            ->label('Last modified at')
                            ->content(fn (Order $record): ?string => $record->updated_at?->diffForHumans()),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn (?Order $record) => $record === null),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('prinsipal.nama')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                // Tables\Columns\TextColumn::make('currency')
                //     ->getStateUsing(fn ($record): ?string => Currency::find($record->currency)?->name ?? null)
                //     ->searchable()
                //     ->sortable()
                //     ->toggleable(),
                Tables\Columns\TextColumn::make('total_harga')
                    ->searchable()
                    ->money("IDR")
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money("IDR"),
                    ]),
                // Tables\Columns\TextColumn::make('shipping_price')
                //     ->label('Shipping cost')
                //     ->searchable()
                //     ->sortable()
                //     ->toggleable()
                //     ->summarize([
                //         Tables\Columns\Summarizers\Sum::make()
                //             ->money(),
                //     ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Order Date')
                    ->date()
                    ->toggleable(),
            ])
            ->filters([
                // Tables\Filters\TrashedFilter::make(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->placeholder(fn ($state): string => 'Dec 18, ' . now()->subYear()->format('Y')),
                        Forms\Components\DatePicker::make('created_until')
                            ->placeholder(fn ($state): string => now()->format('M d, Y')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'Order from ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'Order until ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->groupedBulkActions([
                Tables\Actions\DeleteBulkAction::make()
                // ->action(function () {
                //     Notification::make()
                //         ->title('Now, now, don\'t be cheeky, leave some records for others to play with!')
                //         ->warning()
                //         ->send();
                // }),
            ])
            ->groups([
                Tables\Grouping\Group::make('created_at')
                    ->label('Order Date')
                    ->date()
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            PenerimaBarangResource\Widgets\OrderStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenerimaBarangs::route('/'),
            'create' => Pages\CreatePenerimaBarang::route('/create'),
            'view' => Pages\ViewPenerimaBarang::route('/{record}'),
            'edit' => Pages\EditPenerimaBarang::route('/{record}/edit'),
        ];
    }

    // public static function getEloquentQuery(): Builder
    // {
    //     // return parent::getEloquentQuery()->withoutGlobalScope(SoftDeletingScope::class);
    // }

    public static function getGloballySearchableAttributes(): array
    {
        return ['invoice', 'prinsipal.nama'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Order $record */

        return [
            'Prinsipal' => optional($record->prinsipal)->nama,
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['prinsipal', 'items']);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::$model::where('status', 'baru')->count();
    }

    public static function getDetailsFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('invoice')
                ->default('INV-' . random_int(100000, 999999))
                ->disabled()
                ->dehydrated()
                ->required()
                ->maxLength(32)
                ->unique(Order::class, 'invoice', ignoreRecord: true),

            Forms\Components\Select::make('prinsipal_id')
                ->relationship('prinsipal', 'nama')
                ->searchable()
                ->preload()
                ->required()
                ->createOptionForm([
                    Forms\Components\TextInput::make('kode')
                        ->required()
                        ->maxLength(20),
                    Forms\Components\TextInput::make('nama')
                        ->required()
                        ->maxLength(50),
                    Forms\Components\TextInput::make('alamat')
                        ->required(),
                    Forms\Components\TextInput::make('kota')
                        ->required(),
                    Forms\Components\TextInput::make('kode_pos')
                        ->required(),
                    Forms\Components\TextInput::make('kontak')
                        ->required(),
                    Forms\Components\Textarea::make('keterangan')
                        ->required(),
                ])
                ->createOptionAction(function (Action $action) {
                    return $action
                        ->modalHeading('Prinsipal Baru')
                        ->modalButton('Prinsipal Baru')
                        ->modalWidth('lg');
                }),

            Forms\Components\Select::make('status')
                ->options(OrderStatus::class)
                ->required()
                ->native(false),

            // Forms\Components\Select::make('currency')
            //     ->searchable()
            //     ->getSearchResultsUsing(fn (string $query) => Currency::where('name', 'like', "%{$query}%")->pluck('name', 'id'))
            //     ->getOptionLabelUsing(fn ($value): ?string => Currency::find($value)?->getAttribute('name'))
            //     ->required(),

            // AddressForm::make('address')
            //     ->columnSpan('full'),

            Forms\Components\MarkdownEditor::make('notes')
                ->columnSpan('full'),
        ];
    }

    public static function getItemsRepeater(): Repeater
    {
        return Repeater::make('items')
            ->relationship()
            ->schema([
                Forms\Components\Select::make('barang_id')
                    ->label('Barang')
                    ->options(Barang::query()->pluck('nama', 'id'))
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => [$set('satuan', Barang::find($state)->satuan)])
                    ->distinct()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->columnSpan([
                        'md' => 3,
                    ])
                    ->searchable(),
                Forms\Components\Select::make('satuan')
                    ->options(function ($state) {
                        $satuan = [];
                        if (!$state) {
                            return [];
                        } else {
                            // dd($state);
                            foreach ($state as $k => $v) {
                                $satuan[$k] = $v['nama'];
                            }
                            return $satuan;
                        }
                    })
                    ->columnSpan([
                        'md' => 2,
                    ]),
                Forms\Components\TextInput::make('qty')
                    ->label('Quantity')
                    ->numeric()
                    ->default(1)
                    ->reactive()
                    ->afterStateUpdated(
                        function ($state, Forms\set $set, Get $get) {
                            $set('sub_total', ($state * $get('harga')));
                            $set('subTotal', ($state * $get('harga')));
                        }
                    )
                    ->columnSpan([
                        'md' => 1,
                    ])
                    ->required(),

                Forms\Components\TextInput::make('harga')
                    ->label('Harga')
                    // ->disabled()
                    ->reactive()
                    ->afterStateUpdated(
                        function ($state, Forms\set $set, Get $get) {
                            $set('sub_total', ($state * $get('qty')));
                            $set('subTotal', ($state * $get('qty')));
                        }
                    )
                    ->numeric()
                    ->required()
                    ->columnSpan([
                        'md' => 2,
                    ]),
                Forms\Components\TextInput::make('sub_total')
                    ->label('Sub Total')
                    ->disabled()
                    ->numeric()
                    ->required()
                    ->columnSpan([
                        'md' => 2,
                    ]),
                Forms\Components\Hidden::make('sub_total')
            ])
            ->extraItemActions([
                Action::make('Lihat Barang')
                    ->tooltip('Lihat Barang')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(function (array $arguments, Repeater $component): ?string {
                        $itemData = $component->getRawItemState($arguments['item']);

                        $product = Barang::find($itemData['barang_id']);

                        if (!$product) {
                            return null;
                        }

                        return BarangResource::getUrl('edit', ['record' => $product]);
                    }, shouldOpenInNewTab: true)
                    ->hidden(fn (array $arguments, Repeater $component): bool => blank($component->getRawItemState($arguments['item'])['barang_id'])),
            ])
            // ->orderColumn('sort')
            ->defaultItems(1)
            ->hiddenLabel()
            ->columns([
                'md' => 10,
            ])
            ->required();
    }
}
