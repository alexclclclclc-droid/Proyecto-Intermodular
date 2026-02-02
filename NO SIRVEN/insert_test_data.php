<?php
/**
 * Script para insertar datos de prueba
 */

require_once 'config/config.php';
require_once 'dao/UsuarioDAO.php';
require_once 'dao/ReservaDAO.php';
require_once 'dao/ApartamentoDAO.php';

try {
    $usuarioDAO = new UsuarioDAO();
    $reservaDAO = new ReservaDAO();
    $apartamentoDAO = new ApartamentoDAO();
    
    echo "Insertando datos de prueba...\n";
    
    // Insertar usuarios de prueba
    $usuarios = [
        [
            'nombre' => 'Juan',
            'apellidos' => 'Pérez García',
            'email' => 'juan@test.com',
            'password' => password_hash('123456', PASSWORD_DEFAULT),
            'telefono' => '666111222',
            'rol' => 'usuario'
        ],
        [
            'nombre' => 'María',
            'apellidos' => 'López Martín',
            'email' => 'maria@test.com',
            'password' => password_hash('123456', PASSWORD_DEFAULT),
            'telefono' => '666333444',
            'rol' => 'usuario'
        ],
        [
            'nombre' => 'Carlos',
            'apellidos' => 'Ruiz Sánchez',
            'email' => 'carlos@test.com',
            'password' => password_hash('123456', PASSWORD_DEFAULT),
            'telefono' => '666555666',
            'rol' => 'usuario'
        ]
    ];
    
    $conn = Database::getInstance()->getConnection();
    
    foreach ($usuarios as $userData) {
        $sql = "INSERT INTO usuarios (nombre, apellidos, email, password, telefono, rol, activo, verificado) 
                VALUES (:nombre, :apellidos, :email, :password, :telefono, :rol, 1, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->execute($userData);
        echo "Usuario {$userData['email']} insertado\n";
    }
    
    // Insertar apartamentos de prueba
    $apartamentos = [
        [
            'n_registro' => 'AT-001-2024',
            'nombre' => 'Apartamento Centro Salamanca',
            'direccion' => 'Plaza Mayor, 15',
            'codigo_postal' => '37001',
            'provincia' => 'Salamanca',
            'municipio' => 'Salamanca',
            'localidad' => 'Salamanca',
            'telefono_1' => '923123456',
            'email' => 'info@apartcentro.com',
            'plazas' => 4,
            'categoria' => '3 estrellas',
            'gps_latitud' => 40.9701,
            'gps_longitud' => -5.6635
        ],
        [
            'n_registro' => 'AT-002-2024',
            'nombre' => 'Casa Rural Valladolid',
            'direccion' => 'Calle Real, 25',
            'codigo_postal' => '47001',
            'provincia' => 'Valladolid',
            'municipio' => 'Valladolid',
            'localidad' => 'Valladolid',
            'telefono_1' => '983654321',
            'email' => 'reservas@casarural.com',
            'plazas' => 6,
            'categoria' => '4 estrellas',
            'gps_latitud' => 41.6523,
            'gps_longitud' => -4.7245
        ],
        [
            'n_registro' => 'AT-003-2024',
            'nombre' => 'Apartamento León Centro',
            'direccion' => 'Calle Ancha, 8',
            'codigo_postal' => '24001',
            'provincia' => 'León',
            'municipio' => 'León',
            'localidad' => 'León',
            'telefono_1' => '987789123',
            'email' => 'contacto@leoncentro.com',
            'plazas' => 2,
            'categoria' => '2 estrellas',
            'gps_latitud' => 42.5987,
            'gps_longitud' => -5.5671
        ]
    ];
    
    foreach ($apartamentos as $aptData) {
        $sql = "INSERT INTO apartamentos (n_registro, nombre, direccion, codigo_postal, provincia, municipio, localidad, telefono_1, email, plazas, categoria, gps_latitud, gps_longitud, activo) 
                VALUES (:n_registro, :nombre, :direccion, :codigo_postal, :provincia, :municipio, :localidad, :telefono_1, :email, :plazas, :categoria, :gps_latitud, :gps_longitud, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->execute($aptData);
        echo "Apartamento {$aptData['nombre']} insertado\n";
    }
    
    // Insertar reservas de prueba
    $reservas = [
        [
            'id_usuario' => 2, // Juan
            'id_apartamento' => 1,
            'fecha_entrada' => '2024-03-15',
            'fecha_salida' => '2024-03-18',
            'num_huespedes' => 2,
            'precio_total' => 180.00,
            'estado' => 'confirmada'
        ],
        [
            'id_usuario' => 3, // María
            'id_apartamento' => 2,
            'fecha_entrada' => '2024-04-01',
            'fecha_salida' => '2024-04-05',
            'num_huespedes' => 4,
            'precio_total' => 320.00,
            'estado' => 'pendiente'
        ],
        [
            'id_usuario' => 4, // Carlos
            'id_apartamento' => 3,
            'fecha_entrada' => '2024-02-20',
            'fecha_salida' => '2024-02-22',
            'num_huespedes' => 1,
            'precio_total' => 90.00,
            'estado' => 'completada'
        ],
        [
            'id_usuario' => 2, // Juan
            'id_apartamento' => 1,
            'fecha_entrada' => '2024-05-10',
            'fecha_salida' => '2024-05-12',
            'num_huespedes' => 2,
            'precio_total' => 120.00,
            'estado' => 'cancelada'
        ]
    ];
    
    foreach ($reservas as $resData) {
        $sql = "INSERT INTO reservas (id_usuario, id_apartamento, fecha_entrada, fecha_salida, num_huespedes, precio_total, estado) 
                VALUES (:id_usuario, :id_apartamento, :fecha_entrada, :fecha_salida, :num_huespedes, :precio_total, :estado)";
        $stmt = $conn->prepare($sql);
        $stmt->execute($resData);
        echo "Reserva insertada\n";
    }
    
    echo "\n¡Datos de prueba insertados correctamente!\n";
    echo "Usuarios creados: " . count($usuarios) . "\n";
    echo "Apartamentos creados: " . count($apartamentos) . "\n";
    echo "Reservas creadas: " . count($reservas) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}