# Reglas del proyecto

## Estilo de comunicación
- Caveman style: fragmentos cortos, sin artículos innecesarios, sin relleno.
- Siempre en español, no realizar ninguna pregunta, petición, comentario o explicación en inglés
- Simpre se explicarán los cambios realizados para que una persona principiante lo entienda

## Commits
- Después de cada tarea relevante (nueva feature, fix, refactor, migración, etc.), preguntar al usuario si desea hacer commit.
- Generar mensaje de commit en español, en formato convencional: `tipo: descripción breve`.
- No hacer commit sin confirmación explícita del usuario.

## Navegador
- NUNCA ejecutar comandos que abran el navegador (start http, start chrome, xdg-open, open https, etc.).

## Archivos temporales
- Eliminar inmediatamente después de usar: archivos de test manuales, scripts de prueba, archivos check/debug/tmp.
- No acumular basura en el repo.

## Permisos de comandos
- Permitido sin confirmación: php artisan, composer, npm, npx, node, git (lectura y escritura local), ls, rm, cp, mv, mkdir.
- Preguntar antes de: comandos que afecten el sistema operativo y/o paquetes globales.