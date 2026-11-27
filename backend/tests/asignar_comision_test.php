<?php

require_once __DIR__ . '/../modulos/comisiones/models/asignar_comision.php';

final class AsignacionPdoFalso {
    public array $comision;
    public array $personalComisionado = [];
    public array $conflictos = [];
    public bool $banderaSincronizada = false;
    public bool $transaccionActiva = false;

    public function __construct(string $tipoComision, string $estado = 'PENDIENTE') {
        $this->comision = [
            'id_comision' => 1,
            'id_area' => 10,
            'estado' => $estado,
            'tipo_comision' => $tipoComision,
            'id_subdireccion' => 20,
            'fecha_inicio' => $tipoComision === 'FORANEA' ? '2026-12-01' : null,
            'fecha_final' => $tipoComision === 'FORANEA' ? '2026-12-03' : null,
        ];
    }

    public function beginTransaction(): bool {
        $this->transaccionActiva = true;
        return true;
    }

    public function commit(): bool {
        $this->transaccionActiva = false;
        return true;
    }

    public function rollBack(): bool {
        $this->transaccionActiva = false;
        return true;
    }

    public function inTransaction(): bool {
        return $this->transaccionActiva;
    }

    public function prepare(string $sql): AsignacionStatementFalso {
        return new AsignacionStatementFalso($this, $sql);
    }

    public function ejecutar(string $sql, array $parametros): array {
        if (str_contains($sql, 'a.id_subdireccion')) {
            return ['filas' => [$this->comision], 'afectadas' => 0];
        }
        if (str_contains($sql, 'INNER JOIN comisiones o')) {
            return ['filas' => $this->conflictos, 'afectadas' => 0];
        }
        if (str_contains($sql, 'WHERE area = :area')) {
            return ['filas' => [['id_personal' => 31]], 'afectadas' => 0];
        }
        if (str_contains($sql, 'UPDATE personal p')) {
            $this->banderaSincronizada = true;
            return ['filas' => [], 'afectadas' => 1];
        }
        if (str_contains($sql, 'SELECT id_personal FROM personal_comisionado')) {
            return [
                'filas' => array_map(
                    static fn (int $id): array => ['id_personal' => $id],
                    $this->personalComisionado,
                ),
                'afectadas' => 0,
            ];
        }
        if (str_contains($sql, 'INSERT INTO personal_comisionado')) {
            $this->personalComisionado[] = (int) $parametros['id_personal'];
            return ['filas' => [], 'afectadas' => 1];
        }
        if (str_contains($sql, 'UPDATE comisiones')) {
            if ($this->comision['estado'] !== 'PENDIENTE') {
                return ['filas' => [], 'afectadas' => 0];
            }
            $this->comision['estado'] = str_contains($sql, "'EN CURSO'")
                ? 'EN CURSO'
                : 'PENDIENTE DE SOLICITUDES_V';
            if ($this->comision['estado'] === 'EN CURSO') {
                $this->comision['fecha_inicio'] = date('Y-m-d');
            }
            return ['filas' => [], 'afectadas' => 1];
        }
        if (str_contains($sql, 'SELECT fecha_inicio')) {
            return ['filas' => [['fecha_inicio' => $this->comision['fecha_inicio']]], 'afectadas' => 0];
        }
        return ['filas' => [], 'afectadas' => 1];
    }
}

final class AsignacionStatementFalso {
    private array $filas = [];
    private int $indice = 0;
    private int $afectadas = 0;

    public function __construct(
        private AsignacionPdoFalso $pdo,
        private string $sql,
    ) {}

    public function execute(array $parametros = []): bool {
        $resultado = $this->pdo->ejecutar($this->sql, $parametros);
        $this->filas = $resultado['filas'];
        $this->afectadas = $resultado['afectadas'];
        return true;
    }

    public function fetch(int $modo = PDO::FETCH_DEFAULT): array|false {
        return $this->filas[$this->indice++] ?? false;
    }

    public function fetchAll(int $modo = PDO::FETCH_DEFAULT): array {
        if ($modo === PDO::FETCH_COLUMN) {
            return array_map(
                static fn (array $fila): mixed => array_values($fila)[0],
                $this->filas,
            );
        }
        return $this->filas;
    }

    public function fetchColumn(int $columna = 0): mixed {
        $fila = $this->filas[$this->indice++] ?? false;
        return $fila === false ? false : array_values($fila)[$columna] ?? false;
    }

    public function rowCount(): int {
        return $this->afectadas;
    }
}

function comprobar(bool $condicion, string $mensaje): void {
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

$urbana = new AsignacionPdoFalso('URBANA');
$resultadoUrbana = asignar_comision($urbana, [
    'id_comision' => 1,
    'ids_personal' => [31],
], 3, 10);
comprobar($resultadoUrbana['estado'] === 'EN CURSO', 'La comisión urbana debe iniciar.');
comprobar($resultadoUrbana['fecha_inicio'] === date('Y-m-d'), 'La comisión urbana debe registrar fecha de inicio.');
comprobar($urbana->personalComisionado === [31], 'Debe asignarse el personal de la comisión urbana.');
comprobar($urbana->banderaSincronizada, 'Debe recalcularse la bandera comisionado tras asignar.');

$foranea = new AsignacionPdoFalso('FORANEA');
$resultadoForanea = asignar_comision($foranea, [
    'id_comision' => 1,
    'ids_personal' => [31],
], 3, 10);
comprobar(
    $resultadoForanea['estado'] === 'PENDIENTE DE SOLICITUDES_V',
    'La comisión foránea debe quedar pendiente de solicitudes.',
);
comprobar(
    $resultadoForanea['fecha_inicio'] === '2026-12-01',
    'La respuesta debe conservar la fecha programada de la comisión foránea.',
);
comprobar($foranea->personalComisionado === [31], 'Debe asignarse el personal de la comisión foránea.');
comprobar(
    $foranea->comision['fecha_inicio'] === '2026-12-01'
        && $foranea->comision['fecha_final'] === '2026-12-03',
    'La asignación no debe borrar el periodo programado de la comisión foránea.',
);

$noPendiente = new AsignacionPdoFalso('FORANEA', 'EN CURSO');
try {
    asignar_comision($noPendiente, [
        'id_comision' => 1,
        'ids_personal' => [31],
    ], 3, 10);
    throw new RuntimeException('Una comisión no pendiente no debe asignarse.');
} catch (InvalidArgumentException $error) {
    comprobar(!$noPendiente->transaccionActiva, 'El rechazo debe revertir la transacción.');
    comprobar($noPendiente->personalComisionado === [], 'El rechazo no debe asignar personal.');
}

$traslape = new AsignacionPdoFalso('FORANEA');
$traslape->conflictos = [[
    'id_personal' => 31, 'id_comision' => 2, 'estado' => 'PROGRAMADA',
    'fecha_inicio' => '2026-12-02', 'fecha_final' => '2026-12-04',
]];
try {
    asignar_comision($traslape, [
        'id_comision' => 1,
        'ids_personal' => [31],
    ], 3, 10);
    throw new RuntimeException('No debe asignarse personal con fechas traslapadas.');
} catch (InvalidArgumentException $error) {
    comprobar(!$traslape->transaccionActiva, 'El traslape debe revertir la transacción.');
    comprobar($traslape->personalComisionado === [], 'El traslape no debe asignar personal.');
}

echo "Pruebas de asignación de comisiones correctas.\n";
