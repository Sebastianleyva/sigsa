<?php

require_once __DIR__ . '/../modulos/comisiones/models/crear_comision.php';

final class CrearComisionPdoFalso {
    public array $comision = [];
    public bool $transaccion_activa = false;
    private bool $solicitud_marcada = false;

    public function beginTransaction(): bool {
        $this->transaccion_activa = true;
        return true;
    }

    public function commit(): bool {
        $this->transaccion_activa = false;
        return true;
    }

    public function rollBack(): bool {
        $this->transaccion_activa = false;
        return true;
    }

    public function inTransaction(): bool {
        return $this->transaccion_activa;
    }

    public function prepare(string $sql): CrearComisionStatementFalso {
        return new CrearComisionStatementFalso($this, $sql);
    }

    public function lastInsertId(): string {
        return '15';
    }

    public function ejecutar(string $sql, array $parametros): array {
        if (str_contains($sql, 'SELECT id_area FROM areas')) {
            return ['filas' => [['id_area' => 6]], 'afectadas' => 0];
        }
        if (str_contains($sql, 'UPDATE solicitudes')) {
            if ($this->solicitud_marcada) {
                return ['filas' => [], 'afectadas' => 0];
            }
            $this->solicitud_marcada = true;
            return ['filas' => [], 'afectadas' => 1];
        }
        if (str_contains($sql, 'INSERT INTO comisiones')) {
            $this->comision = $parametros;
            return ['filas' => [], 'afectadas' => 1];
        }
        throw new RuntimeException("Consulta inesperada en prueba: {$sql}");
    }
}

final class CrearComisionStatementFalso {
    private array $filas = [];
    private int $afectadas = 0;

    public function __construct(
        private CrearComisionPdoFalso $pdo,
        private string $sql,
    ) {}

    public function execute(array $parametros = []): bool {
        $resultado = $this->pdo->ejecutar($this->sql, $parametros);
        $this->filas = $resultado['filas'];
        $this->afectadas = $resultado['afectadas'];
        return true;
    }

    public function fetchColumn(int $columna = 0): mixed {
        return $this->filas[0] === null ? false : array_values($this->filas[0])[$columna] ?? false;
    }

    public function rowCount(): int {
        return $this->afectadas;
    }
}

function comprobar_creacion_comision(bool $condicion, string $mensaje): void {
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

$datos = [
    'id_solicitud' => 2,
    'id_area' => 6,
    'tipo_comision' => 'URBANA',
    'tipo_destino' => 'CENTRO ESCOLAR',
];
$urbana = new CrearComisionPdoFalso();
$resultadoUrbana = crear_comision($urbana, $datos);
comprobar_creacion_comision(
    $resultadoUrbana['fecha_inicio'] === null && $resultadoUrbana['fecha_final'] === null,
    'Las comisiones urbanas deben conservar las fechas sin programar.',
);

$datosForanea = $datos + [
    'fecha_inicio' => '2026-12-01',
    'fecha_final' => '2026-12-03',
];
$datosForanea['tipo_comision'] = 'FORANEA';
$foranea = new CrearComisionPdoFalso();
$resultadoForanea = crear_comision($foranea, $datosForanea);
comprobar_creacion_comision(
    $resultadoForanea['fecha_inicio'] === '2026-12-01'
        && $resultadoForanea['fecha_final'] === '2026-12-03'
        && $foranea->comision['fecha_inicio'] === '2026-12-01'
        && $foranea->comision['fecha_final'] === '2026-12-03',
    'Las fechas seleccionadas deben guardarse en la comisión foránea.',
);

$datosForanea['fecha_final'] = '2026-11-30';
$fechasInvalidas = new CrearComisionPdoFalso();
try {
    crear_comision($fechasInvalidas, $datosForanea);
    throw new RuntimeException('La fecha final anterior al inicio debe rechazarse.');
} catch (InvalidArgumentException) {
    comprobar_creacion_comision(
        !$fechasInvalidas->transaccion_activa && $fechasInvalidas->comision === [],
        'Un periodo inválido no debe persistirse.',
    );
}

echo "Pruebas de creación de comisiones correctas.\n";
