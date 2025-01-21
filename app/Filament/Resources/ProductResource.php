<?php

namespace App\Filament\Resources;

use App\Enums\ProductStatusEnum;
use App\Enums\RolesEnum;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-s-queue-list';

    protected static SubNavigationPosition $subNavigationPosition = subNavigationPosition::End;

    public static function getEloquentQuery(): Builder //show only product of authenticated vendor
    {
        return parent::getEloquentQuery()->forVendor();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()->schema([

                    Forms\Components\TextInput::make('title')
                        ->live(onBlur: true)
                        ->required()
                        ->afterStateUpdated(
                            function (string $operation, $state, callable $set) {
                                $set("slug", Str::slug($state));
                            }
                        ),
                    Forms\Components\TextInput::make('slug')
                        ->required(),
                    Forms\Components\Select::make('department_id')
                        ->relationship('department', 'name')
                        ->label(__('department'))
                        ->preload()
                        ->searchable()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function (callable $set) {
                            $set('category_id', null);
                        }),
                    Select::make('category_id')
                        ->relationship(
                        //get selected department and fetch relevant categories
                            name: 'category', //category is relation in Product model
                            titleAttribute: 'name',
                            modifyQueryUsing: function (Builder $query, callable $get) {
                                $departmentId = $get('department_id');
                                if ($departmentId) {
                                    $query->where('department_id', $departmentId);
                                }
                            }
                        )
                        ->label(__('category'))
                        ->preload()
                        ->searchable()
                        ->required(),
                ]),
                RichEditor::make('description')
                    ->required()
                    ->toolbarButtons([
                        'blockquote',
                        'bold',
                        'bulletList',
                        'h2',
                        'h3',
                        'italic',
                        'link',
                        'orderedList',
                        'redo',
                        'strike',
                        'underline',
                        'undo',
                        'table',
                    ])
                    ->columnSpan(2),
                TextInput::make('price')
                    ->required()
                    ->numeric(),
                TextInput::make('quantity')
                    ->integer(),
                Select::make('status')
                    ->options(ProductStatusEnum::labels())
                    ->default(ProductStatusEnum::Draft->value)
                    ->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('images')
                    ->collection('images')
                    ->limit(1)
                    ->conversion('thumb'),
                Tables\Columns\TextColumn::make('title')
                    ->words(10)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors(ProductStatusEnum::colors()),
                Tables\Columns\TextColumn::make('department.name'),
                Tables\Columns\TextColumn::make('category.name'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(ProductStatusEnum::labels()),
                Tables\Filters\SelectFilter::make('department_id')
                    ->relationship('department', 'name')
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return
            $page->generateNavigationItems([
                Pages\EditProduct::class,
                Pages\ProductImages::class,
                Pages\ProductVariationTypes::class,
                Pages\ProductVariations::class,
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'images' => Pages\ProductImages::route('/{record}/images'),
            'variation-types' => Pages\ProductVariationTypes::route('/{record}/variation-types'),
            'variations' => Pages\ProductVariations::route('/{record}/variations'),
        ];
    }

    public static function canViewAny(): bool
    {
        /** @var User $user */
        $user = auth()->user();
        return $user && $user->hasRole(RolesEnum::Vendor->value);
    }
}
