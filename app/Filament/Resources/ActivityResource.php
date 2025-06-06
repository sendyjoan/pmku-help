<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use App\Models\Activity;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard';

    protected static ?int $navigationSort = 1;

    protected static function getNavigationLabel(): string
    {
        return __('Activities');
    }

    public static function getPluralLabel(): ?string
    {
        return static::getNavigationLabel();
    }

    protected static function getNavigationGroup(): ?string
    {
        return __('Referential');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('Activity name'))
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\Select::make('parent_id')
                                    ->label(__('Parent Activity'))
                                    ->placeholder(__('Select parent activity (optional)'))
                                    ->options(function ($record) {
                                        $query = Activity::query();

                                        // Jika edit, exclude diri sendiri
                                        if ($record) {
                                            $query->where('id', '!=', $record->id);
                                        }

                                        return $query->whereNull('parent_id')
                                            ->orWhere('level', '<', 2)
                                            ->get()
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $parent = Activity::find($state);
                                            $set('level', $parent ? $parent->level + 1 : 0);
                                        } else {
                                            $set('level', 0);
                                        }
                                    }),

                                Forms\Components\Hidden::make('level'),

                                Forms\Components\RichEditor::make('description')
                                    ->label(__('Description'))
                                    ->required()
                                    ->columnSpan(2),
                            ])
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Activity name'))
                    ->formatStateUsing(function ($record) {
                        if (is_null($record->parent_id)) {
                            return '📁 ' . $record->name;
                        } else {
                            return '    └─ ' . $record->name;
                        }
                    })
                    ->searchable(['name']),

                Tables\Columns\BadgeColumn::make('level')
                    ->label(__('Level'))
                    ->colors([
                        'primary' => 0,
                        'secondary' => 1,
                        'success' => 2,
                    ])
                    ->formatStateUsing(fn($state) => $state ?? 0),

                Tables\Columns\TextColumn::make('children_count')
                    ->label(__('Sub Activities'))
                    ->counts('children')
                    ->formatStateUsing(fn($state) => $state > 0 ? $state : '-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->label(__('Level'))
                    ->options([
                        0 => __('Root Activities'),
                        1 => __('Sub Activities'),
                        2 => __('Sub-Sub Activities'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('parent_id');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
            'create' => Pages\CreateActivity::route('/create'),
            'view' => Pages\ViewActivity::route('/{record}'),
            'edit' => Pages\EditActivity::route('/{record}/edit'),
        ];
    }
}
