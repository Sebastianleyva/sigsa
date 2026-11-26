<?php

require_once __DIR__ . '/../modulos/comisiones/models/crear_solicitud_viaticos.php';

final class SolicitudViaticosPdoFalso {
    public string $estado = 'PENDIENTE DE SOLICITUDES_V';
    public string $tipo_comision = 'FORANEA';
    public ?string $fecha_inicio = null;
    public array $solicitudes = [];
    public array $renglones = [];
    public bool $transaccion_activa = false;
    private int $ultimo_id = 0;
    private ?array $respaldo = null;

    public function beginTransaction(): bool {
        $this->respaldo = [$this->estado, $this->fecha_inicio, $this->solicitudes, $this->renglones];
        $this->transaccion_activa = true;
        return true;
    }

    public function commit(): bool {
        $this->transaccion_activa = false;
        $this->respaldo = null;
        return true;
    }

    public function rollBack(): bool {
        if ($this->respaldo !== null) {
            [$this->estado, $this->fecha_inicio, $this->solicitudes, $this->renglones] = $this->respaldo;
        }
        $this->transaccion_activa = false;
        $this->respaldo = null;
        return true;
    }

    public function inTransaction(): bool {
        return $this->transaccion_activa;
    }

    public function prepare(string $sql): SolicitudViaticosStatementFalso {
        return new SolicitudViaticosStatementFalso($this, $sql);
    }

    public function lastInsertId(): string {
        return (string) $this->ultimo_id;
    }

    public function ejecutar(string $sql, array $parametros): array {
        if (str_contains($sql, 'SELECT puesto, area')) {
            return ['filas' => [['puesto' => 3, 'area' => 6]], 'afectadas' => 0];
        }
        if (str_contains($sql, 'SELECT estado')) {
            return ['filas' => [[
                'estado' => $this->estado,
                'tipo_comision' => $this->tipo_comision,
            ]], 'afectadas' => 0];
        }
        if (str_contains($sql, 'SELECT p.puesto')) {
            $puestos = [41 => 7, 42 => 8];
            return array_key_exists((int) $parametros['id_personal'], $puestos)
                ? ['filas' => [['puesto' => $puestos[(int) $parametros['id_personal']]]], 'afectadas' => 0]
                : ['filas' => [], 'afectadas' => 0];
        }
        if (str_contains($sql, 'SELECT id_solicitud_viaticos')) {
            return isset($this->solicitudes[(int) $parametros['id_personal']])
                ? ['filas' => [['id_solicitud_viaticos' => 1]], 'afectadas' => 0]
                : ['filas' => [], 'afectadas' => 0];
        }
        if (str_contains($sql, 'SELECT permite_constancia')) {
            return ['filas' => [['permite_constancia' => 1]], 'afectadas' => 0];
        }
        if (str_contains($sql, 'SELECT monto_diario')) {
            $monto = (int) $parametros['pernocta'] === 1 ? '850.00' : '425.00';
            return ['filas' => [['monto_diario' => $monto]], 'afectadas' => 0];
        }
        if (str_contains($sql, 'INSERT INTO solicitudes_de_viaticos')) {
            $this->ultimo_id = count($this->solicitudes) + 1;
            $this->solicitudes[(int) $parametros['id_personal_comisionado']] = $this->ultimo_id;
            return ['filas' => [], 'afectadas' => 1];
        }
        if (str_contains($sql, 'INSERT INTO viaticos_comisiones')) {
            $this->renglones[] = $parametros;
            return ['filas' => [], 'afectadas' => 1];
        }
        if (str_contains($sql, 'SELECT COUNT(*)')) {
            $pendientes = count(array_diff([41, 42], array_keys($this->solicitudes)));
            return ['filas' => [['COUNT(*)' => $pendientes]], 'afectadas' => 0];
        }
        if (str_contains($sql, 'SELECT 1')) {
            return ['filas' => [['1' => 1]], 'afectadas' => 0];
        }
        if (str_contains($sql, 'UPDATE comisiones')) {
            $this->estado = 'EN CURSO';
            $this->fecha_inicio = date('Y-m-d');
            return ['filas' => [], 'afectadas' => 1];
        }
        throw new RuntimeException("Consulta inesperada en prueba: {$sql}");
    }
}

final class SolicitudViaticosStatementFalso {
    private array $filas = [];
    private int $indice = 0;
    private int $afectadas = 0;

    public function __construct(
        private SolicitudViaticosPdoFalso $pdo,
        private string $sql,
    ) {}

    public function execute(array $parametros = []): bool {
        $resultado = $this->pdo->ejecutar($this->sql, $parametros);
        $this->filas = $resultado['filas'];
        $this->indice = 0;
        $this->afectadas = $resultado['afectadas'];
        return true;
    }

    public function fetch(int $modo = PDO::FETCH_DEFAULT): array|false {
        return $this->filas[$this->indice++] ?? false;
    }

    public function fetchColumn(int $columna = 0): mixed {
        $fila = $this->filas[$this->indice++] ?? false;
        return $fila === false ? false : array_values($fila)[$columna] ?? false;
    }

    public function rowCount(): int {
        return $this->afectadas;
    }
}

function comprobar_solicitud_viaticos(bool $condicion, string $mensaje): void {
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

$pdo = new SolicitudViaticosPdoFalso();
$entrada = [
    'id_comision' => 5,
    'id_personal_comisionado' => 41,
    'id_zona' => 2,
    'tipo_comprobacion' => 'FACTURA',
    'puesto' => 'Analista',
    'programa' => 'PROG01',
    'clave_cobro' => '25021',
    'contacto_emergencia_nombre' => '',
    'contacto_emergencia_numero' => '',
    'observaciones' => 'Primera solicitud',
    'renglones' => [[
        'id_codigo_contable_comision' => 3,
        'num_dias' => null,
        'importe' => 750,
    ]],
];

$importe_decimal = $entrada;
$importe_decimal['renglones'][0]['importe'] = 750.25;
try {
    crear_solicitud_viaticos($pdo, $importe_decimal, 10);
    throw new RuntimeException('Los importes con centavos deben rechazarse para decimal(10,0).');
} catch (InvalidArgumentException) {
    comprobar_solicitud_viaticos(!$pdo->transaccion_activa, 'La validación de importe debe ocurrir antes de iniciar la transacción.');
}

$dias_invalidos = $entrada;
$dias_invalidos['renglones'][0]['num_dias'] = 0.25;
try {
    crear_solicitud_viaticos($pdo, $dias_invalidos, 10);
    throw new RuntimeException('Los días deben avanzar en incrementos de medio día.');
} catch (InvalidArgumentException) {
    comprobar_solicitud_viaticos(!$pdo->transaccion_activa, 'La validación de días debe ocurrir antes de iniciar la transacción.');
}

$pdo->tipo_comision = 'URBANA';
try {
    crear_solicitud_viaticos($pdo, $entrada, 10);
    throw new RuntimeException('Las solicitudes de viáticos no deben iniciar para comisiones urbanas.');
} catch (SolicitudViaticosConflictException) {
    comprobar_solicitud_viaticos(!$pdo->transaccion_activa, 'Una comisión urbana debe revertir la transacción.');
    comprobar_solicitud_viaticos($pdo->solicitudes === [], 'Una comisión urbana no debe recibir una solicitud de viáticos.');
}
$pdo->tipo_comision = 'FORANEA';

$primera = crear_solicitud_viaticos($pdo, $entrada, 10);
comprobar_solicitud_viaticos(
    $primera['estado_comision'] === 'PENDIENTE DE SOLICITUDES_V' && $primera['personal_pendiente'] === 1,
    'La comisión debe seguir pendiente mientras falte otra solicitud.',
);
comprobar_solicitud_viaticos($pdo->fecha_inicio === null, 'La comisión no debe iniciar mientras queden solicitudes pendientes.');
comprobar_solicitud_viaticos(
    $pdo->renglones[0]['num_dias'] === null && $pdo->renglones[0]['cuota_diaria'] === null,
    'Los días vacíos deben guardarse junto con una cuota nula.',
);

try {
    crear_solicitud_viaticos($pdo, $entrada, 10);
    throw new RuntimeException('No debe permitirse duplicar la solicitud de la misma persona.');
} catch (SolicitudViaticosConflictException) {
    comprobar_solicitud_viaticos(!$pdo->transaccion_activa, 'El duplicado debe revertir la transacción.');
    comprobar_solicitud_viaticos(count($pdo->solicitudes) === 1, 'El intento duplicado no debe guardar otra solicitud.');
}

$entrada['id_personal_comisionado'] = 42;
$entrada['observaciones'] = 'Última solicitud';
$entrada['renglones'][0]['num_dias'] = 0.5;
$entrada['renglones'][0]['importe'] = 425;
$ultima = crear_solicitud_viaticos($pdo, $entrada, 10);
comprobar_solicitud_viaticos(
    $ultima['estado_comision'] === 'EN CURSO' && $ultima['personal_pendiente'] === 0,
    'La comisión debe iniciar cuando todas las personas tengan solicitud.',
);
comprobar_solicitud_viaticos($pdo->fecha_inicio === date('Y-m-d'), 'Al iniciar debe guardarse la fecha de inicio.');
comprobar_solicitud_viaticos(
    $pdo->renglones[1]['cuota_diaria'] === '425.00',
    'La cuota sin pernocta debe guardarse para medio día.',
);

echo "Pruebas de creación de solicitudes de viáticos correctas.\n";
