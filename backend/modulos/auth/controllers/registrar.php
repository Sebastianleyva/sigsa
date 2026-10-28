<?php
header('Content-type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        validar_jwt_requerido();
        validar_csrf_requerido();
        requerir_rol([1, 2, 3]);
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            http_response_code(400);
            echo json_encode(["ERROR" => "El cuerpo de la solicitud debe ser un objeto JSON válido"]);
            exit;
        }
        register($pdo, $input);
        break;
    default:
        http_response_code(405);
        echo json_encode(["ERROR" => "Método no permitido"]);
        break;
}

function registration_error($pdo, int $status, string $message): void
{
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code($status);
    echo json_encode(["ERROR" => $message]);
    exit;
}

function register($pdo, $input): void
{
    $required_strings = ['rfc', 'curp', 'nombre', 'apellido_p', 'apellido_m', 'correo', 'telefono', 'contrasena'];
    foreach ($required_strings as $field) {
        if (!isset($input[$field]) || !is_string($input[$field]) || trim($input[$field]) === '') {
            registration_error($pdo, 400, "El campo {$field} es obligatorio");
        }
    }

    $puesto_raw = $input['puesto'] ?? null;
    $area_raw = $input['area'] ?? null;
    $id_jefe_raw = $input['id_jefe'] ?? null;
    if ((!is_int($puesto_raw) && !is_string($puesto_raw))
        || ($area_raw !== null && !is_int($area_raw) && !is_string($area_raw))
        || ($id_jefe_raw !== null && !is_int($id_jefe_raw) && !is_string($id_jefe_raw))) {
        registration_error($pdo, 400, "El puesto, área o jefe no es válido");
    }

    $puesto = filter_var($puesto_raw, FILTER_VALIDATE_INT);
    $area = $area_raw !== null && $area_raw !== ''
        ? filter_var($area_raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
        : null;
    $id_jefe = $id_jefe_raw !== null && $id_jefe_raw !== ''
        ? filter_var($id_jefe_raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
        : null;

    if ($puesto === false || !in_array($puesto, [1, 2, 3, 4], true)
        || ($area === false) || ($id_jefe === false)) {
        registration_error($pdo, 400, "El puesto, área o jefe no es válido");
    }

    $max_lengths = [
        'rfc' => 13,
        'curp' => 18,
        'nombre' => 20,
        'apellido_p' => 25,
        'apellido_m' => 25,
        'correo' => 150,
        'telefono' => 12
    ];
    foreach ($max_lengths as $field => $max_length) {
        if (mb_strlen(trim($input[$field])) > $max_length) {
            registration_error($pdo, 400, "El campo {$field} excede la longitud permitida");
        }
    }
    if (!filter_var(trim($input['correo']), FILTER_VALIDATE_EMAIL)) {
        registration_error($pdo, 400, "El correo electrónico no es válido");
    }

    if ($puesto === 1 && ($area !== null || $id_jefe !== null)) {
        registration_error($pdo, 400, "El director no debe tener área ni jefe asignado");
    }
    if ($puesto !== 1 && ($area === null || $id_jefe === null)) {
        registration_error($pdo, 400, "El área y el jefe son obligatorios para este puesto");
    }

    $pdo->beginTransaction();
    try {
        // Serializa altas del mismo puesto para que los cupos activos no se excedan.
        $lock_puesto = $pdo->prepare('SELECT id_puesto FROM puestos WHERE id_puesto = :puesto FOR UPDATE');
        $lock_puesto->execute(['puesto' => $puesto]);
        if (!$lock_puesto->fetch()) {
            registration_error($pdo, 400, "El puesto seleccionado no existe");
        }

        $consulta_validacion = $pdo->prepare(
            'SELECT id_personal
             FROM personal
             WHERE correo = :correo OR rfc = :rfc OR curp = :curp OR telefono = :telefono
             LIMIT 1'
        );
        $consulta_validacion->execute([
            'correo' => trim($input['correo']),
            'rfc' => trim($input['rfc']),
            'curp' => trim($input['curp']),
            'telefono' => trim($input['telefono'])
        ]);
        if ($consulta_validacion->fetch()) {
            registration_error($pdo, 409, "El correo, RFC, CURP o teléfono ya está registrado");
        }

        if ($puesto === 1) {
            $consulta_cupo = $pdo->prepare(
                "SELECT COUNT(*) FROM personal WHERE puesto = 1 AND activo = '1'"
            );
            $consulta_cupo->execute();
            if ((int) $consulta_cupo->fetchColumn() >= 1) {
                registration_error($pdo, 409, "Ya existe un director activo");
            }
        } elseif ($puesto === 2) {
            $area_subdireccion = $pdo->prepare(
                "SELECT id_area FROM areas
                 WHERE id_area = :area
                   AND id_subdireccion IS NULL
                   AND UPPER(nombre) LIKE 'SUBDIRECCION%'"
            );
            $area_subdireccion->execute(['area' => $area]);
            if (!$area_subdireccion->fetch()) {
                registration_error($pdo, 400, "Selecciona una subdirección válida");
            }

            $consulta_total_subdirectores = $pdo->prepare(
                "SELECT COUNT(*) FROM personal WHERE puesto = 2 AND activo = '1'"
            );
            $consulta_total_subdirectores->execute();
            if ((int) $consulta_total_subdirectores->fetchColumn() >= 2) {
                registration_error($pdo, 409, "Ya están ocupadas las dos plazas de subdirector");
            }

            $consulta_cupo = $pdo->prepare(
                "SELECT COUNT(*) FROM personal
                 WHERE puesto = 2 AND area = :area AND activo = '1'"
            );
            $consulta_cupo->execute(['area' => $area]);
            if ((int) $consulta_cupo->fetchColumn() >= 1) {
                registration_error($pdo, 409, "Ya existe un subdirector activo en esa subdirección");
            }
        } else {
            $area_departamento = $pdo->prepare(
                'SELECT id_area, id_subdireccion FROM areas
                 WHERE id_area = :area AND id_subdireccion IS NOT NULL'
            );
            $area_departamento->execute(['area' => $area]);
            $departamento = $area_departamento->fetch();
            if (!$departamento) {
                registration_error($pdo, 400, "Selecciona un departamento vinculado a una subdirección");
            }
        }

        if ($puesto === 2) {
            $jefe_valido = $pdo->prepare(
                "SELECT id_personal FROM personal
                 WHERE id_personal = :id_jefe AND puesto = 1
                   AND area IS NULL AND activo = '1'"
            );
            $jefe_valido->execute(['id_jefe' => $id_jefe]);
        } elseif ($puesto === 3) {
            $jefe_valido = $pdo->prepare(
                "SELECT id_personal FROM personal
                 WHERE id_personal = :id_jefe AND puesto = 2
                   AND area = :area_jefe AND activo = '1'"
            );
            $jefe_valido->execute([
                'id_jefe' => $id_jefe,
                'area_jefe' => $departamento['id_subdireccion']
            ]);
        } elseif ($puesto === 4) {
            $jefe_valido = $pdo->prepare(
                "SELECT id_personal FROM personal
                 WHERE id_personal = :id_jefe AND puesto = 3
                   AND area = :area AND activo = '1'"
            );
            $jefe_valido->execute(['id_jefe' => $id_jefe, 'area' => $area]);
        }

        if ($puesto !== 1 && !$jefe_valido->fetch()) {
            registration_error($pdo, 400, "El jefe seleccionado no está activo o no corresponde al área y puesto");
        }

        $query = $pdo->prepare(
            "INSERT INTO personal
             (rfc, curp, nombre, apellido_p, apellido_m, correo, telefono, puesto, area, id_jefe, activo, contrasena)
             VALUES
             (:rfc, :curp, :nombre, :apellido_p, :apellido_m, :correo, :telefono, :puesto, :area, :id_jefe, '1', :contrasena)"
        );
        $query->execute([
            'rfc' => trim($input['rfc']),
            'curp' => trim($input['curp']),
            'nombre' => trim($input['nombre']),
            'apellido_p' => trim($input['apellido_p']),
            'apellido_m' => trim($input['apellido_m']),
            'correo' => trim($input['correo']),
            'telefono' => trim($input['telefono']),
            'puesto' => $puesto,
            'area' => $area,
            'id_jefe' => $id_jefe,
            'contrasena' => password_hash($input['contrasena'], PASSWORD_BCRYPT)
        ]);
        $nuevo_id = $pdo->lastInsertId();
        $pdo->commit();

        http_response_code(201);
        echo json_encode([
            "message" => "Usuario creado con éxito",
            "usuario" => [
                "id" => $nuevo_id,
                "nombre" => trim($input['nombre']),
                "curp" => trim($input['curp']),
                "correo" => trim($input['correo'])
            ]
        ]);
    } catch (PDOException $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error al registrar personal: " . $error->getMessage());
        http_response_code(500);
        echo json_encode(["ERROR" => "No fue posible registrar al usuario"]);
    }
}
