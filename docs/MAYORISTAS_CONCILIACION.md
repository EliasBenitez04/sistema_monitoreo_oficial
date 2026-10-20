# Control mensual de Mayoristas — reglas de auditoría

## Fuentes y límites

- `ventas`: solo `UPPER(TRIM(local)) = COMERCIAL MATRIZ`. La factura se reconstruye a partir de fecha + comprobante + código de cliente + cliente. No existe clave nativa factura→remisión. Las notas de crédito se conservan como movimientos negativos.
- `ot_logistica_remisiones`: únicamente entradas destinadas a COMERCIAL MATRIZ por `sucursal_destino` o `sucursal_logistica`. Las salidas **desde** COMERCIAL MATRIZ a otros destinos **no** son ingresos de mayoristas. Se distingue `fecha_remision` de `fecha_recepcion`.
- `ot_logistica_detalle`: plan asignado. `created_at` **no certifica mes efectivo de separación** y no debe utilizarse como sustituto.
- `mayorista_separaciones`: cantidades efectivamente separadas, con fecha y referencia anotadas y confirmadas por un usuario. Su suma nunca puede exceder el total de la línea del plan.
- `mayorista_vinculos`: relación aprobada de una factura con una remisión y una cantidad específica por código **completo**. Permite 1:N, N:1, N:M. Nunca representa por sí sola entrega al comprador.
- `mayorista_auditoria`: registro de confirmaciones, desvinculaciones, separaciones, anulaciones y aperturas.
- `mayorista_saldos_iniciales`: cantidades iniciales declaradas por código y mes, con referencia. Si falta una apertura **no se supone 0**.
- Fotos (MODELO MUESTRA): hasta 1 por OT por defecto, configurable. Es una estimación **no confirmada**, no constituye venta ni salida física.

## Fórmulas

- Facturación bruta = suma de cantidades **positivas** de los comprobantes.
- Devoluciones = suma del valor absoluto de cantidades **negativas**.
- Facturación neta = facturación bruta - devoluciones.
- Pendiente de asignación documental por factura = bruta - unidades manualmente vinculadas, sin valor negativo.
- Disponible de remisión = cantidad remisión - total vinculado a cualquier factura, sin valor negativo.
- Capacidad confirmable = mínimo(saldo factura por código, saldo remisión); bajo bloqueo transaccional.
- Saldo documental estimado por código (solo si apertura respaldada) = inicial declarado + ingresos **con fecha de recepción dentro del mes** - facturación neta.
- Remitido = fecha de emisión dentro del mes; recibido = fecha de recepción dentro del mes. No siempre coinciden en el mismo período.
- Remisiones previas sin vínculo = saldo documental de cantidad de cada remisión previa aún sin asignación; **no** equivale a stock físico disponible. Lista de muestra limitada a 100 documentos más recientes.
- Prendas reservadas para fotos permanecen físicamente en inventario; no se restan como ventas.
- Redistribuciones pueden mover prendas ya registradas antes; jamás sumar plan+remisiones+ventas como producción adicional o stock físico.

## Estados

- `CONFIRMADA DOCUMENTALMENTE`: la cantidad positiva de **cada código** está completamente asociada mediante confirmaciones manuales.
- `PARCIAL`: parte de la cantidad facturada ha sido asociada.
- `PROBABLE`: existe una candidata suficiente por cada código, pero ninguna relación está confirmada.
- `AMBIGUA`: existen múltiples combinaciones de remisiones candidatas.
- `SIN COINCIDENCIA`: remisiones candidatas insuficientes para cubrir el contenido de la factura.
- `SIN VALIDAR`: estado de la lista mensual antes de abrir ficha (no se simula una búsqueda de candidatos para todos los comprobantes).
- `NOTA DE CRÉDITO`: comprobante sin cantidad positiva. La devolución comercial no implica que la prenda físicamente haya reingresado.

Ningún candidato queda asociado sin referencia y aprobación. Las coincidencias de fecha y cantidad total son indicadores de búsqueda, no pruebas.

## Casos de prueba mínimos

1. Remisión Central → Comercial Matriz por 720 unidades; factura mayorista de 720 unidades **mismos códigos completos**: candidata probable, hasta confirmar.
2. Remisiones de dos locales y Central: 300+220+200=720, mismo código completo: varias candidatas; asignación manual 300+220+200.
3. Una remisión de 720 unidades sirve a dos facturas de 300 y 420: no debe quedar saldo de asignación al terminar.
4. Compras/comprobantes de meses posteriores pueden utilizar remisiones previas aún no asignadas, evitando doble uso.
5. Si 720 totales coinciden pero hay talles, colores o códigos distintos: no hay confirmación.
6. Con nota de crédito y/o ausencia de apertura: no crear entrada física ni suponer saldo inicial cero.
7. Código ausente o ambiguo en relación con una OT: no asignar OT por descripción.
8. Resumen mensual: fechas de separación, emisión, recepción y venta se analizan por separado.

## Limitaciones deliberadas

- No hay prueba de entrega al comprador ni stock físico inicial importado en las tablas originales.
- `sucursal_destino` y `sucursal_logistica` requieren revisión con datos reales para otros alias que no sean exactamente COMERCIAL MATRIZ.
- No se habilita un estado de `CERRADO FÍSICAMENTE` sin recuento y auditoría de inventario independiente.
- La búsqueda de candidatos de una factura se limita a 300 remisiones; avisa al usuario si hay más, evitando concluir falsamente que no existen otras.
- Las referencias a saldos iniciales y fechas de separación son declaraciones auditadas, no reemplazan comprobantes externos.
- Se requiere ejecutar la migración antes de abrir la pantalla.

## Verificación operativa

```powershell
php artisan migrate --force
php artisan optimize:clear
php artisan test --filter=MayoristaConciliacionTest
```

Después comprobar en PostgreSQL un mes cerrado y otro vigente, registros de `COMERCIAL MATRIZ`, remisiones previas, notas de crédito y relaciones muchos-a-muchos.
