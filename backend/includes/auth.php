<?php
require_once dirname(__DIR__) . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function crear_jwt(array $claims): string {
    if (JWT_SECRET === '') {
        throw new RuntimeException('SIGSA_JWT_SECRET no está configurada');
    }

    $header = ['typ' => 'JWT', 'alg' => 'HS256'];
    $codificar = static function (string $valor): string {
        return rtrim(strtr(base64_encode($valor), '+/', '-_'), '=');
    };

    $encabezado = $codificar(json_encode($header, JSON_UNESCAPED_SLASHES));
    $payload = $codificar(json_encode($claims, JSON_UNESCAPED_SLASHES));
    $firma = $codificar(hash_hmac('sha256', "$encabezado.$payload", JWT_SECRET, true));

    return "$encabezado.$payload.$firma";
}

function verificar_jwt(string $token): array {
    if (JWT_SECRET === '') {
        throw new RuntimeException('SIGSA_JWT_SECRET no está configurada');
    }

    $partes = explode('.', $token);
    if (count($partes) !== 3) {
        throw new UnexpectedValueException('Formato de JWT inválido');
    }

    $decodificar = static function (string $valor): string {
        if ($valor === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $valor)) {
            throw new UnexpectedValueException('Codificación de JWT inválida');
        }

        $base64 = strtr($valor, '-_', '+/');
        $base64 .= str_repeat('=', (4 - strlen($base64) % 4) % 4);
        $decodificado = base64_decode($base64, true);

        if ($decodificado === false) {
            throw new UnexpectedValueException('Codificación de JWT inválida');
        }

        return $decodificado;
    };

    $encabezado = json_decode($decodificar($partes[0]), true);
    $claims = json_decode($decodificar($partes[1]), true);
    $firma = $decodificar($partes[2]);

    if (!is_array($encabezado) || ($encabezado['alg'] ?? null) !== 'HS256' || ($encabezado['typ'] ?? null) !== 'JWT') {
        throw new UnexpectedValueException('Encabezado de JWT inválido');
    }

    if (!is_array($claims)) {
        throw new UnexpectedValueException('Contenido de JWT inválido');
    }

    $firmaEsperada = hash_hmac('sha256', "$partes[0].$partes[1]", JWT_SECRET, true);
    if (!hash_equals($firmaEsperada, $firma)) {
        throw new UnexpectedValueException('Firma de JWT inválida');
    }

    if (($claims['iss'] ?? null) !== 'sigsa'
        || !isset($claims['exp'])
        || !is_numeric($claims['exp'])
        || $claims['exp'] <= time()
    ) {
        throw new UnexpectedValueException('JWT expirado o inválido');
    }

    if (isset($claims['nbf']) && (!is_numeric($claims['nbf']) || $claims['nbf'] > time())) {
        throw new UnexpectedValueException('JWT aún no es válido');
    }

    return $claims;
}

function validar_jwt_requerido() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(["ERROR" => "Token de autenticación no proporcionado"]);
        exit;
    }

    try {
        $datos = verificar_jwt($matches[1]); 
        if (!$datos) {
            throw new Exception("Token no válido");
        }
        return $datos;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(["ERROR" => "Sesión inválida o expirada"]);
        exit;
    }
}

function validar_csrf_requerido() {
    $headers = getallheaders();
    $csrfToken = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (empty($csrfToken) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        http_response_code(403);
        echo json_encode(["ERROR" => "Petición no autorizada (CSRF inválido)"]);
        exit;
    }
}

// function requerir_autenticacion() {
    
//     if (!isset($_SESSION['id_personal'])) {
//         http_response_code(401);
//         echo json_encode(['error' => 'No has iniciado sesión']);
//         exit;
//     }
// }

function requerir_rol(array $puestos_permitidos) {
    // requerir_autenticacion();
    
    if (!in_array($_SESSION['puesto'], $puestos_permitidos,true)) {
        http_response_code(403);
        echo json_encode(['error' => 'No tienes permisos para realizar esta acción']);
        exit;
    }
}

function requerir_area(array $areas_permitidas) {
    // requerir_autenticacion();
    
    if (!in_array($_SESSION['area'], $areas_permitidas,true)) {
        http_response_code(403);
        echo json_encode(['error' => 'No tienes permisos para realizar esta acción']);
        exit;
    }
}

