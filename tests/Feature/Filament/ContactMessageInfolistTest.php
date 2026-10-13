<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ContactMessages\Schemas\ContactMessageInfolist;
use App\Models\ContactMessage;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContactMessageInfolistTest extends TestCase
{
    use RefreshDatabase;

    private function message(array $attributes = []): ContactMessage
    {
        return ContactMessage::create([
            'first_name' => 'Ana', 'email' => 'ana@example.com',
            'message' => 'Mensaje original', 'status' => 'nuevo',
            ...$attributes,
        ]);
    }

    #[DataProvider('emptyFields')]
    public function test_section_is_absent_for_old_or_empty_messages(array $attributes): void
    {
        Livewire::test(ContactMessageInfolistPreview::class, ['record' => $this->message($attributes)])
            ->assertSee('Datos de contacto')
            ->assertSee('Estado')
            ->assertSee('Mensaje original')
            ->assertDontSee('Campos del formulario');
    }

    public static function emptyFields(): iterable
    {
        yield 'old message' => [[]];
        yield 'null' => [['custom_fields' => null]];
        yield 'empty' => [['custom_fields' => []]];
    }

    public function test_labels_and_answers_are_rendered_without_changing_the_record(): void
    {
        $values = [
            ['empresa', 'Compañía', 'text', 'Ágora 日本語'],
            ['empleados', 'Empleados', 'number', 0],
            ['cantidad', 'Cantidad', 'number', 25],
            ['decimal', 'Decimal', 'number', 2.5],
            ['acepta', 'Acepta', 'checkbox', true],
            ['rechaza', 'Rechaza', 'checkbox', false],
        ];
        $fields = array_map(fn ($field) => array_combine(['id', 'label', 'type', 'value'], $field), $values);
        $record = $this->message(['custom_fields' => $fields]);
        $original = $record->fresh()->getRawOriginal();
        $preview = Livewire::test(ContactMessageInfolistPreview::class, ['record' => $record])
            ->assertSee('Campos del formulario')
            ->assertSee('Mensaje original')
            ->assertSee('Sí')->assertSee('No');

        foreach ($values as [, $label, , $value]) {
            $preview->assertSee($label);
            if (! is_bool($value)) {
                $preview->assertSee((string) $value);
            }
        }

        $schema = $preview->instance()->infolist(Schema::make($preview->instance()));
        $sections = $schema->getComponents();
        $repeatable = $sections[3]->getChildSchema()->getComponents()[0];
        foreach ($repeatable->getItems() as $index => $item) {
            $entry = $item->getComponents()[1];
            $value = $values[$index][3];
            $this->assertSame(is_bool($value) ? ($value ? 'Sí' : 'No') : (string) $value, $entry->formatState($entry->getState()));
            $this->assertInstanceOf(TextEntry::class, $entry);
            $this->assertFalse($entry->isHtml());
        }
        $this->assertSame($original, $record->fresh()->getRawOriginal());
    }

    public function test_html_in_labels_and_answers_is_escaped_in_the_rendered_output(): void
    {
        $label = '<script>alert("label")</script>';
        $value = '<img src=x onerror="alert(1)"><b>Respuesta</b>';
        $record = $this->message(['custom_fields' => [[
            'id' => 'unsafe', 'label' => $label, 'type' => 'text', 'value' => $value,
        ]]]);

        Livewire::test(ContactMessageInfolistPreview::class, ['record' => $record])
            ->assertSee(e($label), escape: false)
            ->assertSee(e($value), escape: false)
            ->assertDontSee($label, escape: false)
            ->assertDontSee($value, escape: false);

        $this->assertSame($value, $record->fresh()->custom_fields[0]['value']);
    }
}

class ContactMessageInfolistPreview extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public ContactMessage $record;

    public function infolist(Schema $schema): Schema
    {
        return ContactMessageInfolist::configure($schema->record($this->record));
    }

    public function render(): string
    {
        return '<div>{{ $this->infolist }}</div>';
    }
}
