# Prendas de invierno

El menú **Prendas de invierno** abre `/prendas-invierno/disponibilidad`, el acumulado por grupo. El seguimiento individual sigue en `/prendas-invierno`. Ambas pantallas están disponibles para usuarios con `ot index`.

## Disponibilidad por grupo

No requiere nuevas importaciones, asociaciones ni migraciones: reúne las líneas de las remisiones que ya están registradas como invierno. No incluye remisiones ajenas al listado ni salidas sin asociación confirmada.

- Agrupa por `maestro_codigos.grupo` del código completo; si falta, usa `grupo_plan` y luego la descripción de la remisión. Sin descripción se muestra **SIN CLASIFICAR**. No infiere grupos desde otras variantes ni desde una OT.
- Acumula prendas documentadas, recibido en Central, pendiente de recepción, enviado asociado, recibido en locales y en tránsito. Por defecto ordena de mayor a menor disponible.
- **Disponible Central = recibido confirmado − salidas asociadas válidas**, con el mismo criterio de incidencias del seguimiento por remisión. El saldo de documentos con diferencias queda fuera del disponible y se informa como **Saldo por revisar**. No es un stock físico certificado.
- Permite filtrar por grupo, origen, código/descripción/remisión y situación. El filtro de situación selecciona grupos completos: ver grupos con pendientes no oculta sus prendas ya recibidas.
- **Ver detalle** muestra cada código con su cantidad y remisión de origen, con filtro por código y acceso a **Ver remisión** para consultar sus asociaciones. Los totales incluyen todas las páginas.
- **Exportar Excel** descarga dos hojas, resumen por grupo y detalle de remisiones, con los mismos filtros. Preserva códigos, series y números como texto, y cantidades como valores numéricos.

El acumulado lee los datos actuales en cada consulta, por conjuntos, sin consultas por documento. Reimportar cantidades/recepciones o anular asociaciones actualiza la disponibilidad al volver a consultar. No modifica movimientos, cantidades ni asociaciones.

## Habilitar después del pull

```bash
php artisan migrate --path=database/migrations/2026_11_10_090000_create_invierno_trazabilidad_tables.php
php artisan optimize:clear
```

La migración agrega `invierno_remisiones` e `invierno_asignaciones`. Requiere que ya exista `ot_logistica_remisiones`; no vuelve a ejecutar las migraciones históricas de permisos. No modifica remisiones, stock ni vínculos de otros módulos.

## Uso

1. Mantener al día el archivo ENVIOS en la importación general de remisiones.
2. En **Prendas de invierno**, importar XLSX, XLS o CSV con encabezados `LOCAL`, `SERIE`, `NRO`. Hay plantilla descargable y registro individual. El límite es 10 MB y 20.000 documentos por archivo.
3. Se acepta el nombre del catálogo, sus equivalencias de la tabla siguiente o el código de sucursal. Los espacios de Excel (incluidos espacios no separables), mayúsculas y acentos del nombre se normalizan. La serie se conserva, incluida `Ñ`. Si dos sucursales del catálogo comparten un alias, el error muestra el nombre ingresado y los códigos para elegir el correcto.
4. Abrir **Ver detalle** para revisar las líneas de la remisión desde ese origen hacia Casa Central (código 1). Si todavía no está importada, el documento permanece como **Sin remisión importada** y se resolverá al actualizar ENVIOS.
5. En una línea recibida en Central, usar **Asociar salida**. Los candidatos deben ser remisiones de Central hacia otra sucursal, con el mismo código completo (incluye color y talle), fecha posterior o igual a la recepción y saldo sin asignar.
6. Verificar físicamente/documentalmente la procedencia y guardar la cantidad correspondiente. Los candidatos por código y fecha no acreditan por sí solos que las prendas salieron de esta devolución.
7. La confirmación de llegada al local se actualiza desde la importación general. Los vínculos se pueden anular con motivo, conservando fecha, usuario, artículo y documento originales. El Excel de resumen respeta los filtros.

Las filas válidas se registran aunque otras sean rechazadas. El resultado informa nuevas, existentes y rechazadas, mostrando hasta 50 errores de fila. Reimportar el mismo local/serie/número no duplica el seguimiento; se normalizan mayúsculas, espacios exteriores y ceros iniciales del número.

### Nombres equivalentes de locales

| Nombre del archivo | Nombres equivalentes en el catálogo |
| --- | --- |
| JARDINES | LOS JARDINES, JARDINES LUQUE |
| MARIANO | SHOPPING MARIANO, SHOP MARIANO |
| PINEDO | PINEDO SHOPPING, SHOPPING PINEDO |
| RURAL | LA RURAL |
| SHOPP | SHOP SAN LO3, SHOP SAN LO 3, SHOP SAN LORENZO, SHOPPING SAN LORENZO |
| SLG, SL | SAN LORENZO |
| MMALL | MALL |
| L06 | L06 SAN LO2, L06 SAN LO 2 |
| MULTI, MULTI PLAZA | MULTIPLAZA |

Estas equivalencias resuelven el código a partir del catálogo actual. No se deduce el origen por la serie: una remisión de `MALL` con serie `V` sigue siendo MALL. Las etiquetas de remisiones históricas solo completan nombres ausentes del catálogo; una etiqueta histórica contradictoria no vuelve ambiguo un nombre del maestro.

## Saldos y controles

- **Confirmado Central:** cantidad positiva de líneas con estado `RECIBIDO` y fecha de recepción válida, no futura ni anterior a su fecha de remisión/creación.
- **Pendiente Central:** cantidades documentadas menos cantidades confirmadas.
- **Enviado asociado:** cantidades vinculadas a salidas válidas. Se admiten divisiones de una devolución entre varios locales y de una salida entre varias devoluciones.
- **Recibido en locales:** cantidades asociadas cuya salida ya tiene recepción válida en su destino.
- **Saldo documental Central:** confirmado menos enviado asociado. Se muestra pendiente de revisión si hay inconsistencias; no equivale a stock físico.
- **Completo:** todas las líneas están confirmadas en Central, todas las unidades tienen salida asociada y esas salidas ya fueron recibidas en locales.

La asignación valida ambos saldos dentro de una transacción con bloqueo de las líneas en orden de ID. El saldo de cada salida se comparte entre todos los documentos de invierno y no puede reutilizarse. No hay asignaciones automáticas ni FIFO implícito. Los documentos que tienen historial no se pueden retirar.

Cada consulta vuelve a leer los estados y cantidades reales. Una reimportación que invalida recepción, artículo, destino, cronología o saldo deja el vínculo observado y evita cerrar el documento como completo. Los vínculos observados se excluyen de los envíos válidos; no se borran ni se reajustan cantidades silenciosamente. Las líneas duplicadas por código completo también quedan observadas.

Permisos existentes: `ot index` para consulta/plantilla/exportación, `ot importar` además para importar o registrar, `ot edit` además para asociar, anular o retirar documentos sin historial. El rol restringido `visualizador_accesorios` mantiene sus restricciones globales.

## Verificación

```bash
vendor/bin/phpunit tests/Feature/InviernoTrazabilidadTest.php
vendor/bin/phpunit tests/Feature/InviernoDisponibilidadTest.php
```

Pruebas con base aislada: importación real XLSX/CSV, duplicados y errores, identificación por origen/destino, recepción parcial, asociación manual, recepción final, restricciones de código/fecha/estado/cantidad, saldo compartido, anulaciones, cambios posteriores, vistas, Excel y autorización. Las consultas usan operaciones disponibles en PostgreSQL 9.5; la suite funcional usa SQLite en memoria.
