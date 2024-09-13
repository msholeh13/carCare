<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingTransactionResource\Pages;
use App\Filament\Resources\BookingTransactionResource\RelationManagers;
use App\Models\BookingTransaction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingTransactionResource extends Resource
{
    protected static ?string $model = BookingTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('trx_id')
                    ->required()
                    ->maxLength(255),

                TextInput::make('phone_number')
                    ->required()
                    ->tel()
                    ->maxLength(255),

                TextInput::make('total_amount')
                    ->required()
                    ->numeric()
                    ->prefix('IDR'),

                DatePicker::make('started_at')
                    ->required(),

                TimePicker::make('time_at')
                    ->required(),

                Select::make('is_paid')
                    ->label('Is Paid?')
                    ->required()
                    ->options([
                        True => 'Paid',
                        False => 'Not Paid'
                    ]),

                Select::make('car_service_id')
                    ->required()
                    ->preload()
                    ->relationship('service_details', 'name')
                    ->searchable(),


                Select::make('car_store_id')
                    ->required()
                    ->preload()
                    ->searchable()
                    ->relationship('store_details', 'name'),

                FileUpload::make('proof')
                    ->image()
                    ->required(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('trx_id')
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Customers Name')
                    ->searchable(),

                TextColumn::make('started_at'),
                TextColumn::make('time_at'),

                TextColumn::make('service_details.name')
                    ->searchable(),
                TextColumn::make('store_details.name')
                    ->searchable(),

                IconColumn::make('is_paid')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
            ])
            ->filters([
                SelectFilter::make('service_detail_id')
                    ->label('Service Details')
                    ->relationship('service_details', 'name'),

                SelectFilter::make('store_detail_id')
                    ->label('Store Details')
                    ->relationship('store_details', 'name'),

                SelectFilter::make('is_paid')
                    ->options([
                        true => 'Paid',
                        false => 'Not paid'
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookingTransactions::route('/'),
            'create' => Pages\CreateBookingTransaction::route('/create'),
            'edit' => Pages\EditBookingTransaction::route('/{record}/edit'),
        ];
    }
}
