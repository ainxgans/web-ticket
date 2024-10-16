<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingTransactionResource\Pages;
use App\Jobs\SendBookingApprovedEmail;
use App\Models\BookingTransaction;
use App\Models\Ticket;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookingTransactionResource extends Resource
{
    protected static ?string $model = BookingTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
    {
        return (string)BookingTransaction::where('is_paid', false)->count();
    }

    public static ?string $navigationGroup = 'Customer';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Product and Price')
                        ->schema([
                            Select::make('ticket_id')->relationship('ticket', 'name')->searchable()->preload()->required()->reactive()->afterStateUpdated(function ($state, callable $set) {
                                $ticket = Ticket::find($state);
                                $set('price', $ticket ? $ticket->price : 0);
                            }),
                            TextInput::make('total_participant')
                                ->required()
                                ->numeric()
                                ->prefix('People')
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    $price = $get('price');
                                    $subTotal = $price * $state;
                                    $totalPpn = $subTotal * 0.11;
                                    $totalAmount = $subTotal + $totalPpn;
                                    $set('total_amount', $totalAmount);
                                }),
                            TextInput::make('total_amount')
                                ->required()
                                ->numeric()
                                ->prefix('IDR')
                                ->readOnly()
                                ->helperText('Harga sudah include PPN 11%'),
                        ]),

                    Step::make('Customer Information')
                        ->schema([
                            TextInput::make('name')->required()->maxLength(255),
                            TextInput::make('phone_number')->required()->maxLength(255),
                            TextInput::make('email')->required()->maxLength(255),
                            TextInput::make('booking_trx_id')->required()->maxLength(255),
                        ]),

                    Step::make('Payment Information')
                        ->schema([
                            ToggleButtons::make('is_paid')
                                ->boolean()
                                ->grouped()
                                ->icons([
                                    true => 'heroicon-o-pencil',
                                    false => 'heroicon-o-clock',
                                ])
                                ->required(),
                            FileUpload::make('proof')->image()->required(),
                            DatePicker::make('started_at')->required(),
                        ]),
                ])
                    ->columnSpan('full')
                    ->columns(1)
                    ->skippable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('ticket.thumbnail')->circular(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('booking_trx_id')->searchable(),
                IconColumn::make('is_paid')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->label('Terverfiikasi'),
            ])
            ->filters([
                SelectFilter::make('ticket_id')->label('Tiket')->relationship('ticket', 'name'),
            ])
            ->actions([
                ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->action(function (BookingTransaction $record) {
                        $record->is_paid = true;
                        $record->save();
                        SendBookingApprovedEmail::dispatch($record);
                        Notification::make()
                            ->title('Ticket Approved')
                            ->success()
                            ->body('The ticket has been successfully approved.')
                            ->send();
                    })
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(BookingTransaction $record) => !$record->is_paid),
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
