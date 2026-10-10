# Gestor de pedidos de hamburguesería

Aplicación web en PHP + MySQL para crear, consultar, editar y eliminar pedidos. Cada pedido registra dirección, estado (`preparando` o `despachado`) y cantidades de hamburguesas sencillas, dobles y mixtas.

## Requisitos
- PHP 8.0 o superior (con PDO MySQL y `mbstring`).
- MySQL/MariaDB.
- La base de datos `webjobs_burguerking` debe existir y el usuario configurado debe tener permisos para crear tablas y consultar/modificar registros.

## Instalación
1. Sube `config.php`, `index.php` y este README a una carpeta de tu alojamiento PHP.
2. Verifica los datos de conexión en `config.php`.
3. Abre `index.php` en el navegador. La aplicación ejecutará `CREATE TABLE IF NOT EXISTS` y creará la tabla `pedidos` si no existe.
4. Si tu proveedor no permite crear tablas con el usuario configurado, crea la tabla con el administrador de MySQL o solicita permisos.

## Base de datos
- Host: `mysql-webjobs.alwaysdata.net`
- Base: `webjobs_burguerking`
- Tabla: `pedidos`

Columnas: `id`, `direccion`, `estado`, `cantidad_sencilla`, `cantidad_doble`, `cantidad_mixta`, `creado_en`, `actualizado_en`.

## Funciones
- Registrar un pedido.
- Listar todos los pedidos y filtrar por estado.
- Editar dirección, estado y cantidades.
- Eliminar un pedido con confirmación.
- Validación de cantidades enteras no negativas y al menos una hamburguesa por pedido.

## Seguridad y publicación
Las credenciales se han incluido según lo solicitado para facilitar la instalación. No publiques `config.php` en un repositorio público; para un entorno real, usa variables de entorno y cambia cualquier contraseña que haya quedado expuesta. Publica el sitio únicamente en un alojamiento que ejecute PHP; abrir el archivo como `file://` no funcionará.
