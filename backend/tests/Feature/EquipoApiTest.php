<?php

use App\Models\Categoria;
use App\Models\Equipo;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/*
| TODO [B6] Escriba las tres pruebas. Cada una debe verificar el código
| de estado Y algo más (estructura, encabezado, mensaje o estado de la base).
|
| Quite el ->todo() de cada prueba al implementarla.
| Ejecute:  php artisan test --filter=EquipoApi
*/

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create());
});

it('crea un equipo y responde 201 con el encabezado Location', function () {
    $categoria = Categoria::factory()->create();

    $response = $this->postJson('/api/equipos', [
        'codigo' => 'EQ-9999',
        'nombre' => 'Equipo de prueba',
        'categoria_id' => $categoria->id,
        'fecha_adquisicion' => now()->toDateString(),
        'costo_interno' => 125000,
    ]);

    $equipo = Equipo::where('codigo', 'EQ-9999')->firstOrFail();

    $response->assertCreated()
        ->assertHeader('Location', route('equipos.show', $equipo))
        ->assertJsonPath('data.codigo', 'EQ-9999')
        ->assertJsonPath('data.categoria.id', $categoria->id);
});

it('rechaza un equipo con datos inválidos y responde 422 con errores por campo', function () {
    $response = $this->postJson('/api/equipos', [
        'codigo' => 'incorrecto',
        'nombre' => 'x',
        'categoria_id' => 999,
        'fecha_adquisicion' => now()->addDay()->toDateString(),
        'costo_interno' => -1,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'codigo',
            'nombre',
            'categoria_id',
            'fecha_adquisicion',
            'costo_interno',
        ]);
});

it('impide eliminar un equipo prestado y responde 409', function () {
    $equipo = Equipo::factory()->prestado()->create();

    $this->deleteJson("/api/equipos/{$equipo->id}")
        ->assertConflict()
        ->assertExactJson([
            'mensaje' => 'No se puede eliminar un equipo prestado.',
            'codigo' => 'REGLA_NEGOCIO',
        ]);

    $this->assertDatabaseHas('equipos', [
        'id' => $equipo->id,
        'estado' => 'prestado',
    ]);
});
