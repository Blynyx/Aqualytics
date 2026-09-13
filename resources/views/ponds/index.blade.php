<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estanques</title>
</head>
<body>
    <h1>Mis estanques</h1>

    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Código</th>
                <th>Especie</th>
                <th>Ubicación</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($ponds as $pond)
                <tr>
                    <td>{{ $pond->name }}</td>
                    <td>{{ $pond->code }}</td>
                    <td>{{ $pond->species }}</td>
                    <td>{{ $pond->location }}</td>
                    <td>{{ $pond->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
