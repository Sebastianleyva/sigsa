<?php

require_once __DIR__ . '/../modulos/comisiones/models/asignar_comision.php';

final class AsignacionPdoFalso {
    public array $comision;
    public array $personalComisionado = [];
    public bool $transaccionActiva = false;

    public function __construct(string $tipoComision, string $estado = 'PENDIENTE') {
        $this->comision = [
            'id_area' => 10,
            'estado' => $estado,
            'tipo_comision' => $tipoComision,
            'id_subdireccion' => 20,
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
        if (str_contains($sql, 'SELECT c.id_area')) {
            return ['filas' => [$this->comision], 'afectadas' => 0];
        }
        if (str_contains($sql, 'SELECT id_personal, comisionado')) {
            return [
                'filas' => [['id_personal' => 31, 'comisionado' => 0]],
                'afectadas' => 0,
            ];
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
            $this->comision['fecha_inicio'] = $this->comision['estado'] === 'EN CURSO'
                ? date('Y-m-d')
                : null;
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

$foranea = new AsignacionPdoFalso('FORANEA');
$resultadoForanea = asignar_comision($foranea, [
    'id_comision' => 1,
    'ids_personal' => [31],
], 3, 10);
comprobar(
    $resultadoForanea['estado'] === 'PENDIENTE DE SOLICITUDES_V',
    'La comisión foránea debe quedar pendiente de solicitudes.',
);
comprobar($resultadoForanea['fecha_inicio'] === null, 'La comisión foránea no debe registrar fecha de inicio.');
comprobar($foranea->personalComisionado === [31], 'Debe asignarse el personal de la comisión foránea.');

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

echo "Pruebas de asignación de comisiones correctas.\n";
